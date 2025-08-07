<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Account Settings Tests
 *
 * Tests account settings management including:
 * - Profile updates (name, email, avatar)
 * - Security settings (password change, 2FA)
 * - API key generation and management
 * - Notification preferences
 */
class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_settings_page_can_be_accessed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/account/settings');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('account/settings'));
    }

    public function test_guests_cannot_access_account_settings(): void
    {
        $response = $this->get('/account/settings');

        $response->assertRedirect('/login');
    }

    public function test_user_can_update_profile_information(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $response = $this->actingAs($user)->patch('/account/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
    }

    public function test_profile_update_requires_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/account/profile', [
            'name' => '',
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_profile_update_requires_valid_email(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/account/profile', [
            'name' => $user->name,
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_profile_update_requires_unique_email(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($user)->patch('/account/profile', [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_upload_avatar(): void
    {
        Storage::fake('public');
        
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $response = $this->actingAs($user)->patch('/account/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->image);
        Storage::disk('public')->assertExists($user->image);
    }

    public function test_avatar_must_be_image(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($user)->patch('/account/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $file,
        ]);

        $response->assertSessionHasErrors('avatar');
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('oldpassword123'),
        ]);

        $response = $this->actingAs($user)->patch('/account/password', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password_hash));
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('oldpassword123'),
        ]);

        $response = $this->actingAs($user)->patch('/account/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_password_change_requires_confirmation(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('oldpassword123'),
        ]);

        $response = $this->actingAs($user)->patch('/account/password', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_password_change_requires_minimum_length(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('oldpassword123'),
        ]);

        $response = $this->actingAs($user)->patch('/account/password', [
            'current_password' => 'oldpassword123',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_user_can_generate_api_key(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/account/api-keys', [
            'name' => 'Test API Key',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'name',
            'key',
            'created_at',
        ]);

        $this->assertDatabaseHas('api_keys', [
            'user_id' => $user->id,
            'name' => 'Test API Key',
        ]);
    }

    public function test_api_key_generation_requires_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/account/api-keys', [
            'name' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_user_can_view_their_api_keys(): void
    {
        $user = User::factory()->create();
        
        // Create API keys for the user
        $user->apiKeys()->create([
            'name' => 'Test Key 1',
            'key' => 'test-key-1',
        ]);
        $user->apiKeys()->create([
            'name' => 'Test Key 2',
            'key' => 'test-key-2',
        ]);

        $response = $this->actingAs($user)->get('/account/api-keys');

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'Test Key 1']);
        $response->assertJsonFragment(['name' => 'Test Key 2']);
    }

    public function test_user_can_delete_their_api_key(): void
    {
        $user = User::factory()->create();
        $apiKey = $user->apiKeys()->create([
            'name' => 'Test Key',
            'key' => 'test-key',
        ]);

        $response = $this->actingAs($user)->delete("/account/api-keys/{$apiKey->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('api_keys', [
            'id' => $apiKey->id,
        ]);
    }

    public function test_user_cannot_delete_other_users_api_key(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        
        $apiKey = $otherUser->apiKeys()->create([
            'name' => 'Other User Key',
            'key' => 'other-key',
        ]);

        $response = $this->actingAs($user)->delete("/account/api-keys/{$apiKey->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('api_keys', [
            'id' => $apiKey->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_can_update_notification_preferences(): void
    {
        $user = User::factory()->create([
            'subscribed' => false,
        ]);

        $response = $this->actingAs($user)->patch('/account/notifications', [
            'email_notifications' => true,
            'marketing_emails' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertTrue($user->subscribed);
    }

    public function test_user_can_export_account_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/account/export');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'export_url',
        ]);
    }

    public function test_user_can_delete_account(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->delete('/account', [
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }

    public function test_account_deletion_requires_password_confirmation(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->delete('/account', [
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }

    public function test_deleted_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('password123'),
        ]);

        // Delete the account
        $this->actingAs($user)->delete('/account', [
            'password' => 'password123',
        ]);

        // Try to login
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
