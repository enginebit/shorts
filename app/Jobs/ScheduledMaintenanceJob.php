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
 * Scheduled Maintenance Job
 *
 * Performs regular maintenance tasks like cleanup, statistics updates,
 * and system health checks following dub-main cron patterns
 */
final class ScheduledMaintenanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300; // 5 minutes

    public function __construct(
        private readonly string $taskType
    ) {
        $this->onQueue('maintenance');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            match ($this->taskType) {
                'cleanup_expired_links' => $this->cleanupExpiredLinks(),
                'update_project_stats' => $this->updateProjectStats(),
                'cleanup_failed_jobs' => $this->cleanupFailedJobs(),
                'health_check' => $this->performHealthCheck(),
                'reset_monthly_usage' => $this->resetMonthlyUsage(),
                default => throw new Exception("Unknown maintenance task: {$this->taskType}")
            };

            Log::info('Scheduled maintenance completed', [
                'task_type' => $this->taskType,
                'completed_at' => now(),
            ]);
        } catch (Exception $e) {
            Log::error('Scheduled maintenance failed', [
                'task_type' => $this->taskType,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Clean up expired links
     */
    private function cleanupExpiredLinks(): void
    {
        $expiredCount = Link::where('expires_at', '<', now())
            ->whereNull('deleted_at')
            ->count();

        if ($expiredCount > 0) {
            Link::where('expires_at', '<', now())
                ->whereNull('deleted_at')
                ->delete();

            Log::info('Cleaned up expired links', [
                'count' => $expiredCount,
            ]);
        }
    }

    /**
     * Update project statistics
     */
    private function updateProjectStats(): void
    {
        $projects = Project::with(['links'])->get();

        foreach ($projects as $project) {
            $totalClicks = $project->links()->sum('clicks');
            $totalLinks = $project->links()->count();
            $activeLinks = $project->links()
                ->whereNull('deleted_at')
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->count();

            $project->update([
                'total_clicks' => $totalClicks,
                'links_usage' => $totalLinks,
                'active_links' => $activeLinks,
            ]);
        }

        Log::info('Updated project statistics', [
            'projects_updated' => $projects->count(),
        ]);
    }

    /**
     * Clean up old failed jobs
     */
    private function cleanupFailedJobs(): void
    {
        $cutoffDate = now()->subDays(7); // Keep failed jobs for 7 days

        $deletedCount = DB::table('failed_jobs')
            ->where('failed_at', '<', $cutoffDate)
            ->delete();

        if ($deletedCount > 0) {
            Log::info('Cleaned up old failed jobs', [
                'count' => $deletedCount,
            ]);
        }
    }

    /**
     * Perform system health check
     */
    private function performHealthCheck(): void
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $allHealthy = array_reduce($checks, fn ($carry, $check) => $carry && $check, true);

        Log::info('System health check completed', [
            'checks' => $checks,
            'all_healthy' => $allHealthy,
        ]);

        if (! $allHealthy) {
            Log::warning('System health check failed', [
                'failed_checks' => array_keys(array_filter($checks, fn ($check) => ! $check)),
            ]);
        }
    }

    /**
     * Reset monthly usage counters
     */
    private function resetMonthlyUsage(): void
    {
        $currentMonth = now()->format('Y-m');

        $updatedCount = Project::where('current_month', '!=', $currentMonth)
            ->update([
                'current_month' => $currentMonth,
                'monthly_clicks' => 0,
                'monthly_links' => 0,
            ]);

        if ($updatedCount > 0) {
            Log::info('Reset monthly usage counters', [
                'projects_updated' => $updatedCount,
                'month' => $currentMonth,
            ]);
        }
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Exception $e) {
            Log::error('Database health check failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Check Redis connectivity
     */
    private function checkRedis(): bool
    {
        try {
            $redis = app('redis');
            $redis->ping();

            return true;
        } catch (Exception $e) {
            Log::error('Redis health check failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Check storage accessibility
     */
    private function checkStorage(): bool
    {
        try {
            $disk = app('filesystem.disk');
            $testFile = 'health-check-'.time().'.txt';
            $disk->put($testFile, 'health check');
            $exists = $disk->exists($testFile);
            $disk->delete($testFile);

            return $exists;
        } catch (Exception $e) {
            Log::error('Storage health check failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Check queue system
     */
    private function checkQueue(): bool
    {
        try {
            // Check if we can connect to the queue
            $queueSize = app('queue')->size();

            return is_numeric($queueSize);
        } catch (Exception $e) {
            Log::error('Queue health check failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Scheduled maintenance job failed permanently', [
            'task_type' => $this->taskType,
            'error' => $exception->getMessage(),
        ]);
    }
}
