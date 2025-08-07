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
 * DashboardController
 *
 * Based on dub-main dashboard patterns with workspace context
 *
 * Handles:
 * - General dashboard for users without workspace context
 * - Workspace-specific dashboards
 * - Workspace switching and redirection
 * - Links management pages
 * - Analytics pages
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia.js for page rendering
 * - Integrates with workspace middleware
 * - Maintains workspace context throughout navigation
 */
final class DashboardController extends Controller
{
    public function __construct(
        private readonly WorkspaceAuthService $workspaceAuthService
    ) {}

    /**
     * Show the general dashboard (fallback for users without workspaces)
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // If user has a default workspace, redirect to it
        $defaultWorkspace = $user->defaultWorkspace();
        if ($defaultWorkspace) {
            return redirect("/{$defaultWorkspace->slug}");
        }

        // If user has any workspaces, redirect to the first one
        $firstWorkspace = $user->workspaces()->first();
        if ($firstWorkspace) {
            return redirect("/{$firstWorkspace->slug}");
        }

        // Show general dashboard for users without workspaces
        return Inertia::render('dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'image' => $user->image,
            ],
        ]);
    }

    /**
     * Show workspace-specific dashboard
     */
    public function workspace(Request $request, Workspace $workspace): Response|RedirectResponse
    {
        $user = $request->user();

        // Verify user has access to this workspace
        if (! $workspace->isMember($user)) {
            return redirect('/dashboard')
                ->with('error', 'You do not have access to this workspace.');
        }

        // Set workspace context
        $this->workspaceAuthService->setWorkspaceContext($workspace);

        // Update user's default workspace if different
        if ($user->default_workspace !== $workspace->slug) {
            $user->update(['default_workspace' => $workspace->slug]);
        }

        // Get workspace statistics
        $stats = [
            'totalLinks' => $workspace->links()->count(),
            'totalClicks' => $workspace->total_clicks,
            'linksUsage' => $workspace->links_usage,
            'linksLimit' => $workspace->links_limit,
            'domainsCount' => $workspace->domains()->count(),
            'membersCount' => $workspace->users()->count(),
        ];

        // Get recent links
        $recentLinks = $workspace->links()
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($link) {
                return [
                    'id' => $link->id,
                    'url' => $link->url,
                    'shortLink' => $link->short_link,
                    'title' => $link->title,
                    'description' => $link->description,
                    'clicks' => $link->clicks,
                    'createdAt' => $link->created_at,
                    'user' => [
                        'name' => $link->user->name,
                        'email' => $link->user->email,
                    ],
                ];
            });

        return Inertia::render('dashboard/workspace', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'logo' => $workspace->logo,
                'plan' => $workspace->plan,
                'stats' => $stats,
                'recentLinks' => $recentLinks,
            ],
        ]);
    }

    /**
     * Show workspace links page
     */
    public function links(Request $request, Workspace $workspace): Response|RedirectResponse
    {
        $user = $request->user();

        // Verify user has access to this workspace
        if (! $workspace->isMember($user)) {
            return redirect('/dashboard')
                ->with('error', 'You do not have access to this workspace.');
        }

        // Set workspace context
        $this->workspaceAuthService->setWorkspaceContext($workspace);

        // Get search query and pagination
        $search = $request->get('search');
        $page = (int) $request->get('page', 1);
        $perPage = 20;

        // Build links query
        $linksQuery = $workspace->links()
            ->with(['user'])
            ->orderBy('created_at', 'desc');

        // Apply search filter
        if ($search) {
            $linksQuery->where(function ($query) use ($search) {
                $query->where('url', 'like', "%{$search}%")
                    ->orWhere('short_link', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Paginate results
        $links = $linksQuery->paginate($perPage, ['*'], 'page', $page);

        // Format links data
        $formattedLinks = $links->items();
        foreach ($formattedLinks as &$link) {
            $link = [
                'id' => $link->id,
                'url' => $link->url,
                'shortLink' => $link->short_link,
                'domain' => $link->domain,
                'key' => $link->key,
                'title' => $link->title,
                'description' => $link->description,
                'image' => $link->image,
                'clicks' => $link->clicks,
                'uniqueClicks' => $link->unique_clicks,
                'lastClicked' => $link->last_clicked,
                'createdAt' => $link->created_at,
                'user' => [
                    'name' => $link->user->name,
                    'email' => $link->user->email,
                    'image' => $link->user->image,
                ],
            ];
        }

        return Inertia::render('dashboard/links', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ],
            'links' => $formattedLinks,
            'totalLinks' => $links->total(),
            'currentPage' => $links->currentPage(),
            'totalPages' => $links->lastPage(),
            'search' => $search,
        ]);
    }

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
        ]);
    }

    /**
     * Show workspace settings page
     */
    public function settings(Request $request, Workspace $workspace): Response|RedirectResponse
    {
        $user = $request->user();

        // Verify user has access to this workspace
        if (! $workspace->isMember($user)) {
            return redirect('/dashboard')
                ->with('error', 'You do not have access to this workspace.');
        }

        // Set workspace context
        $this->workspaceAuthService->setWorkspaceContext($workspace);

        // Get workspace invites
        $invites = $workspace->invites()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($invite) {
                return [
                    'id' => $invite->id,
                    'email' => $invite->email,
                    'role' => $invite->role,
                    'expiresAt' => $invite->expires_at,
                    'createdAt' => $invite->created_at,
                ];
            });

        return Inertia::render('dashboard/settings', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'logo' => $workspace->logo,
                'plan' => $workspace->plan,
                'invites' => $invites,
            ],
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
        $prevStartDate = $startDate->copy()->sub($endDate->diffInDays($startDate), 'days');
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
}
