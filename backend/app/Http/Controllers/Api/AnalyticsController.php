<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AnalyticsController extends Controller
{
    /**
     * Get analytics for a specific link.
     *
     * GET /api/links/{linkId}/analytics
     */
    public function linkAnalytics(Request $request, string $linkId): JsonResponse
    {
        $user = $request->user();

        // Find link with workspace access verification
        $link = Link::where('id', $linkId)
            ->whereHas('project.users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (!$link) {
            return response()->json([
                'error' => 'Link not found',
                'message' => 'Link not found or access denied',
            ], 404);
        }

        // Parse date range parameters
        $start = $request->query('start', Carbon::now()->subDays(30)->toDateString());
        $end = $request->query('end', Carbon::now()->toDateString());
        $interval = $request->query('interval', 'day'); // day, hour, month

        // Validate interval
        if (!in_array($interval, ['hour', 'day', 'month'])) {
            return response()->json([
                'error' => 'Invalid interval',
                'message' => 'Interval must be one of: hour, day, month',
            ], 400);
        }

        // Get analytics data (placeholder - would integrate with actual analytics service)
        $analytics = $this->getLinkAnalyticsData($link, $start, $end, $interval);

        return response()->json([
            'link' => [
                'id' => $link->id,
                'domain' => $link->domain,
                'key' => $link->key,
                'url' => $link->url,
                'title' => $link->title,
            ],
            'analytics' => $analytics,
            'period' => [
                'start' => $start,
                'end' => $end,
                'interval' => $interval,
            ],
        ]);
    }

    /**
     * Get dashboard analytics overview.
     *
     * GET /api/analytics/overview
     */
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get workspace
        $workspaceId = $request->query('workspace_id', $user->default_workspace);

        if (!$workspaceId) {
            return response()->json([
                'error' => 'No workspace specified',
                'message' => 'Please specify a workspace_id or set a default workspace',
            ], 400);
        }

        // Verify workspace access
        $workspace = Project::where('id', $workspaceId)
            ->whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (!$workspace) {
            return response()->json([
                'error' => 'Workspace not found',
                'message' => 'Workspace not found or access denied',
            ], 404);
        }

        // Parse date range parameters
        $start = $request->query('start', Carbon::now()->subDays(30)->toDateString());
        $end = $request->query('end', Carbon::now()->toDateString());

        // Get overview analytics
        $overview = $this->getOverviewAnalytics($workspaceId, $start, $end);

        return response()->json([
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ],
            'overview' => $overview,
            'period' => [
                'start' => $start,
                'end' => $end,
            ],
        ]);
    }

    /**
     * Get analytics data for a specific link.
     * This is a placeholder implementation - would integrate with actual analytics service.
     */
    private function getLinkAnalyticsData(Link $link, string $start, string $end, string $interval): array
    {
        // Placeholder implementation - in production this would query actual analytics data
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);

        $timeseries = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $timeseries[] = [
                'date' => $current->toDateString(),
                'clicks' => rand(0, 100), // Placeholder data
                'uniqueClicks' => rand(0, 80),
            ];

            $current->add(1, $interval);
        }

        return [
            'totalClicks' => $link->clicks ?? 0,
            'uniqueClicks' => $link->unique_clicks ?? 0,
            'timeseries' => $timeseries,
            'topCountries' => [
                ['country' => 'US', 'clicks' => rand(10, 50)],
                ['country' => 'GB', 'clicks' => rand(5, 30)],
                ['country' => 'DE', 'clicks' => rand(3, 20)],
            ],
            'topReferrers' => [
                ['referrer' => 'Direct', 'clicks' => rand(20, 60)],
                ['referrer' => 'Google', 'clicks' => rand(10, 40)],
                ['referrer' => 'Twitter', 'clicks' => rand(5, 25)],
            ],
            'topDevices' => [
                ['device' => 'Desktop', 'clicks' => rand(30, 70)],
                ['device' => 'Mobile', 'clicks' => rand(20, 50)],
                ['device' => 'Tablet', 'clicks' => rand(5, 15)],
            ],
        ];
    }

    /**
     * Get overview analytics for a workspace.
     * This is a placeholder implementation - would integrate with actual analytics service.
     */
    private function getOverviewAnalytics(string $workspaceId, string $start, string $end): array
    {
        // Get basic counts from database
        $totalLinks = Link::where('project_id', $workspaceId)->count();
        $totalClicks = Link::where('project_id', $workspaceId)->sum('clicks') ?? 0;

        // Placeholder implementation for time-series data
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);

        $timeseries = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $timeseries[] = [
                'date' => $current->toDateString(),
                'clicks' => rand(0, 500), // Placeholder data
                'links' => rand(0, 10),
            ];

            $current->addDay();
        }

        return [
            'totalLinks' => $totalLinks,
            'totalClicks' => $totalClicks,
            'clicksToday' => rand(0, 100), // Placeholder
            'linksToday' => rand(0, 5), // Placeholder
            'timeseries' => $timeseries,
            'topLinks' => Link::where('project_id', $workspaceId)
                ->orderBy('clicks', 'desc')
                ->limit(5)
                ->get(['id', 'domain', 'key', 'url', 'title', 'clicks']),
        ];
    }
}
