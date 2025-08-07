<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tinybird Service
 *
 * Handles real-time analytics data ingestion and querying
 * following dub-main patterns from /lib/tinybird/client.ts
 */
final class TinybirdService
{
    private string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.tinybird.api_key');
        $this->baseUrl = config('services.tinybird.api_url', 'https://api.tinybird.co');
    }

    /**
     * Record click event to Tinybird
     * Following dub-main patterns from /lib/tinybird/record-click.ts
     */
    public function recordClick(array $data): bool
    {
        return $this->ingestEvent('dub_click_events', $data);
    }

    /**
     * Record lead event to Tinybird
     * Following dub-main patterns from /lib/tinybird/record-lead.ts
     */
    public function recordLead(array $data): bool
    {
        return $this->ingestEvent('dub_lead_events', $data);
    }

    /**
     * Record sale event to Tinybird
     * Following dub-main patterns from /lib/tinybird/record-sale.ts
     */
    public function recordSale(array $data): bool
    {
        return $this->ingestEvent('dub_sale_events', $data);
    }

    /**
     * Record link creation event to Tinybird
     * Following dub-main patterns from /lib/tinybird/record-link.ts
     */
    public function recordLink(array $data): bool
    {
        return $this->ingestEvent('dub_link_events', $data);
    }

    /**
     * Record webhook event to Tinybird
     * Following dub-main patterns from /lib/tinybird/record-webhook-event.ts
     */
    public function recordWebhookEvent(array $data): bool
    {
        return $this->ingestEvent('dub_webhook_events', $data);
    }

    /**
     * Query analytics data from Tinybird
     * Following dub-main patterns from /lib/analytics/get-analytics.ts
     */
    public function queryAnalytics(string $endpoint, array $params = []): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl.'/v0/pipes/'.$endpoint.'.json', $params);

            if (! $response->successful()) {
                Log::error('Tinybird query failed', [
                    'endpoint' => $endpoint,
                    'params' => $params,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return [];
            }

            $data = $response->json();

            return $data['data'] ?? [];
        } catch (Exception $e) {
            Log::error('Tinybird query exception', [
                'endpoint' => $endpoint,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get click analytics data
     */
    public function getClickAnalytics(array $filters): array
    {
        return $this->queryAnalytics('clicks_analytics', $filters);
    }

    /**
     * Get conversion analytics data
     */
    public function getConversionAnalytics(array $filters): array
    {
        return $this->queryAnalytics('conversion_analytics', $filters);
    }

    /**
     * Get time series analytics data
     */
    public function getTimeSeriesAnalytics(array $filters): array
    {
        return $this->queryAnalytics('timeseries_analytics', $filters);
    }

    /**
     * Get top countries analytics
     */
    public function getTopCountries(array $filters): array
    {
        return $this->queryAnalytics('top_countries', $filters);
    }

    /**
     * Get top referrers analytics
     */
    public function getTopReferrers(array $filters): array
    {
        return $this->queryAnalytics('top_referrers', $filters);
    }

    /**
     * Get device analytics
     */
    public function getDeviceAnalytics(array $filters): array
    {
        return $this->queryAnalytics('device_analytics', $filters);
    }

    /**
     * Generic event ingestion method
     */
    private function ingestEvent(string $datasource, array $data): bool
    {
        try {
            // Add timestamp if not provided
            if (! isset($data['timestamp'])) {
                $data['timestamp'] = now()->toISOString();
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/v0/events', [
                'name' => $datasource,
                'data' => $data,
            ]);

            if (! $response->successful()) {
                Log::error('Tinybird ingestion failed', [
                    'datasource' => $datasource,
                    'data' => $data,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return false;
            }

            Log::info('Tinybird event recorded', [
                'datasource' => $datasource,
                'data_keys' => array_keys($data),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Tinybird ingestion exception', [
                'datasource' => $datasource,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Test Tinybird connection
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])->get($this->baseUrl.'/v0/datasources');

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Tinybird connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Batch ingest multiple events
     */
    public function batchIngest(string $datasource, array $events): bool
    {
        try {
            // Add timestamps to events that don't have them
            $events = array_map(function ($event) {
                if (! isset($event['timestamp'])) {
                    $event['timestamp'] = now()->toISOString();
                }

                return $event;
            }, $events);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/v0/events', [
                'name' => $datasource,
                'data' => $events,
            ]);

            if (! $response->successful()) {
                Log::error('Tinybird batch ingestion failed', [
                    'datasource' => $datasource,
                    'event_count' => count($events),
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return false;
            }

            Log::info('Tinybird batch events recorded', [
                'datasource' => $datasource,
                'event_count' => count($events),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Tinybird batch ingestion exception', [
                'datasource' => $datasource,
                'event_count' => count($events),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
