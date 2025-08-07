<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Link;
use App\Models\Project;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Process Analytics Job
 *
 * Processes click analytics data and updates aggregated statistics
 * following dub-main analytics patterns
 */
final class ProcessAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function __construct(
        private readonly string $linkId,
        private readonly array $clickData
    ) {
        $this->onQueue('analytics');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $link = Link::with(['project'])->find($this->linkId);

        if (! $link) {
            Log::error('Link not found for analytics processing', [
                'link_id' => $this->linkId,
                'click_data' => $this->clickData,
            ]);

            return;
        }

        try {
            DB::transaction(function () use ($link) {
                $this->processClickData($link);
                $this->updateLinkStats($link);
                $this->updateProjectStats($link->project);
                $this->checkUsageLimits($link->project);
            });

            Log::info('Analytics processed successfully', [
                'link_id' => $this->linkId,
                'project_id' => $link->project_id,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to process analytics', [
                'link_id' => $this->linkId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process individual click data
     */
    private function processClickData(Link $link): void
    {
        $clickData = $this->clickData;

        // Extract analytics data
        $country = $clickData['country'] ?? 'Unknown';
        $city = $clickData['city'] ?? 'Unknown';
        $device = $clickData['device'] ?? 'Unknown';
        $browser = $clickData['browser'] ?? 'Unknown';
        $os = $clickData['os'] ?? 'Unknown';
        $referrer = $clickData['referrer'] ?? 'Direct';
        $timestamp = $clickData['timestamp'] ?? now();

        // TODO: Store detailed analytics in analytics table
        // This would integrate with Tinybird or similar analytics service
        Log::info('Processing click analytics', [
            'link_id' => $this->linkId,
            'country' => $country,
            'city' => $city,
            'device' => $device,
            'browser' => $browser,
            'os' => $os,
            'referrer' => $referrer,
            'timestamp' => $timestamp,
        ]);
    }

    /**
     * Update link statistics
     */
    private function updateLinkStats(Link $link): void
    {
        $link->increment('clicks');
        $link->update([
            'last_clicked' => now(),
        ]);

        // Update unique clicks if this is a unique visitor
        if ($this->isUniqueClick()) {
            $link->increment('unique_clicks');
        }
    }

    /**
     * Update project statistics
     */
    private function updateProjectStats(Project $project): void
    {
        $project->increment('usage'); // usage is the clicks usage field

        // Update monthly usage if needed
        $currentMonth = now()->format('Y-m');
        if ($project->current_month !== $currentMonth) {
            $project->update([
                'current_month' => $currentMonth,
                'monthly_clicks' => 1,
            ]);
        } else {
            $project->increment('monthly_clicks');
        }
    }

    /**
     * Check if usage limits are exceeded and trigger notifications
     */
    private function checkUsageLimits(Project $project): void
    {
        $clicksLimit = $project->usage_limit ?? 1000;
        $clicksUsage = $project->usage ?? 0;
        $usagePercentage = $clicksLimit > 0 ? ($clicksUsage / $clicksLimit) * 100 : 0;

        // Check for 80% usage threshold
        if ($usagePercentage >= 80 && $usagePercentage < 100) {
            $this->triggerLimitWarning($project, 'firstUsageLimitEmail');
        }

        // Check for 100% usage threshold
        if ($usagePercentage >= 100) {
            $this->triggerLimitWarning($project, 'secondUsageLimitEmail');
        }

        // Check links limit
        $linksLimit = $project->links_limit ?? 10;
        $linksUsage = $project->links_usage ?? 0;
        $linksPercentage = $linksLimit > 0 ? ($linksUsage / $linksLimit) * 100 : 0;

        if ($linksPercentage >= 80 && $linksPercentage < 100) {
            $this->triggerLimitWarning($project, 'firstLinksLimitEmail');
        }

        if ($linksPercentage >= 100) {
            $this->triggerLimitWarning($project, 'secondLinksLimitEmail');
        }
    }

    /**
     * Trigger limit warning email
     */
    private function triggerLimitWarning(Project $project, string $type): void
    {
        // Check if we've already sent this type of email recently
        $recentlySent = $this->hasRecentLimitEmail($project, $type);

        if (! $recentlySent) {
            SendLimitEmailJob::dispatch($project->id, $type);

            Log::info('Triggered limit warning email', [
                'project_id' => $project->id,
                'type' => $type,
            ]);
        }
    }

    /**
     * Check if we've recently sent a limit email of this type
     */
    private function hasRecentLimitEmail(Project $project, string $type): bool
    {
        // TODO: Check sent_emails table for recent emails of this type
        // For now, return false to allow emails
        return false;
    }

    /**
     * Determine if this is a unique click
     */
    private function isUniqueClick(): bool
    {
        // TODO: Implement unique click detection based on IP, user agent, etc.
        // This would typically involve checking a cache or database
        return true; // For now, consider all clicks unique
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Process analytics job failed permanently', [
            'link_id' => $this->linkId,
            'click_data' => $this->clickData,
            'error' => $exception->getMessage(),
        ]);
    }
}
