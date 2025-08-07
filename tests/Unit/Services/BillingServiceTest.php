<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BillingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BillingService;
    }

    public function test_can_create_link_free_plan(): void
    {
        $project = Project::factory()->create([
            'plan' => 'free',
            'links_usage' => 20,
        ]);

        $canCreate = $this->service->canCreateLink($project);

        $this->assertTrue($canCreate); // 20 < 25 (free plan limit)
    }

    public function test_cannot_create_link_over_limit(): void
    {
        $project = Project::factory()->create([
            'plan' => 'free',
            'links_usage' => 25,
        ]);

        $canCreate = $this->service->canCreateLink($project);

        $this->assertFalse($canCreate); // 25 >= 25 (free plan limit)
    }

    public function test_can_create_link_unlimited_plan(): void
    {
        $project = Project::factory()->create([
            'plan' => 'enterprise',
            'links_usage' => 100000,
        ]);

        $canCreate = $this->service->canCreateLink($project);

        $this->assertTrue($canCreate); // Enterprise has unlimited links
    }

    public function test_can_handle_click_within_limit(): void
    {
        $project = Project::factory()->create([
            'plan' => 'free',
            'usage' => 500,
        ]);

        $canHandle = $this->service->canHandleClick($project);

        $this->assertTrue($canHandle); // 500 < 1000 (free plan limit)
    }

    public function test_cannot_handle_click_over_limit(): void
    {
        $project = Project::factory()->create([
            'plan' => 'free',
            'usage' => 1000,
        ]);

        $canHandle = $this->service->canHandleClick($project);

        $this->assertFalse($canHandle); // 1000 >= 1000 (free plan limit)
    }

    public function test_increment_link_usage(): void
    {
        $project = Project::factory()->create([
            'links_usage' => 5,
            'total_links' => 10,
        ]);

        $this->service->incrementLinkUsage($project);

        $project->refresh();
        $this->assertEquals(6, $project->links_usage);
        $this->assertEquals(11, $project->total_links);
    }

    public function test_increment_click_usage(): void
    {
        $project = Project::factory()->create([
            'usage' => 100,
            'total_clicks' => 500,
        ]);

        $this->service->incrementClickUsage($project, 5);

        $project->refresh();
        $this->assertEquals(105, $project->usage);
        $this->assertEquals(505, $project->total_clicks);
    }

    public function test_get_usage_stats(): void
    {
        $project = Project::factory()->create([
            'plan' => 'starter',
            'links_usage' => 500,
            'usage' => 10000,
            'ai_usage' => 50,
        ]);

        $stats = $this->service->getUsageStats($project);

        $this->assertEquals('starter', $stats['plan']);
        $this->assertEquals(500, $stats['usage']['links']['used']);
        $this->assertEquals(1000, $stats['usage']['links']['limit']);
        $this->assertEquals(50.0, $stats['usage']['links']['percentage']);

        $this->assertEquals(10000, $stats['usage']['clicks']['used']);
        $this->assertEquals(25000, $stats['usage']['clicks']['limit']);
        $this->assertEquals(40.0, $stats['usage']['clicks']['percentage']);
    }

    public function test_is_over_limits(): void
    {
        $project = Project::factory()->create([
            'plan' => 'free',
            'links_usage' => 30, // Over 25 limit
            'usage' => 1200,     // Over 1000 limit
            'ai_usage' => 15,    // Over 10 limit
        ]);

        $overages = $this->service->isOverLimits($project);

        $this->assertEquals(5, $overages['links']); // 30 - 25
        $this->assertEquals(200, $overages['clicks']); // 1200 - 1000
        $this->assertEquals(5, $overages['ai']); // 15 - 10
    }

    public function test_is_not_over_limits(): void
    {
        $project = Project::factory()->create([
            'plan' => 'free',
            'links_usage' => 20,
            'usage' => 800,
            'ai_usage' => 8,
        ]);

        $overages = $this->service->isOverLimits($project);

        $this->assertEmpty($overages);
    }

    public function test_get_plan_limits(): void
    {
        $freeLimits = $this->service->getPlanLimits('free');
        $this->assertEquals(25, $freeLimits['links']);
        $this->assertEquals(1000, $freeLimits['clicks']);

        $starterLimits = $this->service->getPlanLimits('starter');
        $this->assertEquals(1000, $starterLimits['links']);
        $this->assertEquals(25000, $starterLimits['clicks']);

        $enterpriseLimits = $this->service->getPlanLimits('enterprise');
        $this->assertEquals(-1, $enterpriseLimits['links']); // unlimited
        $this->assertEquals(-1, $enterpriseLimits['clicks']); // unlimited
    }

    public function test_reset_monthly_usage(): void
    {
        $project = Project::factory()->create([
            'usage' => 500,
            'monthly_clicks' => 300,
            'current_month' => '2024-07',
        ]);

        $this->service->resetMonthlyUsage($project);

        $project->refresh();
        $this->assertEquals(0, $project->usage);
        $this->assertEquals(0, $project->monthly_clicks);
        $this->assertEquals(now()->format('Y-m'), $project->current_month);
    }

    public function test_increment_ai_usage(): void
    {
        $project = Project::factory()->create([
            'ai_usage' => 5,
        ]);

        $this->service->incrementAiUsage($project, 3);

        $project->refresh();
        $this->assertEquals(8, $project->ai_usage);
    }
}
