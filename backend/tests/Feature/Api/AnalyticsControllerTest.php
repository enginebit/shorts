<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Link;
use App\Models\Project;
use App\Models\User;
use App\Services\TinybirdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

final class AnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;
    private Link $link;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
        $this->project->users()->attach($this->user);

        $this->link = Link::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'key' => 'test-key',
            'url' => 'https://destination.com',
            'title' => 'Test Link',
            'clicks' => 100,
            'unique_clicks' => 80,
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_analytics_overview_success(): void
    {
        // Mock Tinybird service
        $tinybirdMock = Mockery::mock(TinybirdService::class);
        $this->app->instance(TinybirdService::class, $tinybirdMock);

        $tinybirdMock->shouldReceive('getClickAnalytics')->andReturn([
            ['clicks' => 100, 'unique_clicks' => 80],
            ['clicks' => 50, 'unique_clicks' => 40],
        ]);

        $tinybirdMock->shouldReceive('queryAnalytics')
            ->with('leads_overview', Mockery::any())
            ->andReturn([['leads' => 10]]);

        $tinybirdMock->shouldReceive('queryAnalytics')
            ->with('sales_overview', Mockery::any())
            ->andReturn([['sales' => 5, 'amount' => 25000]]);

        $tinybirdMock->shouldReceive('queryAnalytics')
            ->with('top_links', Mockery::any())
            ->andReturn([]);

        $tinybirdMock->shouldReceive('getTopCountries')->andReturn([]);
        $tinybirdMock->shouldReceive('getTopReferrers')->andReturn([]);
        $tinybirdMock->shouldReceive('getDeviceAnalytics')->andReturn([]);

        $response = $this->getJson('/api/analytics/overview?workspaceId=' . $this->project->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'clicks' => ['total', 'unique', 'change'],
                'leads' => ['total', 'change'],
                'sales' => ['total', 'amount', 'change'],
                'topLinks',
                'topCountries',
                'topReferrers',
                'devices',
            ]);
    }

    public function test_link_analytics_success(): void
    {
        $tinybirdMock = Mockery::mock(TinybirdService::class);
        $this->app->instance(TinybirdService::class, $tinybirdMock);

        $mockTimeSeriesData = [
            ['date' => '2024-08-04', 'clicks' => 50, 'unique_clicks' => 40],
            ['date' => '2024-08-03', 'clicks' => 30, 'unique_clicks' => 25],
        ];

        $tinybirdMock->shouldReceive('getTimeSeriesAnalytics')
            ->andReturn($mockTimeSeriesData);

        $response = $this->getJson('/api/links/' . $this->link->id . '/analytics?interval=7d&groupBy=timeseries');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'link' => [
                    'id',
                    'domain',
                    'key',
                    'url',
                    'title',
                    'clicks',
                    'unique_clicks',
                    'last_clicked',
                ],
            ])
            ->assertJson([
                'data' => $mockTimeSeriesData,
                'link' => [
                    'id' => $this->link->id,
                    'domain' => $this->link->domain,
                    'key' => $this->link->key,
                    'clicks' => 100,
                    'unique_clicks' => 80,
                ],
            ]);
    }

    public function test_link_analytics_countries_groupby(): void
    {
        $tinybirdMock = Mockery::mock(TinybirdService::class);
        $this->app->instance(TinybirdService::class, $tinybirdMock);

        $mockCountriesData = [
            ['country' => 'US', 'clicks' => 60],
            ['country' => 'CA', 'clicks' => 20],
        ];

        $tinybirdMock->shouldReceive('getTopCountries')
            ->andReturn($mockCountriesData);

        $response = $this->getJson('/api/links/' . $this->link->id . '/analytics?groupBy=countries');

        $response->assertStatus(200)
            ->assertJson([
                'data' => $mockCountriesData,
            ]);
    }

    public function test_timeseries_analytics_success(): void
    {
        $tinybirdMock = Mockery::mock(TinybirdService::class);
        $this->app->instance(TinybirdService::class, $tinybirdMock);

        $mockData = [
            ['timestamp' => '2024-08-04T00:00:00Z', 'clicks' => 25],
            ['timestamp' => '2024-08-04T01:00:00Z', 'clicks' => 30],
        ];

        $tinybirdMock->shouldReceive('getTimeSeriesAnalytics')
            ->andReturn($mockData);

        $response = $this->getJson('/api/analytics/timeseries?workspaceId=' . $this->project->id . '&interval=24h');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'filters',
            ])
            ->assertJson([
                'data' => $mockData,
            ]);
    }

    public function test_conversions_analytics_success(): void
    {
        $tinybirdMock = Mockery::mock(TinybirdService::class);
        $this->app->instance(TinybirdService::class, $tinybirdMock);

        $mockConversions = [
            ['date' => '2024-08-04', 'leads' => 5, 'sales' => 2, 'revenue' => 10000],
            ['date' => '2024-08-03', 'leads' => 3, 'sales' => 1, 'revenue' => 5000],
        ];

        $tinybirdMock->shouldReceive('getConversionAnalytics')
            ->andReturn($mockConversions);

        $response = $this->getJson('/api/analytics/conversions?workspaceId=' . $this->project->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'filters',
            ])
            ->assertJson([
                'data' => $mockConversions,
            ]);
    }

    public function test_analytics_overview_unauthorized_workspace(): void
    {
        $otherProject = Project::factory()->create();

        $response = $this->getJson('/api/analytics/overview?workspaceId=' . $otherProject->id);

        $response->assertStatus(404);
    }

    public function test_link_analytics_unauthorized_link(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create();
        $otherProject->users()->attach($otherUser);

        $otherLink = Link::factory()->create([
            'project_id' => $otherProject->id,
        ]);

        $response = $this->getJson('/api/links/' . $otherLink->id . '/analytics');

        $response->assertStatus(404);
    }

    public function test_analytics_requires_authentication(): void
    {
        auth()->logout();

        $response = $this->getJson('/api/analytics/overview?workspaceId=' . $this->project->id);

        $response->assertStatus(401);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
