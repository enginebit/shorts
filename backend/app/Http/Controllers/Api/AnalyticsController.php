<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Models\Project;
use App\Services\TinybirdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Analytics API Controller
 * 
 * Handles analytics data retrieval and reporting
 * following dub-main patterns from /apps/web/app/api/analytics/
 */
final class AnalyticsController extends Controller
{
    public function __construct(
        private readonly TinybirdService $tinybird
    ) {}

    /**
     * Get analytics overview for workspace
     * GET /api/analytics/overview
     */
    public function overview(Request $request): JsonResponse
    {
        $user = Auth::user();
        $workspaceId = $request->query('workspaceId');
        
        // Verify workspace access
        $project = Project::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($workspaceId);
        
        $interval = $request->query('interval', '7d');
        $timezone = $request->query('timezone', 'UTC');
        
        // Get analytics data from Tinybird
        $filters = [
            'project_id' => $project->id,
            'interval' => $interval,
            'timezone' => $timezone,
            'start' => $this->getStartDate($interval),
            'end' => now()->toISOString(),
        ];
        
        $analytics = [
            'clicks' => $this->getClicksOverview($filters),
            'leads' => $this->getLeadsOverview($filters),
            'sales' => $this->getSalesOverview($filters),
            'topLinks' => $this->getTopLinks($filters),
            'topCountries' => $this->getTopCountries($filters),
            'topReferrers' => $this->getTopReferrers($filters),
            'devices' => $this->getDeviceBreakdown($filters),
        ];
        
        return response()->json($analytics);
    }

    /**
     * Get analytics for specific link
     * GET /api/links/{linkId}/analytics
     */
    public function linkAnalytics(Request $request, string $linkId): JsonResponse
    {
        $user = Auth::user();
        
        // Find link with workspace access verification
        $link = Link::where('id', $linkId)
            ->whereHas('project.users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['project'])
            ->firstOrFail();
        
        $interval = $request->query('interval', '7d');
        $groupBy = $request->query('groupBy', 'timeseries');
        $timezone = $request->query('timezone', 'UTC');
        
        $filters = [
            'link_id' => $link->id,
            'project_id' => $link->project_id,
            'interval' => $interval,
            'group_by' => $groupBy,
            'timezone' => $timezone,
            'start' => $this->getStartDate($interval),
            'end' => now()->toISOString(),
        ];
        
        $analytics = match ($groupBy) {
            'timeseries' => $this->getTimeSeriesData($filters),
            'countries' => $this->tinybird->getTopCountries($filters),
            'cities' => $this->getTopCities($filters),
            'referrers' => $this->tinybird->getTopReferrers($filters),
            'devices' => $this->tinybird->getDeviceAnalytics($filters),
            'browsers' => $this->getBrowserAnalytics($filters),
            'os' => $this->getOSAnalytics($filters),
            default => $this->getTimeSeriesData($filters),
        };
        
        return response()->json([
            'data' => $analytics,
            'link' => [
                'id' => $link->id,
                'domain' => $link->domain,
                'key' => $link->key,
                'url' => $link->url,
                'title' => $link->title,
                'clicks' => $link->clicks,
                'unique_clicks' => $link->unique_clicks,
                'last_clicked' => $link->last_clicked,
            ],
        ]);
    }

    /**
     * Get time series analytics data
     * GET /api/analytics/timeseries
     */
    public function timeseries(Request $request): JsonResponse
    {
        $user = Auth::user();
        $workspaceId = $request->query('workspaceId');
        $linkId = $request->query('linkId');
        
        // Verify workspace access if workspaceId provided
        if ($workspaceId) {
            Project::whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->findOrFail($workspaceId);
        }
        
        $filters = [
            'project_id' => $workspaceId,
            'link_id' => $linkId,
            'interval' => $request->query('interval', '7d'),
            'timezone' => $request->query('timezone', 'UTC'),
            'start' => $request->query('start', $this->getStartDate($request->query('interval', '7d'))),
            'end' => $request->query('end', now()->toISOString()),
            'country' => $request->query('country'),
            'device' => $request->query('device'),
            'browser' => $request->query('browser'),
            'os' => $request->query('os'),
            'referer' => $request->query('referer'),
        ];
        
        $data = $this->tinybird->getTimeSeriesAnalytics($filters);
        
        return response()->json([
            'data' => $data,
            'filters' => $filters,
        ]);
    }

    /**
     * Get conversion analytics
     * GET /api/analytics/conversions
     */
    public function conversions(Request $request): JsonResponse
    {
        $user = Auth::user();
        $workspaceId = $request->query('workspaceId');
        
        // Verify workspace access
        if ($workspaceId) {
            Project::whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->findOrFail($workspaceId);
        }
        
        $filters = [
            'project_id' => $workspaceId,
            'link_id' => $request->query('linkId'),
            'interval' => $request->query('interval', '7d'),
            'start' => $request->query('start', $this->getStartDate($request->query('interval', '7d'))),
            'end' => $request->query('end', now()->toISOString()),
        ];
        
        $conversions = $this->tinybird->getConversionAnalytics($filters);
        
        return response()->json([
            'data' => $conversions,
            'filters' => $filters,
        ]);
    }

    /**
     * Get clicks overview data
     */
    private function getClicksOverview(array $filters): array
    {
        $data = $this->tinybird->getClickAnalytics($filters);
        
        return [
            'total' => array_sum(array_column($data, 'clicks')),
            'unique' => array_sum(array_column($data, 'unique_clicks')),
            'change' => 0, // TODO: Calculate change from previous period
        ];
    }

    /**
     * Get leads overview data
     */
    private function getLeadsOverview(array $filters): array
    {
        $data = $this->tinybird->queryAnalytics('leads_overview', $filters);
        
        return [
            'total' => array_sum(array_column($data, 'leads')),
            'change' => 0, // TODO: Calculate change from previous period
        ];
    }

    /**
     * Get sales overview data
     */
    private function getSalesOverview(array $filters): array
    {
        $data = $this->tinybird->queryAnalytics('sales_overview', $filters);
        
        return [
            'total' => array_sum(array_column($data, 'sales')),
            'amount' => array_sum(array_column($data, 'amount')),
            'change' => 0, // TODO: Calculate change from previous period
        ];
    }

    /**
     * Get top links data
     */
    private function getTopLinks(array $filters): array
    {
        return $this->tinybird->queryAnalytics('top_links', array_merge($filters, ['limit' => 10]));
    }

    /**
     * Get top countries data
     */
    private function getTopCountries(array $filters): array
    {
        return $this->tinybird->getTopCountries(array_merge($filters, ['limit' => 10]));
    }

    /**
     * Get top referrers data
     */
    private function getTopReferrers(array $filters): array
    {
        return $this->tinybird->getTopReferrers(array_merge($filters, ['limit' => 10]));
    }

    /**
     * Get device breakdown data
     */
    private function getDeviceBreakdown(array $filters): array
    {
        return $this->tinybird->getDeviceAnalytics($filters);
    }

    /**
     * Get time series data
     */
    private function getTimeSeriesData(array $filters): array
    {
        return $this->tinybird->getTimeSeriesAnalytics($filters);
    }

    /**
     * Get top cities data
     */
    private function getTopCities(array $filters): array
    {
        return $this->tinybird->queryAnalytics('top_cities', array_merge($filters, ['limit' => 10]));
    }

    /**
     * Get browser analytics
     */
    private function getBrowserAnalytics(array $filters): array
    {
        return $this->tinybird->queryAnalytics('browser_analytics', $filters);
    }

    /**
     * Get OS analytics
     */
    private function getOSAnalytics(array $filters): array
    {
        return $this->tinybird->queryAnalytics('os_analytics', $filters);
    }

    /**
     * Get start date based on interval
     */
    private function getStartDate(string $interval): string
    {
        return match ($interval) {
            '24h' => now()->subDay()->toISOString(),
            '7d' => now()->subWeek()->toISOString(),
            '30d' => now()->subMonth()->toISOString(),
            '90d' => now()->subMonths(3)->toISOString(),
            '1y' => now()->subYear()->toISOString(),
            'mtd' => now()->startOfMonth()->toISOString(),
            'qtd' => now()->startOfQuarter()->toISOString(),
            'ytd' => now()->startOfYear()->toISOString(),
            'all' => now()->subYears(10)->toISOString(),
            default => now()->subWeek()->toISOString(),
        };
    }
}
