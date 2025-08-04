<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ClickController;
use App\Http\Controllers\Api\DomainsController;
use App\Http\Controllers\Api\LinksController;
use App\Http\Controllers\Api\ProjectsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Password reset routes
    Route::post('password/email', [PasswordResetController::class, 'sendResetLink']);
    Route::post('password/reset', [PasswordResetController::class, 'resetPassword']);

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

    // Links API - following dub-main patterns
    Route::apiResource('links', LinksController::class);

    // Analytics API - following dub-main patterns
    Route::get('links/{linkId}/analytics', [AnalyticsController::class, 'linkAnalytics']);
    Route::get('analytics/overview', [AnalyticsController::class, 'overview']);
    Route::get('analytics/timeseries', [AnalyticsController::class, 'timeseries']);
    Route::get('analytics/conversions', [AnalyticsController::class, 'conversions']);

    // Domains API
    Route::apiResource('domains', DomainsController::class);
    Route::put('domains/{id}/verify', [DomainsController::class, 'verify']);

    // Projects/Workspaces API
    Route::apiResource('projects', ProjectsController::class);

    // Conversion tracking API
    Route::post('conversions/lead', [ClickController::class, 'recordLead']);
    Route::post('conversions/sale', [ClickController::class, 'recordSale']);
});

/*
|--------------------------------------------------------------------------
| Public Click Tracking Routes
|--------------------------------------------------------------------------
*/

// Click tracking route (public, no auth required)
Route::get('{domain}/{key}', [ClickController::class, 'handleClick'])
    ->where('domain', '[a-zA-Z0-9.-]+')
    ->where('key', '[a-zA-Z0-9._-]+');
