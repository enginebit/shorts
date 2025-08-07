<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Logout Flow Tests
 *
 * Tests the user logout process including:
 * - Successful logout
 * - Session invalidation
 * - Redirect after logout
 * - Remember token cleanup
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    /** @test */
    public function logout_invalidates_session(): void
    {
        $user = User::factory()->create();

        // Login and get session ID
        $this->actingAs($user);
        $sessionId = session()->getId();

        // Logout
        $this->post('/logout');

        // Session should be invalidated
        $this->assertNotEquals($sessionId, session()->getId());
        $this->assertGuest();
    }

    /** @test */
    public function logout_clears_remember_token(): void
    {
        $user = User::factory()->create([
            'remember_token' => 'test-remember-token',
        ]);

        $this->actingAs($user)->post('/logout');

        $user->refresh();
        $this->assertNull($user->remember_token);
    }

    /** @test */
    public function guests_cannot_logout(): void
    {
        $response = $this->post('/logout');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function logout_redirects_to_home_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
    }

    /** @test */
    public function logout_removes_authentication_cookies(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        // Check that authentication cookies are cleared
        $response->assertCookieExpired('laravel_session');
    }
}
