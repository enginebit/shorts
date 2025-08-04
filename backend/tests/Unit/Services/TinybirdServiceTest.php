<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\TinybirdService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class TinybirdServiceTest extends TestCase
{
    private TinybirdService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.tinybird.api_key' => 'test-api-key',
            'services.tinybird.api_url' => 'https://api.tinybird.co',
        ]);

        $this->service = new TinybirdService();
    }

    public function test_record_click_success(): void
    {
        Http::fake([
            'api.tinybird.co/v0/events' => Http::response(['success' => true], 200),
        ]);

        $clickData = [
            'click_id' => 'test-click-id',
            'link_id' => 'test-link-id',
            'project_id' => 'test-project-id',
            'domain' => 'example.com',
            'key' => 'test-key',
            'url' => 'https://example.com/destination',
            'country' => 'US',
            'device' => 'Desktop',
            'browser' => 'Chrome',
        ];

        $result = $this->service->recordClick($clickData);

        $this->assertTrue($result);

        Http::assertSent(function ($request) use ($clickData) {
            return $request->url() === 'https://api.tinybird.co/v0/events' &&
                   $request->method() === 'POST' &&
                   $request->data()['name'] === 'dub_click_events' &&
                   $request->data()['data']['click_id'] === $clickData['click_id'];
        });
    }

    public function test_record_lead_success(): void
    {
        Http::fake([
            'api.tinybird.co/v0/events' => Http::response(['success' => true], 200),
        ]);

        $leadData = [
            'lead_id' => 'test-lead-id',
            'click_id' => 'test-click-id',
            'link_id' => 'test-link-id',
            'project_id' => 'test-project-id',
            'customer_email' => 'test@example.com',
        ];

        $result = $this->service->recordLead($leadData);

        $this->assertTrue($result);

        Http::assertSent(function ($request) use ($leadData) {
            return $request->url() === 'https://api.tinybird.co/v0/events' &&
                   $request->data()['name'] === 'dub_lead_events' &&
                   $request->data()['data']['lead_id'] === $leadData['lead_id'];
        });
    }

    public function test_record_sale_success(): void
    {
        Http::fake([
            'api.tinybird.co/v0/events' => Http::response(['success' => true], 200),
        ]);

        $saleData = [
            'sale_id' => 'test-sale-id',
            'click_id' => 'test-click-id',
            'link_id' => 'test-link-id',
            'project_id' => 'test-project-id',
            'amount' => 5000, // $50.00 in cents
            'currency' => 'USD',
        ];

        $result = $this->service->recordSale($saleData);

        $this->assertTrue($result);

        Http::assertSent(function ($request) use ($saleData) {
            return $request->url() === 'https://api.tinybird.co/v0/events' &&
                   $request->data()['name'] === 'dub_sale_events' &&
                   $request->data()['data']['amount'] === $saleData['amount'];
        });
    }

    public function test_query_analytics_success(): void
    {
        $mockData = [
            'data' => [
                ['date' => '2024-08-04', 'clicks' => 100, 'unique_clicks' => 80],
                ['date' => '2024-08-03', 'clicks' => 150, 'unique_clicks' => 120],
            ]
        ];

        Http::fake([
            'api.tinybird.co/v0/pipes/clicks_analytics.json*' => Http::response($mockData, 200),
        ]);

        $filters = [
            'project_id' => 'test-project-id',
            'start' => '2024-08-01',
            'end' => '2024-08-04',
        ];

        $result = $this->service->queryAnalytics('clicks_analytics', $filters);

        $this->assertEquals($mockData['data'], $result);

        Http::assertSent(function ($request) use ($filters) {
            return str_contains($request->url(), 'clicks_analytics.json') &&
                   $request->method() === 'GET' &&
                   str_contains($request->url(), 'project_id=test-project-id');
        });
    }

    public function test_batch_ingest_success(): void
    {
        Http::fake([
            'api.tinybird.co/v0/events' => Http::response(['success' => true], 200),
        ]);

        $events = [
            ['click_id' => 'click-1', 'link_id' => 'link-1'],
            ['click_id' => 'click-2', 'link_id' => 'link-2'],
        ];

        $result = $this->service->batchIngest('dub_click_events', $events);

        $this->assertTrue($result);

        Http::assertSent(function ($request) use ($events) {
            return $request->url() === 'https://api.tinybird.co/v0/events' &&
                   $request->data()['name'] === 'dub_click_events' &&
                   count($request->data()['data']) === count($events);
        });
    }

    public function test_test_connection_success(): void
    {
        Http::fake([
            'api.tinybird.co/v0/datasources' => Http::response(['datasources' => []], 200),
        ]);

        $result = $this->service->testConnection();

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.tinybird.co/v0/datasources' &&
                   $request->method() === 'GET' &&
                   $request->hasHeader('Authorization', 'Bearer test-api-key');
        });
    }

    public function test_record_click_failure(): void
    {
        Http::fake([
            'api.tinybird.co/v0/events' => Http::response(['error' => 'Bad request'], 400),
        ]);

        $clickData = ['click_id' => 'test-click-id'];

        $result = $this->service->recordClick($clickData);

        $this->assertFalse($result);
    }

    public function test_query_analytics_failure(): void
    {
        Http::fake([
            'api.tinybird.co/v0/pipes/clicks_analytics.json*' => Http::response(['error' => 'Not found'], 404),
        ]);

        $result = $this->service->queryAnalytics('clicks_analytics', []);

        $this->assertEquals([], $result);
    }

    public function test_test_connection_failure(): void
    {
        Http::fake([
            'api.tinybird.co/v0/datasources' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $result = $this->service->testConnection();

        $this->assertFalse($result);
    }
}
