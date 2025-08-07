<?php

declare(strict_types=1);

namespace Tests\Feature\Analytics;

use App\Models\Link;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Analytics Dashboard Tests
 *
 * Tests the analytics dashboard functionality including:
 * - Dashboard data display
 * - Link click tracking
 * - Analytics filtering by time periods
 * - Workspace-specific analytics
 */
class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        $response = $this->actingAs($user)->get("/{$workspace->slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('dashboard/index'));
    }

    public function test_dashboard_displays_workspace_analytics(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        // Create some test links with clicks
        $links = Link::factory()->count(3)->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'clicks' => 10,
        ]);

        $response = $this->actingAs($user)->get("/{$workspace->slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('dashboard/index')
                ->has('analytics')
                ->has('topLinks')
        );
    }

    public function test_dashboard_shows_correct_total_clicks(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        // Create links with specific click counts
        Link::factory()->create([
            'workspace_id' => $workspace->id,
            'clicks' => 100,
        ]);
        Link::factory()->create([
            'workspace_id' => $workspace->id,
            'clicks' => 50,
        ]);

        $response = $this->actingAs($user)->get("/{$workspace->slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->where('analytics.totalClicks', 150)
        );
    }

    public function test_dashboard_shows_correct_total_links(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        // Create 5 links
        Link::factory()->count(5)->create([
            'workspace_id' => $workspace->id,
        ]);

        $response = $this->actingAs($user)->get("/{$workspace->slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->where('analytics.totalLinks', 5)
        );
    }

    public function test_dashboard_filters_analytics_by_time_period(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        // Create links with different creation dates
        Link::factory()->create([
            'workspace_id' => $workspace->id,
            'created_at' => now()->subDays(2),
            'clicks' => 10,
        ]);
        Link::factory()->create([
            'workspace_id' => $workspace->id,
            'created_at' => now()->subDays(10),
            'clicks' => 20,
        ]);

        // Test 7-day filter
        $response = $this->actingAs($user)->get("/{$workspace->slug}?interval=7d");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->where('analytics.totalClicks', 10) // Only the recent link
        );
    }

    public function test_dashboard_shows_top_performing_links(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        // Create links with different click counts
        $topLink = Link::factory()->create([
            'workspace_id' => $workspace->id,
            'title' => 'Top Link',
            'clicks' => 100,
        ]);
        $mediumLink = Link::factory()->create([
            'workspace_id' => $workspace->id,
            'title' => 'Medium Link',
            'clicks' => 50,
        ]);
        $lowLink = Link::factory()->create([
            'workspace_id' => $workspace->id,
            'title' => 'Low Link',
            'clicks' => 10,
        ]);

        $response = $this->actingAs($user)->get("/{$workspace->slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('topLinks', 3)
                ->where('topLinks.0.title', 'Top Link')
                ->where('topLinks.0.clicks', 100)
        );
    }

    public function test_dashboard_only_shows_workspace_specific_data(): void
    {
        $user = User::factory()->create();
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();
        
        $workspace1->users()->attach($user, ['role' => 'member']);
        $workspace2->users()->attach($user, ['role' => 'member']);

        // Create links in different workspaces
        Link::factory()->create([
            'workspace_id' => $workspace1->id,
            'clicks' => 100,
        ]);
        Link::factory()->create([
            'workspace_id' => $workspace2->id,
            'clicks' => 200,
        ]);

        $response = $this->actingAs($user)->get("/{$workspace1->slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->where('analytics.totalClicks', 100) // Only workspace1 data
                ->where('analytics.totalLinks', 1)
        );
    }

    public function test_non_workspace_members_cannot_access_dashboard(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        $response = $this->actingAs($user)->get("/{$workspace->slug}");

        $response->assertRedirect('/dashboard');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $workspace = Workspace::factory()->create();

        $response = $this->get("/{$workspace->slug}");

        $response->assertRedirect('/login');
    }

    public function test_dashboard_handles_empty_workspace(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        $response = $this->actingAs($user)->get("/{$workspace->slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->where('analytics.totalClicks', 0)
                ->where('analytics.totalLinks', 0)
                ->where('topLinks', [])
        );
    }

    public function test_dashboard_analytics_api_endpoint(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        Link::factory()->count(3)->create([
            'workspace_id' => $workspace->id,
            'clicks' => 25,
        ]);

        $response = $this->actingAs($user)->get("/api/analytics?workspace_id={$workspace->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'totalClicks' => 75,
            'totalLinks' => 3,
        ]);
    }

    public function test_dashboard_supports_different_time_intervals(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        $intervals = ['24h', '7d', '30d', '90d', '12mo', 'all'];

        foreach ($intervals as $interval) {
            $response = $this->actingAs($user)->get("/{$workspace->slug}?interval={$interval}");
            
            $response->assertStatus(200);
            $response->assertInertia(fn ($page) => 
                $page->has('analytics')
                    ->where('currentInterval', $interval)
            );
        }
    }

    public function test_dashboard_shows_recent_activity(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        // Create recent links
        $recentLinks = Link::factory()->count(3)->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'created_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($user)->get("/{$workspace->slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('recentLinks', 3)
        );
    }

    public function test_dashboard_click_tracking_increments_correctly(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        $link = Link::factory()->create([
            'workspace_id' => $workspace->id,
            'domain' => 'dub.sh',
            'key' => 'test-link',
            'clicks' => 0,
        ]);

        // Simulate clicking the link
        $response = $this->get("/dub.sh/test-link");

        // Should redirect to the target URL
        $response->assertRedirect($link->url);

        // Check that clicks were incremented
        $link->refresh();
        $this->assertEquals(1, $link->clicks);
    }

    public function test_dashboard_shows_click_analytics_over_time(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        // Create links with clicks at different times
        Link::factory()->create([
            'workspace_id' => $workspace->id,
            'created_at' => now()->subDays(1),
            'clicks' => 10,
        ]);
        Link::factory()->create([
            'workspace_id' => $workspace->id,
            'created_at' => now()->subDays(3),
            'clicks' => 15,
        ]);

        $response = $this->actingAs($user)->get("/{$workspace->slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('analytics.clicksOverTime')
        );
    }
}
