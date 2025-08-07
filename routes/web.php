<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AnalyticsController;use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public routes
Route::get('/', function () {
    return Inertia::render('welcome');
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    // General dashboard (fallback)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Onboarding for new users
    Route::get('/onboarding', [OnboardingController::class, 'index'])
        ->name('onboarding');
    Route::post('/onboarding/workspace', [OnboardingController::class, 'createWorkspace'])
        ->name('onboarding.workspace');
    Route::post('/onboarding/accept/{inviteId}', [OnboardingController::class, 'acceptInvite'])
        ->name('onboarding.accept');
    Route::post('/onboarding/decline/{inviteId}', [OnboardingController::class, 'declineInvite'])
        ->name('onboarding.decline');
    Route::post('/onboarding/skip', [OnboardingController::class, 'skip'])
        ->name('onboarding.skip');

    // Workspace API routes
    Route::apiResource('api/workspaces', WorkspaceController::class);
});

// Workspace-aware routes (with workspace middleware)
// Note: These routes are properly constrained to avoid catching API routes
Route::middleware(['auth', 'workspace'])->group(function () {
    // Workspace links (more specific route first)
    Route::get('/{workspace}/links', [DashboardController::class, 'links'])
        ->where('workspace', '^(?!api$|admin$|www$|mail$|ftp$|storage$|sanctum$|up$)[a-zA-Z0-9_-]+$')
        ->name('workspace.links');

    // Workspace analytics
    Route::get('/{workspace}/analytics', [AnalyticsController::class, 'analytics'])
        ->where('workspace', '^(?!api$|admin$|www$|mail$|ftp$|storage$|sanctum$|up$)[a-zA-Z0-9_-]+$')
        ->name('workspace.analytics');

    // Workspace settings
    Route::get('/{workspace}/settings', [DashboardController::class, 'settings'])
        ->where('workspace', '^(?!api$|admin$|www$|mail$|ftp$|storage$|sanctum$|up$)[a-zA-Z0-9_-]+$')
        ->name('workspace.settings');

    // Workspace dashboard (catch-all, must be last and properly constrained)
    Route::get('/{workspace}', [DashboardController::class, 'workspace'])
        ->where('workspace', '^(?!api$|admin$|www$|mail$|ftp$|storage$|sanctum$|up$)[a-zA-Z0-9_-]+$')
        ->name('workspace.dashboard');
});
