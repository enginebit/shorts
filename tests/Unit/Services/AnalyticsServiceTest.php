<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Link;
use App\Models\Project;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\TinybirdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

final class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsService $service;

    private TinybirdService $tinybirdMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tinybirdMock = Mockery::mock(TinybirdService::class);
        $this->service = new AnalyticsService($this->tinybirdMock);
    }

    public function test_record_click_with_comprehensive_data(): void
    {
        // Create test data
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user);

        $link = Link::factory()->create([
            'project_id' => $project->id,
            'domain' => 'example.com',
            'key' => 'test-key',
            'url' => 'https://destination.com',
        ]);

        // Mock HTTP request for geolocation
        Http::fake([
            'ipapi.co/*' => Http::response([
                'country_code' => 'US',
                'city' => 'San Francisco',
                'region' => 'California',
                'latitude' => 37.7749,
                'longitude' => -122.4194,
            ], 200),
        ]);

        // Create mock request
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'HTTP_REFERER' => 'https://google.com/search',
        ]);

        // Mock Tinybird service
        $this->tinybirdMock
            ->shouldReceive('recordClick')
            ->once()
            ->with(Mockery::on(function ($data) use ($link) {
                return $data['link_id'] === $link->id &&
                       $data['domain'] === $link->domain &&
                       $data['key'] === $link->key &&
                       $data['country'] === 'US' &&
                       $data['city'] === 'San Francisco' &&
                       $data['device'] === 'Desktop' &&
                       $data['browser'] === 'Chrome' &&
                       $data['os'] === 'macOS' &&
                       $data['referer'] === 'Google' &&
                       $data['bot'] === false;
            }))
            ->andReturn(true);

        $clickId = $this->service->recordClick($request, $link);

        $this->assertIsString($clickId);
        $this->assertNotEmpty($clickId);

        // Verify link stats were updated
        $link->refresh();
        $this->assertEquals(1, $link->clicks);
        $this->assertEquals(1, $link->unique_clicks);
        $this->assertNotNull($link->last_clicked);
    }

    public function test_record_click_detects_bot(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user);

        $link = Link::factory()->create([
            'project_id' => $project->id,
        ]);

        // Create request with bot user agent
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'Googlebot/2.1 (+http://www.google.com/bot.html)',
        ]);

        $this->tinybirdMock
            ->shouldReceive('recordClick')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['bot'] === true;
            }))
            ->andReturn(true);

        $clickId = $this->service->recordClick($request, $link);

        $this->assertIsString($clickId);

        // Bot clicks should not increment unique_clicks
        $link->refresh();
        $this->assertEquals(1, $link->clicks);
        $this->assertEquals(0, $link->unique_clicks); // Bot clicks don't count as unique
    }

    public function test_record_click_detects_mobile_device(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user);

        $link = Link::factory()->create([
            'project_id' => $project->id,
        ]);

        // Create request with mobile user agent
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.2 Mobile/15E148 Safari/604.1',
        ]);

        $this->tinybirdMock
            ->shouldReceive('recordClick')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['device'] === 'Mobile' &&
                       $data['os'] === 'iOS' &&
                       $data['browser'] === 'Safari';
            }))
            ->andReturn(true);

        $clickId = $this->service->recordClick($request, $link);

        $this->assertIsString($clickId);
    }

    public function test_record_lead_success(): void
    {
        $leadData = [
            'click_id' => 'test-click-id',
            'link_id' => 'test-link-id',
            'project_id' => 'test-project-id',
            'customer_email' => 'test@example.com',
            'customer_name' => 'Test Customer',
        ];

        $this->tinybirdMock
            ->shouldReceive('recordLead')
            ->once()
            ->with(Mockery::on(function ($data) use ($leadData) {
                return $data['click_id'] === $leadData['click_id'] &&
                       $data['customer_email'] === $leadData['customer_email'] &&
                       isset($data['lead_id']);
            }))
            ->andReturn(true);

        $result = $this->service->recordLead($leadData);

        $this->assertTrue($result);
    }

    public function test_record_sale_success(): void
    {
        $saleData = [
            'click_id' => 'test-click-id',
            'link_id' => 'test-link-id',
            'project_id' => 'test-project-id',
            'amount' => 5000, // $50.00 in cents
            'currency' => 'USD',
            'customer_email' => 'test@example.com',
        ];

        $this->tinybirdMock
            ->shouldReceive('recordSale')
            ->once()
            ->with(Mockery::on(function ($data) use ($saleData) {
                return $data['click_id'] === $saleData['click_id'] &&
                       $data['amount'] === $saleData['amount'] &&
                       $data['currency'] === $saleData['currency'] &&
                       isset($data['sale_id']);
            }))
            ->andReturn(true);

        $result = $this->service->recordSale($saleData);

        $this->assertTrue($result);
    }

    public function test_geolocation_fallback_for_localhost(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user);

        $link = Link::factory()->create([
            'project_id' => $project->id,
        ]);

        // Create request from localhost
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
        ]);

        $this->tinybirdMock
            ->shouldReceive('recordClick')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['country'] === 'US' &&
                       $data['city'] === 'San Francisco' &&
                       $data['latitude'] === 37.7749 &&
                       $data['longitude'] === -122.4194;
            }))
            ->andReturn(true);

        $clickId = $this->service->recordClick($request, $link);

        $this->assertIsString($clickId);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
