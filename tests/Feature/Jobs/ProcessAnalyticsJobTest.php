<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessAnalyticsJob;
use App\Jobs\SendLimitEmailJob;
use App\Models\Link;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class ProcessAnalyticsJobTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    private Link $link;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create([
            'usage_limit' => 1000,
            'links_limit' => 10,
        ]);

        // Create project-user relationship
        $this->project->users()->attach($this->user->id, ['role' => 'owner']);

        $this->link = Link::create([
            'id' => 'link_test123',
            'domain' => 'dub.sh',
            'key' => 'test123',
            'url' => 'https://example.com',
            'short_link' => 'https://dub.sh/test123',
            'title' => 'Test Link',
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'clicks' => 0,
            'unique_clicks' => 0,
        ]);
    }

    public function test_processes_click_analytics_successfully(): void
    {
        $clickData = [
            'country' => 'US',
            'city' => 'San Francisco',
            'device' => 'Desktop',
            'browser' => 'Chrome',
            'os' => 'macOS',
            'referrer' => 'https://google.com',
            'timestamp' => now()->toISOString(),
        ];

        $job = new ProcessAnalyticsJob($this->link->id, $clickData);
        $job->handle();

        // Assert link stats were updated
        $this->link->refresh();
        $this->assertEquals(1, $this->link->clicks);
        $this->assertEquals(1, $this->link->unique_clicks);
        $this->assertNotNull($this->link->last_clicked);

        // Assert project stats were updated
        $this->project->refresh();
        $this->assertEquals(1, $this->project->usage);
    }

    public function test_updates_monthly_usage_correctly(): void
    {
        $currentMonth = now()->format('Y-m');
        $this->project->update([
            'current_month' => $currentMonth,
            'monthly_clicks' => 5,
        ]);

        $clickData = [
            'country' => 'US',
            'timestamp' => now()->toISOString(),
        ];

        $job = new ProcessAnalyticsJob($this->link->id, $clickData);
        $job->handle();

        // Assert monthly clicks were incremented
        $this->project->refresh();
        $this->assertEquals(6, $this->project->monthly_clicks);
        $this->assertEquals($currentMonth, $this->project->current_month);
    }

    public function test_resets_monthly_usage_for_new_month(): void
    {
        $lastMonth = now()->subMonth()->format('Y-m');
        $this->project->update([
            'current_month' => $lastMonth,
            'monthly_clicks' => 100,
        ]);

        $clickData = [
            'country' => 'US',
            'timestamp' => now()->toISOString(),
        ];

        $job = new ProcessAnalyticsJob($this->link->id, $clickData);
        $job->handle();

        // Assert monthly usage was reset for new month
        $this->project->refresh();
        $this->assertEquals(1, $this->project->monthly_clicks);
        $this->assertEquals(now()->format('Y-m'), $this->project->current_month);
    }

    public function test_triggers_usage_limit_warning_at_80_percent(): void
    {
        Queue::fake();

        $this->project->update([
            'usage_limit' => 100,
            'usage' => 79, // Will become 80 after processing
        ]);

        $clickData = [
            'country' => 'US',
            'timestamp' => now()->toISOString(),
        ];

        $job = new ProcessAnalyticsJob($this->link->id, $clickData);
        $job->handle();

        // Assert limit warning email was queued
        Queue::assertPushed(SendLimitEmailJob::class);
    }

    public function test_triggers_usage_limit_exceeded_at_100_percent(): void
    {
        Queue::fake();

        $this->project->update([
            'usage_limit' => 100,
            'usage' => 99, // Will become 100 after processing
        ]);

        $clickData = [
            'country' => 'US',
            'timestamp' => now()->toISOString(),
        ];

        $job = new ProcessAnalyticsJob($this->link->id, $clickData);
        $job->handle();

        // Assert limit exceeded email was queued
        Queue::assertPushed(SendLimitEmailJob::class);
    }

    public function test_handles_nonexistent_link_gracefully(): void
    {
        $clickData = [
            'country' => 'US',
            'timestamp' => now()->toISOString(),
        ];

        $job = new ProcessAnalyticsJob('nonexistent_link_id', $clickData);

        // Should not throw exception, just log and return
        $job->handle();

        // Original link should be unchanged
        $this->link->refresh();
        $this->assertEquals(0, $this->link->clicks);
    }

    public function test_job_queued_on_correct_queue(): void
    {
        Queue::fake();

        ProcessAnalyticsJob::dispatch('link_123', ['country' => 'US']);

        Queue::assertPushedOn('analytics', ProcessAnalyticsJob::class);
    }

    public function test_job_retry_configuration(): void
    {
        $job = new ProcessAnalyticsJob('link_123', ['country' => 'US']);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(120, $job->timeout);
        $this->assertEquals([1, 5, 10], $job->backoff());
    }

    public function test_processes_multiple_analytics_fields(): void
    {
        $clickData = [
            'country' => 'US',
            'city' => 'San Francisco',
            'device' => 'Mobile',
            'browser' => 'Safari',
            'os' => 'iOS',
            'referrer' => 'https://twitter.com',
            'timestamp' => now()->toISOString(),
        ];

        $job = new ProcessAnalyticsJob($this->link->id, $clickData);
        $job->handle();

        // Verify all analytics data was processed (logged)
        $this->link->refresh();
        $this->assertEquals(1, $this->link->clicks);
        $this->assertNotNull($this->link->last_clicked);
    }
}
