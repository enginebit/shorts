<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Link;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

/**
 * Analytics Service
 * 
 * Handles comprehensive click tracking, geolocation, device detection,
 * and analytics data processing following dub-main patterns
 */
final class AnalyticsService
{
    public function __construct(
        private readonly TinybirdService $tinybird
    ) {}

    /**
     * Record click event with comprehensive analytics data
     * Following dub-main patterns from /lib/tinybird/record-click.ts
     */
    public function recordClick(Request $request, Link $link, array $options = []): string
    {
        $clickId = Str::uuid()->toString();
        $timestamp = now();
        
        // Get IP address
        $ip = $this->getClientIp($request);
        
        // Get geolocation data
        $geoData = $this->getGeolocationData($ip);
        
        // Get device and browser information
        $deviceData = $this->getDeviceData($request);
        
        // Get referrer information
        $referrerData = $this->getReferrerData($request);
        
        // Check for bot detection
        $isBot = $this->detectBot($request);
        
        // Check for QR code scan
        $isQr = $this->detectQr($request);
        
        // Prepare click event data
        $clickData = [
            'click_id' => $clickId,
            'link_id' => $link->id,
            'project_id' => $link->project_id,
            'domain' => $link->domain,
            'key' => $link->key,
            'url' => $link->url,
            'timestamp' => $timestamp->toISOString(),
            
            // Geographic data
            'country' => $geoData['country'] ?? 'Unknown',
            'city' => $geoData['city'] ?? 'Unknown',
            'region' => $geoData['region'] ?? 'Unknown',
            'latitude' => $geoData['latitude'] ?? null,
            'longitude' => $geoData['longitude'] ?? null,
            
            // Device data
            'device' => $deviceData['device'],
            'device_vendor' => $deviceData['device_vendor'],
            'device_model' => $deviceData['device_model'],
            'browser' => $deviceData['browser'],
            'browser_version' => $deviceData['browser_version'],
            'engine' => $deviceData['engine'],
            'engine_version' => $deviceData['engine_version'],
            'os' => $deviceData['os'],
            'os_version' => $deviceData['os_version'],
            'cpu_architecture' => $deviceData['cpu_architecture'],
            
            // Referrer data
            'referer' => $referrerData['referer'],
            'referer_url' => $referrerData['referer_url'],
            
            // Detection flags
            'bot' => $isBot,
            'qr' => $isQr,
            
            // Additional metadata
            'ip' => $ip,
            'user_agent' => $request->userAgent(),
            'trigger' => $options['trigger'] ?? 'link',
        ];
        
        // Record to Tinybird
        $this->tinybird->recordClick($clickData);
        
        // Update link statistics
        $this->updateLinkStats($link, $clickData);
        
        Log::info('Click recorded', [
            'click_id' => $clickId,
            'link_id' => $link->id,
            'country' => $geoData['country'] ?? 'Unknown',
            'device' => $deviceData['device'],
            'bot' => $isBot,
        ]);
        
        return $clickId;
    }

    /**
     * Record lead conversion event
     * Following dub-main patterns from /lib/tinybird/record-lead.ts
     */
    public function recordLead(array $data): bool
    {
        $leadData = [
            'lead_id' => $data['lead_id'] ?? Str::uuid()->toString(),
            'click_id' => $data['click_id'],
            'link_id' => $data['link_id'],
            'project_id' => $data['project_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'customer_avatar' => $data['customer_avatar'] ?? null,
            'timestamp' => $data['timestamp'] ?? now()->toISOString(),
            'metadata' => $data['metadata'] ?? null,
        ];
        
        return $this->tinybird->recordLead($leadData);
    }

    /**
     * Record sale conversion event
     * Following dub-main patterns from /lib/tinybird/record-sale.ts
     */
    public function recordSale(array $data): bool
    {
        $saleData = [
            'sale_id' => $data['sale_id'] ?? Str::uuid()->toString(),
            'click_id' => $data['click_id'],
            'link_id' => $data['link_id'],
            'project_id' => $data['project_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'customer_avatar' => $data['customer_avatar'] ?? null,
            'amount' => $data['amount'], // in cents
            'currency' => $data['currency'] ?? 'USD',
            'invoice_id' => $data['invoice_id'] ?? null,
            'timestamp' => $data['timestamp'] ?? now()->toISOString(),
            'metadata' => $data['metadata'] ?? null,
        ];
        
        return $this->tinybird->recordSale($saleData);
    }

    /**
     * Get client IP address
     */
    private function getClientIp(Request $request): string
    {
        // Check for IP from various headers (Cloudflare, etc.)
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipHeaders as $header) {
            $ip = $request->server($header);
            if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
        
        return $request->ip() ?? '127.0.0.1';
    }

    /**
     * Get geolocation data from IP address
     */
    private function getGeolocationData(string $ip): array
    {
        // For localhost/development
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return [
                'country' => 'US',
                'city' => 'San Francisco',
                'region' => 'California',
                'latitude' => 37.7749,
                'longitude' => -122.4194,
            ];
        }
        
        try {
            // Use ipapi.co for geolocation (free tier available)
            $response = Http::timeout(5)->get("https://ipapi.co/{$ip}/json/");
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'country' => $data['country_code'] ?? 'Unknown',
                    'city' => $data['city'] ?? 'Unknown',
                    'region' => $data['region'] ?? 'Unknown',
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Geolocation lookup failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }
        
        return [
            'country' => 'Unknown',
            'city' => 'Unknown',
            'region' => 'Unknown',
            'latitude' => null,
            'longitude' => null,
        ];
    }

    /**
     * Get device and browser information
     */
    private function getDeviceData(Request $request): array
    {
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());
        
        return [
            'device' => $agent->device() ?: ($agent->isMobile() ? 'Mobile' : ($agent->isTablet() ? 'Tablet' : 'Desktop')),
            'device_vendor' => $agent->device() ? $agent->device() : 'Unknown',
            'device_model' => 'Unknown', // Agent doesn't provide model
            'browser' => $agent->browser(),
            'browser_version' => $agent->version($agent->browser()),
            'engine' => 'Unknown', // Would need additional parsing
            'engine_version' => 'Unknown',
            'os' => $agent->platform(),
            'os_version' => $agent->version($agent->platform()),
            'cpu_architecture' => 'Unknown', // Would need additional detection
        ];
    }

    /**
     * Get referrer information
     */
    private function getReferrerData(Request $request): array
    {
        $referer = $request->header('referer');
        
        if (!$referer) {
            return [
                'referer' => 'Direct',
                'referer_url' => null,
            ];
        }
        
        $parsedUrl = parse_url($referer);
        $domain = $parsedUrl['host'] ?? 'Unknown';
        
        // Categorize common referrers
        $knownReferrers = [
            'google.com' => 'Google',
            'facebook.com' => 'Facebook',
            'twitter.com' => 'Twitter',
            'linkedin.com' => 'LinkedIn',
            'instagram.com' => 'Instagram',
            'youtube.com' => 'YouTube',
            'reddit.com' => 'Reddit',
            'pinterest.com' => 'Pinterest',
        ];
        
        foreach ($knownReferrers as $pattern => $name) {
            if (str_contains($domain, $pattern)) {
                return [
                    'referer' => $name,
                    'referer_url' => $referer,
                ];
            }
        }
        
        return [
            'referer' => $domain,
            'referer_url' => $referer,
        ];
    }

    /**
     * Detect if request is from a bot
     */
    private function detectBot(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        
        $botPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python',
            'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
            'yandexbot', 'facebookexternalhit', 'twitterbot', 'linkedinbot',
            'whatsapp', 'telegram', 'discord', 'slack'
        ];
        
        foreach ($botPatterns as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Detect if request is from QR code scan
     */
    private function detectQr(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        
        // QR code scanners often have specific patterns
        $qrPatterns = [
            'qr', 'scanner', 'camera', 'vision'
        ];
        
        foreach ($qrPatterns as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                return true;
            }
        }
        
        // Check for mobile devices with camera-related headers
        if ($request->header('x-requested-with') === 'com.android.camera') {
            return true;
        }
        
        return false;
    }

    /**
     * Update link statistics
     */
    private function updateLinkStats(Link $link, array $clickData): void
    {
        $link->increment('clicks');
        $link->update(['last_clicked' => now()]);
        
        // Update unique clicks if this is a unique visitor
        // This is a simplified implementation - in production you'd want
        // more sophisticated unique visitor detection
        if (!$clickData['bot']) {
            $link->increment('unique_clicks');
        }
    }
}
