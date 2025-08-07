<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Login Flow Tests
 *
 * Tests the complete user login process including:
 * - Login form validation
 * - Authentication with valid credentials
 * - Authentication failure with invalid credentials
 * - Remember me functionality
 * - Redirect to intended page after login
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('auth/login'));
    }

    /** @test */
    public function users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/onboarding');
    }

    /** @test */
    public function users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function users_can_not_authenticate_with_invalid_email(): void
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function login_requires_email(): void
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function login_requires_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    /** @test */
    public function login_requires_valid_email_format(): void
    {
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function remember_me_functionality_works(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/onboarding');

        // Check that remember token was set
        $user->refresh();
        $this->assertNotNull($user->remember_token);
    }

    /** @test */
    public function authenticated_users_are_redirected_from_login(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/onboarding');
    }

    /** @test */
    public function users_are_redirected_to_intended_page_after_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        // Try to access a protected page
        $this->get('/dashboard/settings');

        // Login
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Should be redirected to the intended page
        $response->assertRedirect('/dashboard/settings');
    }

    /** @test */
    public function login_throttling_works_after_multiple_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        // Make multiple failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // The next attempt should be throttled
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
    }

    /** @test */
    public function successful_login_after_failed_attempts_resets_throttling(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        // Make a few failed attempts
        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // Successful login should reset throttling
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/onboarding');
    }

    /** @test */
    public function case_insensitive_email_login_works(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'TEST@EXAMPLE.COM',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/onboarding');
    }
}
