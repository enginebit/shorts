<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add security headers to all responses
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Inertia.js middleware for web routes
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // Rate limiting for API routes
        $middleware->throttleApi('60,1'); // 60 requests per minute

        // CORS configuration for API
        $middleware->api(append: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Scheduled maintenance tasks following dub-main cron patterns

        // Clean up expired links every hour
        $schedule->job(new \App\Jobs\ScheduledMaintenanceJob('cleanup_expired_links'))
            ->hourly()
            ->name('cleanup-expired-links')
            ->withoutOverlapping();

        // Update project statistics every 30 minutes
        $schedule->job(new \App\Jobs\ScheduledMaintenanceJob('update_project_stats'))
            ->everyThirtyMinutes()
            ->name('update-project-stats')
            ->withoutOverlapping();

        // Clean up old failed jobs daily at 2 AM
        $schedule->job(new \App\Jobs\ScheduledMaintenanceJob('cleanup_failed_jobs'))
            ->dailyAt('02:00')
            ->name('cleanup-failed-jobs')
            ->withoutOverlapping();

        // System health check every 15 minutes
        $schedule->job(new \App\Jobs\ScheduledMaintenanceJob('health_check'))
            ->everyFifteenMinutes()
            ->name('system-health-check')
            ->withoutOverlapping();

        // Reset monthly usage counters on the first day of each month
        $schedule->job(new \App\Jobs\ScheduledMaintenanceJob('reset_monthly_usage'))
            ->monthlyOn(1, '00:00')
            ->name('reset-monthly-usage')
            ->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
