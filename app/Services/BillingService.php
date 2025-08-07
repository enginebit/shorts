<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendLimitEmailJob;
use App\Models\Project;
use Illuminate\Support\Facades\Log;

/**
 * Billing Service
 *
 * Handles usage tracking, limits enforcement, and billing calculations
 * following dub-main patterns from workspace usage tracking
 */
final class BillingService
{
    /**
     * Plan limits configuration following dub-main patterns
     */
    private const PLAN_LIMITS = [
        'free' => [
            'links' => 25,
            'clicks' => 1000,
            'domains' => 3,
            'users' => 1,
            'tags' => 5,
            'folders' => 0,
            'ai' => 10,
            'payouts' => 0,
        ],
        'starter' => [
            'links' => 1000,
            'clicks' => 25000,
            'domains' => 10,
            'users' => 3,
            'tags' => 25,
            'folders' => 25,
            'ai' => 100,
            'payouts' => 0,
        ],
        'pro' => [
            'links' => 5000,
            'clicks' => 100000,
            'domains' => 40,
            'users' => 10,
            'tags' => 150,
            'folders' => 200,
            'ai' => 1000,
            'payouts' => 1000,
        ],
        'business' => [
            'links' => 25000,
            'clicks' => 1000000,
            'domains' => 100,
            'users' => 25,
            'tags' => 500,
            'folders' => 1000,
            'ai' => 5000,
            'payouts' => 10000,
        ],
        'enterprise' => [
            'links' => -1, // unlimited
            'clicks' => -1, // unlimited
            'domains' => -1, // unlimited
            'users' => -1, // unlimited
            'tags' => -1, // unlimited
            'folders' => -1, // unlimited
            'ai' => -1, // unlimited
            'payouts' => -1, // unlimited
        ],
    ];

    /**
     * Check if project can create a new link
     */
    public function canCreateLink(Project $project): bool
    {
        $limit = $this->getLinkLimit($project);

        if ($limit === -1) {
            return true; // unlimited
        }

        return $project->links_usage < $limit;
    }

    /**
     * Check if project can handle more clicks
     */
    public function canHandleClick(Project $project): bool
    {
        $limit = $this->getClickLimit($project);

        if ($limit === -1) {
            return true; // unlimited
        }

        return $project->usage < $limit;
    }

    /**
     * Increment link usage
     */
    public function incrementLinkUsage(Project $project): void
    {
        $project->increment('links_usage');
        $project->increment('total_links');

        $this->checkLinkLimits($project->fresh());
    }

    /**
     * Increment click usage
     */
    public function incrementClickUsage(Project $project, int $clicks = 1): void
    {
        $project->increment('usage', $clicks);
        $project->increment('total_clicks', $clicks);

        $this->checkClickLimits($project->fresh());
    }

    /**
     * Increment AI usage
     */
    public function incrementAiUsage(Project $project, int $usage = 1): void
    {
        $project->increment('ai_usage', $usage);

        $this->checkAiLimits($project->fresh());
    }

    /**
     * Reset monthly usage counters
     * Called by scheduled job on billing cycle start
     */
    public function resetMonthlyUsage(Project $project): void
    {
        $project->update([
            'usage' => 0,
            'monthly_clicks' => 0,
            'current_month' => now()->format('Y-m'),
        ]);

        Log::info('Reset monthly usage for project', [
            'project_id' => $project->id,
            'billing_cycle_start' => $project->billing_cycle_start,
        ]);
    }

    /**
     * Get usage statistics for project
     */
    public function getUsageStats(Project $project): array
    {
        $limits = $this->getPlanLimits($project->plan);

        return [
            'plan' => $project->plan,
            'billing_cycle_start' => $project->billing_cycle_start,
            'usage' => [
                'links' => [
                    'used' => $project->links_usage,
                    'limit' => $limits['links'],
                    'percentage' => $this->calculateUsagePercentage($project->links_usage, $limits['links']),
                ],
                'clicks' => [
                    'used' => $project->usage,
                    'limit' => $limits['clicks'],
                    'percentage' => $this->calculateUsagePercentage($project->usage, $limits['clicks']),
                ],
                'domains' => [
                    'used' => $project->domains()->count(),
                    'limit' => $limits['domains'],
                    'percentage' => $this->calculateUsagePercentage($project->domains()->count(), $limits['domains']),
                ],
                'users' => [
                    'used' => $project->users()->count(),
                    'limit' => $limits['users'],
                    'percentage' => $this->calculateUsagePercentage($project->users()->count(), $limits['users']),
                ],
                'ai' => [
                    'used' => $project->ai_usage,
                    'limit' => $limits['ai'],
                    'percentage' => $this->calculateUsagePercentage($project->ai_usage, $limits['ai']),
                ],
            ],
            'overage' => $this->calculateOverage($project),
        ];
    }

    /**
     * Check if project is over limits
     */
    public function isOverLimits(Project $project): array
    {
        $limits = $this->getPlanLimits($project->plan);
        $overages = [];

        if ($limits['links'] !== -1 && $project->links_usage > $limits['links']) {
            $overages['links'] = $project->links_usage - $limits['links'];
        }

        if ($limits['clicks'] !== -1 && $project->usage > $limits['clicks']) {
            $overages['clicks'] = $project->usage - $limits['clicks'];
        }

        if ($limits['ai'] !== -1 && $project->ai_usage > $limits['ai']) {
            $overages['ai'] = $project->ai_usage - $limits['ai'];
        }

        return $overages;
    }

    /**
     * Get plan limits
     */
    public function getPlanLimits(string $plan): array
    {
        return self::PLAN_LIMITS[$plan] ?? self::PLAN_LIMITS['free'];
    }

    /**
     * Get link limit for project
     */
    private function getLinkLimit(Project $project): int
    {
        return $this->getPlanLimits($project->plan)['links'];
    }

    /**
     * Get click limit for project
     */
    private function getClickLimit(Project $project): int
    {
        return $this->getPlanLimits($project->plan)['clicks'];
    }

    /**
     * Check link limits and send notifications
     */
    private function checkLinkLimits(Project $project): void
    {
        $limit = $this->getLinkLimit($project);

        if ($limit === -1) {
            return; // unlimited
        }

        $percentage = $this->calculateUsagePercentage($project->links_usage, $limit);

        if ($percentage >= 80 && $percentage < 100) {
            $this->sendLimitWarning($project, 'links', $percentage);
        } elseif ($percentage >= 100) {
            $this->sendLimitExceeded($project, 'links', $percentage);
        }
    }

    /**
     * Check click limits and send notifications
     */
    private function checkClickLimits(Project $project): void
    {
        $limit = $this->getClickLimit($project);

        if ($limit === -1) {
            return; // unlimited
        }

        $percentage = $this->calculateUsagePercentage($project->usage, $limit);

        if ($percentage >= 80 && $percentage < 100) {
            $this->sendLimitWarning($project, 'clicks', $percentage);
        } elseif ($percentage >= 100) {
            $this->sendLimitExceeded($project, 'clicks', $percentage);
        }
    }

    /**
     * Check AI limits and send notifications
     */
    private function checkAiLimits(Project $project): void
    {
        $limit = $this->getPlanLimits($project->plan)['ai'];

        if ($limit === -1) {
            return; // unlimited
        }

        $percentage = $this->calculateUsagePercentage($project->ai_usage, $limit);

        if ($percentage >= 80 && $percentage < 100) {
            $this->sendLimitWarning($project, 'ai', $percentage);
        } elseif ($percentage >= 100) {
            $this->sendLimitExceeded($project, 'ai', $percentage);
        }
    }

    /**
     * Send limit warning email
     */
    private function sendLimitWarning(Project $project, string $type, float $percentage): void
    {
        SendLimitEmailJob::dispatch($project->id, 'warning');
    }

    /**
     * Send limit exceeded email
     */
    private function sendLimitExceeded(Project $project, string $type, float $percentage): void
    {
        SendLimitEmailJob::dispatch($project->id, 'exceeded');
    }

    /**
     * Calculate usage percentage
     */
    private function calculateUsagePercentage(int $used, int $limit): float
    {
        if ($limit === -1 || $limit === 0) {
            return 0.0;
        }

        return ($used / $limit) * 100;
    }

    /**
     * Calculate overage charges
     */
    private function calculateOverage(Project $project): array
    {
        $overages = $this->isOverLimits($project);
        $charges = [];

        // Overage pricing (example rates)
        $overageRates = [
            'clicks' => 0.001, // $0.001 per click
            'links' => 0.10,   // $0.10 per link
            'ai' => 0.01,      // $0.01 per AI request
        ];

        foreach ($overages as $type => $amount) {
            if (isset($overageRates[$type])) {
                $charges[$type] = [
                    'amount' => $amount,
                    'rate' => $overageRates[$type],
                    'charge' => $amount * $overageRates[$type],
                ];
            }
        }

        return $charges;
    }
}
