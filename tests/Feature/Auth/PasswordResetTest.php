<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Password Reset Flow Tests
 *
 * Tests the complete password reset process including:
 * - Password reset request
 * - Password reset token validation
 * - Password update with valid token
 * - Password reset form validation
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('auth/forgot-password'));
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertSessionHasNoErrors();
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);
            $response->assertInertia(fn ($page) => $page->component('auth/reset-password'));

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertRedirect('/login');

            // Verify password was updated
            $user->refresh();
            $this->assertTrue(Hash::check('newpassword123', $user->password_hash));

            return true;
        });
    }

    public function test_password_reset_requires_email(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => '',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_reset_requires_valid_email(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_reset_requires_existing_email(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        // Laravel doesn't reveal if email exists for security
        $response->assertSessionHasNoErrors();
    }

    public function test_password_reset_form_requires_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => '',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('token');
    }

    public function test_password_reset_form_requires_email(): void
    {
        $response = $this->post('/reset-password', [
            'token' => 'valid-token',
            'email' => '',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_reset_form_requires_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => 'valid-token',
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_password_reset_form_requires_password_confirmation(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => 'valid-token',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_password_reset_form_requires_minimum_password_length(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => 'valid-token',
            'email' => $user->email,
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_reset_token_expires(): void
    {
        $user = User::factory()->create();

        // Create an expired token
        $token = Password::createToken($user);
        
        // Simulate token expiration by manipulating the database
        \DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update(['created_at' => now()->subHours(2)]);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_reset_token_can_only_be_used_once(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            // Use the token once
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

            $response->assertSessionHasNoErrors();

            // Try to use the same token again
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'anothernewpassword123',
                'password_confirmation' => 'anothernewpassword123',
            ]);

            $response->assertSessionHasErrors('email');

            return true;
        });
    }

    public function test_user_is_redirected_to_login_after_successful_password_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

            $response->assertRedirect('/login');
            $response->assertSessionHas('status');

            return true;
        });
    }
}
