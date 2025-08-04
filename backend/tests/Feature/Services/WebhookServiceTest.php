<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Jobs\DeliverWebhookJob;
use App\Models\Project;
use App\Models\User;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class WebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    private WebhookService $webhookService;
    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->webhookService = new WebhookService();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create([
            'webhook_enabled' => true,
        ]);

        // Create project-user relationship
        $this->project->users()->attach($this->user->id, ['role' => 'owner']);
    }

    public function test_sends_workspace_webhook_successfully(): void
    {
        Queue::fake();

        $webhook = Webhook::create([
            'project_id' => $this->project->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_test123',
            'triggers' => ['link.created', 'link.clicked'],
        ]);

        $data = [
            'link_id' => 'link_123',
            'url' => 'https://example.com',
        ];

        $this->webhookService->sendWorkspaceWebhook('link.created', $this->project, $data);

        Queue::assertPushed(DeliverWebhookJob::class);
    }

    public function test_skips_webhook_when_disabled_for_project(): void
    {
        Queue::fake();

        $this->project->update(['webhook_enabled' => false]);

        $webhook = Webhook::create([
            'project_id' => $this->project->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_test123',
            'triggers' => ['link.created'],
        ]);

        $data = ['link_id' => 'link_123'];

        $this->webhookService->sendWorkspaceWebhook('link.created', $this->project, $data);

        Queue::assertNotPushed(DeliverWebhookJob::class);
    }

    public function test_skips_webhook_when_no_matching_triggers(): void
    {
        Queue::fake();

        $webhook = Webhook::create([
            'project_id' => $this->project->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_test123',
            'triggers' => ['link.deleted'], // Different trigger
        ]);

        $data = ['link_id' => 'link_123'];

        $this->webhookService->sendWorkspaceWebhook('link.created', $this->project, $data);

        Queue::assertNotPushed(DeliverWebhookJob::class);
    }

    public function test_skips_disabled_webhooks(): void
    {
        Queue::fake();

        $webhook = Webhook::create([
            'project_id' => $this->project->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_test123',
            'triggers' => ['link.created'],
            'disabled_at' => now(),
        ]);

        $data = ['link_id' => 'link_123'];

        $this->webhookService->sendWorkspaceWebhook('link.created', $this->project, $data);

        Queue::assertNotPushed(DeliverWebhookJob::class);
    }

    public function test_creates_webhook_successfully(): void
    {
        $webhook = $this->webhookService->createWebhook(
            $this->project,
            'Test Webhook',
            'https://example.com/webhook',
            ['link.created', 'link.updated']
        );

        $this->assertDatabaseHas('webhooks', [
            'id' => $webhook->id,
            'project_id' => $this->project->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
        ]);

        $this->assertEquals(['link.created', 'link.updated'], $webhook->triggers);
        $this->assertStringStartsWith('whsec_', $webhook->secret);
    }

    public function test_creates_webhook_with_custom_secret(): void
    {
        $customSecret = 'custom_secret_123';

        $webhook = $this->webhookService->createWebhook(
            $this->project,
            'Test Webhook',
            'https://example.com/webhook',
            ['link.created'],
            $customSecret
        );

        $this->assertEquals($customSecret, $webhook->secret);
    }

    public function test_validates_webhook_triggers(): void
    {
        $this->assertTrue($this->webhookService->isValidTrigger('link.created'));
        $this->assertTrue($this->webhookService->isValidTrigger('link.clicked'));
        $this->assertTrue($this->webhookService->isValidTrigger('lead.created'));
        $this->assertTrue($this->webhookService->isValidTrigger('sale.created'));

        $this->assertFalse($this->webhookService->isValidTrigger('invalid.trigger'));
        $this->assertFalse($this->webhookService->isValidTrigger(''));
    }

    public function test_generates_sample_payloads(): void
    {
        $linkCreatedPayload = $this->webhookService->getSamplePayload('link.created');
        $this->assertArrayHasKey('id', $linkCreatedPayload);
        $this->assertArrayHasKey('domain', $linkCreatedPayload);
        $this->assertArrayHasKey('url', $linkCreatedPayload);

        $linkClickedPayload = $this->webhookService->getSamplePayload('link.clicked');
        $this->assertArrayHasKey('link', $linkClickedPayload);
        $this->assertArrayHasKey('click', $linkClickedPayload);

        $leadCreatedPayload = $this->webhookService->getSamplePayload('lead.created');
        $this->assertArrayHasKey('id', $leadCreatedPayload);
        $this->assertArrayHasKey('link_id', $leadCreatedPayload);

        $saleCreatedPayload = $this->webhookService->getSamplePayload('sale.created');
        $this->assertArrayHasKey('amount', $saleCreatedPayload);
        $this->assertArrayHasKey('currency', $saleCreatedPayload);
    }

    public function test_sends_multiple_webhooks_for_same_trigger(): void
    {
        Queue::fake();

        // Create two webhooks with the same trigger
        $webhook1 = Webhook::create([
            'project_id' => $this->project->id,
            'name' => 'Webhook 1',
            'url' => 'https://example1.com/webhook',
            'secret' => 'whsec_test123',
            'triggers' => ['link.created'],
        ]);

        $webhook2 = Webhook::create([
            'project_id' => $this->project->id,
            'name' => 'Webhook 2',
            'url' => 'https://example2.com/webhook',
            'secret' => 'whsec_test456',
            'triggers' => ['link.created'],
        ]);

        $data = ['link_id' => 'link_123'];

        $this->webhookService->sendWorkspaceWebhook('link.created', $this->project, $data);

        // Assert both webhooks were queued
        Queue::assertPushed(DeliverWebhookJob::class, 2);
    }

    public function test_webhook_constants_are_defined(): void
    {
        $expectedTriggers = [
            'link.created',
            'link.updated',
            'link.deleted',
            'link.clicked',
            'lead.created',
            'sale.created',
            'commission.created',
            'partner.enrolled',
        ];

        $this->assertEquals($expectedTriggers, WebhookService::WEBHOOK_TRIGGERS);
    }
}
