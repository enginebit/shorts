<?php

declare(strict_types=1);

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
});
