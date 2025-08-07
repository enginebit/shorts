<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\WorkspaceAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AnalyticsController - Analytics Enhanced
 *
 * Enhanced analytics method for Phase 3B implementation
 */
final class AnalyticsController extends Controller
{
    public function __construct(
        private readonly WorkspaceAuthService $workspaceAuthService
    ) {}

    /**
     * Show workspace analytics page
     */
    public function analytics(Request $request, Workspace $workspace): Response|RedirectResponse
    {
        $user = $request->user();

        // Verify user has access to this workspace
        if (! $workspace->isMember($user)) {
            return redirect('/dashboard')
                ->with('error', 'You do not have access to this workspace.');
        }

        // Set workspace context
        $this->workspaceAuthService->setWorkspaceContext($workspace);

        // Get analytics parameters
        $interval = $request->get('interval', '7d');
        
        // Get analytics data
        $analyticsData = $this->getWorkspaceAnalytics($workspace, $interval);

        return Inertia::render('dashboard/analytics', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ],
            'initialData' => $analyticsData,
            'interval' => $interval,
        ]);
    }

    /**
     * Get analytics data for workspace
     */
    private function getWorkspaceAnalytics(Workspace $workspace, string $interval): array
    {
        // Calculate date range based on interval
        $endDate = now();
        $startDate = match ($interval) {
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '12mo' => now()->subYear(),
            'all' => now()->subYears(10),
            default => now()->subDays(7),
        };

        // Get workspace links for the period
        $links = $workspace->links()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['user'])
            ->get();

        // Calculate metrics
        $totalClicks = $links->sum('clicks');
        $totalLinks = $links->count();
        $uniqueVisitors = $links->sum('unique_clicks');

        // Get previous period for comparison
        $prevStartDate = $startDate->copy()->subDays($endDate->diffInDays($startDate));
        $prevLinks = $workspace->links()
            ->whereBetween('created_at', [$prevStartDate, $startDate])
            ->get();
        
        $prevTotalClicks = $prevLinks->sum('clicks');
        $prevUniqueVisitors = $prevLinks->sum('unique_clicks');

        // Calculate changes
        $clicksChange = $prevTotalClicks > 0 ? (($totalClicks - $prevTotalClicks) / $prevTotalClicks) * 100 : 0;
        $visitorsChange = $prevUniqueVisitors > 0 ? (($uniqueVisitors - $prevUniqueVisitors) / $prevUniqueVisitors) * 100 : 0;

        // Mock data for now (will be replaced with real analytics service)
        return [
            'clicks' => [
                'total' => $totalClicks,
                'change' => round($clicksChange, 1),
                'timeseries' => [], // Will be implemented with real analytics service
            ],
            'visitors' => [
                'total' => $uniqueVisitors,
                'change' => round($visitorsChange, 1),
            ],
            'conversionRate' => [
                'rate' => $totalClicks > 0 ? ($uniqueVisitors / $totalClicks) * 100 : 0,
                'change' => 0, // Will be calculated with real data
            ],
            'topCountries' => [
                ['country' => 'United States', 'clicks' => (int)($totalClicks * 0.4)],
                ['country' => 'United Kingdom', 'clicks' => (int)($totalClicks * 0.2)],
                ['country' => 'Germany', 'clicks' => (int)($totalClicks * 0.15)],
                ['country' => 'France', 'clicks' => (int)($totalClicks * 0.1)],
                ['country' => 'Canada', 'clicks' => (int)($totalClicks * 0.08)],
            ],
            'topReferrers' => [
                ['referrer' => 'Direct', 'clicks' => (int)($totalClicks * 0.5)],
                ['referrer' => 'google.com', 'clicks' => (int)($totalClicks * 0.2)],
                ['referrer' => 'twitter.com', 'clicks' => (int)($totalClicks * 0.15)],
                ['referrer' => 'facebook.com', 'clicks' => (int)($totalClicks * 0.1)],
                ['referrer' => 'linkedin.com', 'clicks' => (int)($totalClicks * 0.05)],
            ],
            'topDevices' => [
                ['device' => 'Desktop', 'clicks' => (int)($totalClicks * 0.6)],
                ['device' => 'Mobile', 'clicks' => (int)($totalClicks * 0.35)],
                ['device' => 'Tablet', 'clicks' => (int)($totalClicks * 0.05)],
            ],
            'topLinks' => $links->sortByDesc('clicks')->take(10)->map(function ($link) {
                return [
                    'id' => $link->id,
                    'url' => $link->url,
                    'shortLink' => $link->short_link,
                    'clicks' => $link->clicks,
                    'title' => $link->title,
                ];
            })->values()->toArray(),
        ];
    }

    // ... other existing methods would be here
}
