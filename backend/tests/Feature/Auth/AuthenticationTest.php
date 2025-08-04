<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'UniqueTestPassword123!@#',
            'password_confirmation' => 'UniqueTestPassword123!@#',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'image', 'email_verified'],
                'token',
                'expires_at',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::create([
            'id' => 'user_test123',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_hash' => Hash::make('UniqueTestPassword123!@#'),
            'email_verified_at' => now(),
            'subscribed' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'UniqueTestPassword123!@#',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'email_verified'],
                'token',
                'expires_at',
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::create([
            'id' => 'user_test123',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_access_protected_routes(): void
    {
        $user = User::create([
            'id' => 'user_test123',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'email_verified_at' => now(),
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
    }

    public function test_user_can_logout(): void
    {
        $user = User::create([
            'id' => 'user_test123',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'email_verified_at' => now(),
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);

        // Token should be revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }
}
