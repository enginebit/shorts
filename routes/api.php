<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\ClickController;
use App\Http\Controllers\Api\DomainsController;
use App\Http\Controllers\Api\LinksController;
use App\Http\Controllers\Api\ProjectsController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// Public authentication routes with rate limiting
Route::prefix('auth')->middleware('rate.limit:auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Password reset routes with stricter rate limiting
    Route::middleware('rate.limit:password-reset')->group(function () {
        Route::post('password/email', [PasswordResetController::class, 'sendResetLink']);
        Route::post('password/reset', [PasswordResetController::class, 'resetPassword']);
    });

    // OAuth routes
    Route::prefix('oauth')->group(function () {
        Route::get('{provider}', [OAuthController::class, 'redirect'])
            ->where('provider', 'google|github');
        Route::get('{provider}/callback', [OAuthController::class, 'callback'])
            ->where('provider', 'google|github');
    });
});

// Protected authentication routes
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

/*
|--------------------------------------------------------------------------
| Protected API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    // User profile route (legacy compatibility)
    Route::get('/user', [AuthController::class, 'me']);

    /*
    |--------------------------------------------------------------------------
    | Core API Routes
    |--------------------------------------------------------------------------
    */

    // Links API - following dub-main patterns with rate limiting
    Route::middleware('rate.limit:links')->group(function () {
        Route::apiResource('links', LinksController::class);
    });

    // Analytics API - following dub-main patterns with rate limiting
    Route::middleware('rate.limit:analytics')->group(function () {
        Route::get('links/{linkId}/analytics', [AnalyticsController::class, 'linkAnalytics']);
        Route::get('analytics/overview', [AnalyticsController::class, 'overview']);
        Route::get('analytics/timeseries', [AnalyticsController::class, 'timeseries']);
        Route::get('analytics/conversions', [AnalyticsController::class, 'conversions']);
    });

    // Billing API - following dub-main patterns
    Route::get('billing/overview', [BillingController::class, 'overview']);
    Route::get('billing/usage', [BillingController::class, 'usage']);
    Route::get('billing/plans', [BillingController::class, 'plans']);
    Route::get('billing/history', [BillingController::class, 'billingHistory']);
    Route::post('billing/subscription', [BillingController::class, 'createSubscription']);
    Route::put('billing/subscription', [BillingController::class, 'updateSubscription']);
    Route::delete('billing/subscription', [BillingController::class, 'cancelSubscription']);
    Route::post('billing/setup-intent', [BillingController::class, 'createSetupIntent']);

    // Domains API
    Route::apiResource('domains', DomainsController::class);
    Route::put('domains/{id}/verify', [DomainsController::class, 'verify']);

    // Projects/Workspaces API with rate limiting
    Route::middleware('rate.limit:workspaces')->group(function () {
        Route::apiResource('projects', ProjectsController::class);
    });

    // Conversion tracking API
    Route::post('conversions/lead', [ClickController::class, 'recordLead']);
    Route::post('conversions/sale', [ClickController::class, 'recordSale']);
});

/*
|--------------------------------------------------------------------------
| Public Click Tracking Routes
|--------------------------------------------------------------------------
*/

// Click tracking route (public, no auth required) with high rate limits
Route::middleware('rate.limit:redirects')->group(function () {
    Route::get('{domain}/{key}', [ClickController::class, 'handleClick'])
        ->where('domain', '[a-zA-Z0-9.-]+')
        ->where('key', '[a-zA-Z0-9._-]+');
});

// Stripe webhook route (public, no auth required)
Route::post('webhooks/stripe', [StripeWebhookController::class, 'handleWebhook']);

/*
|--------------------------------------------------------------------------
| Supabase Authentication API Routes
|--------------------------------------------------------------------------
*/

// Include Supabase authentication routes
require __DIR__.'/supabase-api.php';
