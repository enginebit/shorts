<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\DeliverWebhookJob;
use App\Models\Project;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class DeliverWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;
    private Webhook $webhook;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
        
        // Create project-user relationship
        $this->project->users()->attach($this->user->id, ['role' => 'owner']);
        
        $this->webhook = Webhook::create([
            'project_id' => $this->project->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_test123',
            'triggers' => ['link.created', 'link.clicked'],
        ]);
    }

    public function test_webhook_delivery_success(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['status' => 'ok'], 200),
        ]);

        $payload = [
            'id' => 'evt_123',
            'event' => 'link.created',
            'data' => ['link_id' => 'link_123'],
        ];

        $job = new DeliverWebhookJob(
            $this->webhook->id,
            'evt_123',
            'link.created',
            $payload
        );

        $job->handle();

        // Assert HTTP request was made
        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/webhook' &&
                   $request->hasHeader('X-Shorts-Event', 'link.created') &&
                   $request->hasHeader('X-Shorts-Signature');
        });

        // Assert webhook success was recorded
        $this->webhook->refresh();
        $this->assertEquals(0, $this->webhook->failure_count);
        $this->assertNotNull($this->webhook->last_success_at);
    }

    public function test_webhook_delivery_failure(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['error' => 'Server Error'], 500),
        ]);

        $payload = [
            'id' => 'evt_123',
            'event' => 'link.created',
            'data' => ['link_id' => 'link_123'],
        ];

        $job = new DeliverWebhookJob(
            $this->webhook->id,
            'evt_123',
            'link.created',
            $payload,
            3 // Final attempt
        );

        $this->expectException(\Exception::class);
        $job->handle();

        // Assert webhook was disabled after final failure
        $this->webhook->refresh();
        $this->assertEquals(1, $this->webhook->failure_count);
        $this->assertNotNull($this->webhook->disabled_at);
    }

    public function test_webhook_not_found(): void
    {
        $payload = [
            'id' => 'evt_123',
            'event' => 'link.created',
            'data' => ['link_id' => 'link_123'],
        ];

        $job = new DeliverWebhookJob(
            'nonexistent_webhook_id',
            'evt_123',
            'link.created',
            $payload
        );

        // Should not throw exception, just log and return
        $job->handle();

        // No HTTP requests should be made
        Http::assertNothingSent();
    }

    public function test_disabled_webhook_skipped(): void
    {
        $this->webhook->update(['disabled_at' => now()]);

        $payload = [
            'id' => 'evt_123',
            'event' => 'link.created',
            'data' => ['link_id' => 'link_123'],
        ];

        $job = new DeliverWebhookJob(
            $this->webhook->id,
            'evt_123',
            'link.created',
            $payload
        );

        $job->handle();

        // No HTTP requests should be made for disabled webhook
        Http::assertNothingSent();
    }

    public function test_webhook_signature_generation(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['status' => 'ok'], 200),
        ]);

        $payload = [
            'id' => 'evt_123',
            'event' => 'link.created',
            'data' => ['link_id' => 'link_123'],
        ];

        $job = new DeliverWebhookJob(
            $this->webhook->id,
            'evt_123',
            'link.created',
            $payload
        );

        $job->handle();

        // Assert signature header is present and properly formatted
        Http::assertSent(function ($request) {
            $signature = $request->header('X-Shorts-Signature')[0] ?? '';
            return str_starts_with($signature, 'v1=') && strlen($signature) > 10;
        });
    }

    public function test_job_queued_on_correct_queue(): void
    {
        Queue::fake();

        DeliverWebhookJob::dispatch(
            $this->webhook->id,
            'evt_123',
            'link.created',
            ['data' => 'test']
        );

        Queue::assertPushedOn('webhooks', DeliverWebhookJob::class);
    }

    public function test_job_retry_configuration(): void
    {
        $job = new DeliverWebhookJob(
            $this->webhook->id,
            'evt_123',
            'link.created',
            ['data' => 'test']
        );

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(30, $job->timeout);
        $this->assertEquals([1, 5, 10], $job->backoff());
    }
}
