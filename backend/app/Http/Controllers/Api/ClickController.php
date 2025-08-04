<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAnalyticsJob;
use App\Models\Link;
use App\Services\AnalyticsService;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Click Controller
 * 
 * Handles click tracking and redirection
 * following dub-main patterns from /apps/web/app/[domain]/[key]/route.ts
 */
final class ClickController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly WebhookService $webhook
    ) {}

    /**
     * Handle click tracking and redirect
     * GET /{domain}/{key}
     */
    public function handleClick(Request $request, string $domain, string $key): RedirectResponse
    {
        // Find the link
        $link = Link::where('domain', $domain)
            ->where('key', $key)
            ->whereNull('deleted_at')
            ->first();
        
        if (!$link) {
            // Redirect to 404 page or default URL
            return redirect(config('app.url') . '/404');
        }
        
        // Check if link is expired
        if ($link->expires_at && $link->expires_at->isPast()) {
            return redirect(config('app.url') . '/expired');
        }
        
        // Check password protection
        if ($link->password && !$this->verifyPassword($request, $link)) {
            return redirect(config('app.url') . '/password-required?link=' . $link->id);
        }
        
        // Check device targeting
        if (!$this->checkDeviceTargeting($request, $link)) {
            return redirect($link->url);
        }
        
        // Check geo targeting
        if (!$this->checkGeoTargeting($request, $link)) {
            return redirect($link->url);
        }
        
        try {
            // Record click analytics
            $clickId = $this->analytics->recordClick($request, $link);
            
            // Queue analytics processing job
            ProcessAnalyticsJob::dispatch($link->id, [
                'click_id' => $clickId,
                'country' => $request->header('cf-ipcountry', 'Unknown'),
                'city' => 'Unknown', // Would be populated by geolocation service
                'device' => $this->getDeviceType($request),
                'browser' => $this->getBrowser($request),
                'os' => $this->getOS($request),
                'referrer' => $request->header('referer', 'Direct'),
                'timestamp' => now()->toISOString(),
            ]);
            
            // Send webhook if enabled
            if ($link->project->webhook_enabled) {
                $this->webhook->sendWorkspaceWebhook('link.clicked', $link->project, [
                    'link' => [
                        'id' => $link->id,
                        'domain' => $link->domain,
                        'key' => $link->key,
                        'url' => $link->url,
                        'title' => $link->title,
                    ],
                    'click' => [
                        'id' => $clickId,
                        'timestamp' => now()->toISOString(),
                        'country' => $request->header('cf-ipcountry', 'Unknown'),
                        'device' => $this->getDeviceType($request),
                        'referrer' => $request->header('referer', 'Direct'),
                    ],
                ]);
            }
            
            Log::info('Click tracked successfully', [
                'link_id' => $link->id,
                'click_id' => $clickId,
                'destination' => $link->url,
            ]);
        } catch (\Exception $e) {
            Log::error('Click tracking failed', [
                'link_id' => $link->id,
                'error' => $e->getMessage(),
            ]);
            // Continue with redirect even if tracking fails
        }
        
        // Redirect to destination URL
        return redirect($link->url);
    }

    /**
     * Record lead conversion
     * POST /api/conversions/lead
     */
    public function recordLead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'click_id' => 'required|string',
            'customer_id' => 'sometimes|string',
            'customer_name' => 'sometimes|string',
            'customer_email' => 'sometimes|email',
            'customer_avatar' => 'sometimes|url',
            'metadata' => 'sometimes|array',
        ]);
        
        // Find the click to get link and project info
        // This would typically involve querying the analytics database
        // For now, we'll require additional parameters
        $linkId = $request->input('link_id');
        $projectId = $request->input('project_id');
        
        if (!$linkId || !$projectId) {
            return response()->json([
                'error' => 'Missing required parameters',
                'message' => 'link_id and project_id are required',
            ], 400);
        }
        
        $leadData = array_merge($validated, [
            'link_id' => $linkId,
            'project_id' => $projectId,
        ]);
        
        $success = $this->analytics->recordLead($leadData);
        
        if ($success) {
            // Send webhook if enabled
            $link = Link::find($linkId);
            if ($link && $link->project->webhook_enabled) {
                $this->webhook->sendWorkspaceWebhook('lead.created', $link->project, $leadData);
            }
            
            return response()->json([
                'success' => true,
                'lead_id' => $leadData['lead_id'] ?? null,
            ]);
        }
        
        return response()->json([
            'error' => 'Failed to record lead',
            'message' => 'Lead conversion could not be recorded',
        ], 500);
    }

    /**
     * Record sale conversion
     * POST /api/conversions/sale
     */
    public function recordSale(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'click_id' => 'required|string',
            'amount' => 'required|integer|min:0', // in cents
            'currency' => 'sometimes|string|size:3',
            'customer_id' => 'sometimes|string',
            'customer_name' => 'sometimes|string',
            'customer_email' => 'sometimes|email',
            'customer_avatar' => 'sometimes|url',
            'invoice_id' => 'sometimes|string',
            'metadata' => 'sometimes|array',
        ]);
        
        // Find the click to get link and project info
        $linkId = $request->input('link_id');
        $projectId = $request->input('project_id');
        
        if (!$linkId || !$projectId) {
            return response()->json([
                'error' => 'Missing required parameters',
                'message' => 'link_id and project_id are required',
            ], 400);
        }
        
        $saleData = array_merge($validated, [
            'link_id' => $linkId,
            'project_id' => $projectId,
            'currency' => $validated['currency'] ?? 'USD',
        ]);
        
        $success = $this->analytics->recordSale($saleData);
        
        if ($success) {
            // Send webhook if enabled
            $link = Link::find($linkId);
            if ($link && $link->project->webhook_enabled) {
                $this->webhook->sendWorkspaceWebhook('sale.created', $link->project, $saleData);
            }
            
            return response()->json([
                'success' => true,
                'sale_id' => $saleData['sale_id'] ?? null,
            ]);
        }
        
        return response()->json([
            'error' => 'Failed to record sale',
            'message' => 'Sale conversion could not be recorded',
        ], 500);
    }

    /**
     * Verify password protection
     */
    private function verifyPassword(Request $request, Link $link): bool
    {
        if (!$link->password) {
            return true;
        }
        
        $providedPassword = $request->query('password') ?? $request->header('x-password');
        return $providedPassword === $link->password;
    }

    /**
     * Check device targeting
     */
    private function checkDeviceTargeting(Request $request, Link $link): bool
    {
        if (!$link->ios_targeting && !$link->android_targeting) {
            return true;
        }
        
        $userAgent = $request->userAgent();
        $isIOS = str_contains(strtolower($userAgent), 'iphone') || str_contains(strtolower($userAgent), 'ipad');
        $isAndroid = str_contains(strtolower($userAgent), 'android');
        
        if ($link->ios_targeting && $isIOS) {
            return true;
        }
        
        if ($link->android_targeting && $isAndroid) {
            return true;
        }
        
        return !$link->ios_targeting && !$link->android_targeting;
    }

    /**
     * Check geo targeting
     */
    private function checkGeoTargeting(Request $request, Link $link): bool
    {
        if (!$link->geo_targeting) {
            return true;
        }
        
        $country = $request->header('cf-ipcountry');
        if (!$country) {
            return true; // Allow if we can't determine country
        }
        
        $targetCountries = json_decode($link->geo_targeting, true);
        return in_array($country, $targetCountries ?? []);
    }

    /**
     * Get device type from user agent
     */
    private function getDeviceType(Request $request): string
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        
        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'iphone') || str_contains($userAgent, 'android')) {
            return 'Mobile';
        }
        
        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'Tablet';
        }
        
        return 'Desktop';
    }

    /**
     * Get browser from user agent
     */
    private function getBrowser(Request $request): string
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        
        if (str_contains($userAgent, 'chrome')) return 'Chrome';
        if (str_contains($userAgent, 'firefox')) return 'Firefox';
        if (str_contains($userAgent, 'safari')) return 'Safari';
        if (str_contains($userAgent, 'edge')) return 'Edge';
        if (str_contains($userAgent, 'opera')) return 'Opera';
        
        return 'Unknown';
    }

    /**
     * Get OS from user agent
     */
    private function getOS(Request $request): string
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        
        if (str_contains($userAgent, 'windows')) return 'Windows';
        if (str_contains($userAgent, 'mac')) return 'macOS';
        if (str_contains($userAgent, 'linux')) return 'Linux';
        if (str_contains($userAgent, 'android')) return 'Android';
        if (str_contains($userAgent, 'ios') || str_contains($userAgent, 'iphone') || str_contains($userAgent, 'ipad')) return 'iOS';
        
        return 'Unknown';
    }
}
