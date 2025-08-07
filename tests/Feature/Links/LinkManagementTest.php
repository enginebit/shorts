<?php

declare(strict_types=1);

namespace Tests\Feature\Links;

use App\Models\Link;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Link Management Tests
 *
 * Tests link creation, editing, and deletion including:
 * - Basic link creation
 * - Advanced link options (UTM parameters, device targeting)
 * - Link editing and updates
 * - Link deletion
 * - Link access control
 */
class LinkManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_basic_link(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        $response = $this->actingAs($user)->post('/api/links', [
            'url' => 'https://example.com',
            'domain' => 'dub.sh',
            'key' => 'test-link',
            'workspace_id' => $workspace->id,
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('links', [
            'url' => 'https://example.com',
            'domain' => 'dub.sh',
            'key' => 'test-link',
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function link_creation_requires_valid_url(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        $response = $this->actingAs($user)->post('/api/links', [
            'url' => 'invalid-url',
            'domain' => 'dub.sh',
            'key' => 'test-link',
            'workspace_id' => $workspace->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('url');
    }

    /** @test */
    public function link_key_must_be_unique_within_domain(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        // Create first link
        Link::factory()->create([
            'domain' => 'dub.sh',
            'key' => 'existing-key',
            'workspace_id' => $workspace->id,
        ]);

        $response = $this->actingAs($user)->post('/api/links', [
            'url' => 'https://example.com',
            'domain' => 'dub.sh',
            'key' => 'existing-key',
            'workspace_id' => $workspace->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('key');
    }

    /** @test */
    public function user_can_create_link_with_advanced_options(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        $response = $this->actingAs($user)->post('/api/links', [
            'url' => 'https://example.com',
            'domain' => 'dub.sh',
            'key' => 'advanced-link',
            'workspace_id' => $workspace->id,
            'title' => 'Test Link Title',
            'description' => 'Test link description',
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => 'summer-sale',
            'expires_at' => now()->addDays(30)->toDateTimeString(),
            'password' => 'secret123',
            'ios' => 'https://apps.apple.com/app/example',
            'android' => 'https://play.google.com/store/apps/details?id=example',
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('links', [
            'url' => 'https://example.com',
            'key' => 'advanced-link',
            'title' => 'Test Link Title',
            'description' => 'Test link description',
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => 'summer-sale',
            'password' => 'secret123',
            'ios' => 'https://apps.apple.com/app/example',
            'android' => 'https://play.google.com/store/apps/details?id=example',
        ]);
    }

    /** @test */
    public function user_can_update_their_link(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);
        
        $link = Link::factory()->create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'url' => 'https://example.com',
            'title' => 'Original Title',
        ]);

        $response = $this->actingAs($user)->put("/api/links/{$link->id}", [
            'url' => 'https://updated-example.com',
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('links', [
            'id' => $link->id,
            'url' => 'https://updated-example.com',
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ]);
    }

    /** @test */
    public function user_cannot_update_other_users_link(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);
        $workspace->users()->attach($otherUser, ['role' => 'member']);
        
        $link = Link::factory()->create([
            'user_id' => $otherUser->id,
            'workspace_id' => $workspace->id,
        ]);

        $response = $this->actingAs($user)->put("/api/links/{$link->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function workspace_admin_can_update_any_link_in_workspace(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($admin, ['role' => 'admin']);
        $workspace->users()->attach($member, ['role' => 'member']);
        
        $link = Link::factory()->create([
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
            'title' => 'Original Title',
        ]);

        $response = $this->actingAs($admin)->put("/api/links/{$link->id}", [
            'title' => 'Admin Updated Title',
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('links', [
            'id' => $link->id,
            'title' => 'Admin Updated Title',
        ]);
    }

    /** @test */
    public function user_can_delete_their_link(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);
        
        $link = Link::factory()->create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);

        $response = $this->actingAs($user)->delete("/api/links/{$link->id}");

        $response->assertStatus(200);
        
        $this->assertSoftDeleted('links', [
            'id' => $link->id,
        ]);
    }

    /** @test */
    public function user_cannot_delete_other_users_link(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);
        $workspace->users()->attach($otherUser, ['role' => 'member']);
        
        $link = Link::factory()->create([
            'user_id' => $otherUser->id,
            'workspace_id' => $workspace->id,
        ]);

        $response = $this->actingAs($user)->delete("/api/links/{$link->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('links', [
            'id' => $link->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function user_can_view_links_in_their_workspace(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);
        
        $links = Link::factory()->count(3)->create([
            'workspace_id' => $workspace->id,
        ]);

        $response = $this->actingAs($user)->get("/api/links?workspace_id={$workspace->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    /** @test */
    public function user_cannot_view_links_from_other_workspaces(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $otherWorkspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);
        
        Link::factory()->count(2)->create(['workspace_id' => $workspace->id]);
        Link::factory()->count(3)->create(['workspace_id' => $otherWorkspace->id]);

        $response = $this->actingAs($user)->get("/api/links?workspace_id={$workspace->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function links_can_be_searched_by_title_and_url(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);
        
        Link::factory()->create([
            'workspace_id' => $workspace->id,
            'title' => 'GitHub Repository',
            'url' => 'https://github.com/example/repo',
        ]);
        
        Link::factory()->create([
            'workspace_id' => $workspace->id,
            'title' => 'Documentation',
            'url' => 'https://docs.example.com',
        ]);

        $response = $this->actingAs($user)->get("/api/links?workspace_id={$workspace->id}&search=github");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'GitHub Repository');
    }

    /** @test */
    public function links_can_be_paginated(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);
        
        Link::factory()->count(25)->create(['workspace_id' => $workspace->id]);

        $response = $this->actingAs($user)->get("/api/links?workspace_id={$workspace->id}&per_page=10");

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
        $response->assertJsonStructure([
            'data',
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
    }
}
