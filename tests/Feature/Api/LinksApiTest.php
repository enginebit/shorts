<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Link;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class LinksApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::create([
            'id' => 'user_test123',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password'),
            'email_verified_at' => now(),
            'subscribed' => true,
        ]);

        // Create test project
        $this->project = Project::create([
            'id' => 'proj_test123',
            'name' => 'Test Project',
            'slug' => 'test-project',
            'plan' => 'free',
            'usage' => 0,
            'usage_limit' => 1000,
            'billing_cycle_start' => now()->startOfMonth(),
            'links_limit' => 25,
            'domains_limit' => 3,
            'tags_limit' => 5,
            'users_limit' => 1,
            'invite_code' => 'test_invite_code',
        ]);

        // Associate user with project
        $this->project->users()->attach($this->user->id, [
            'role' => 'owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Set as default workspace
        $this->user->update(['default_workspace' => $this->project->id]);

        // Create API token
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_can_create_link(): void
    {
        $linkData = [
            'url' => 'https://example.com',
            'title' => 'Test Link',
            'description' => 'A test link',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/links', $linkData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'domain',
                'key',
                'url',
                'title',
                'description',
                'project_id',
                'user_id',
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'url' => 'https://example.com',
                'title' => 'Test Link',
                'description' => 'A test link',
                'project_id' => $this->project->id,
                'user_id' => $this->user->id,
            ]);

        $this->assertDatabaseHas('links', [
            'url' => 'https://example.com',
            'title' => 'Test Link',
            'project_id' => $this->project->id,
        ]);
    }

    public function test_can_list_links(): void
    {
        // Create test links
        Link::create([
            'id' => 'link_test1',
            'domain' => 'dub.sh',
            'key' => 'test1',
            'url' => 'https://example1.com',
            'short_link' => 'https://dub.sh/test1',
            'title' => 'Test Link 1',
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        Link::create([
            'id' => 'link_test2',
            'domain' => 'dub.sh',
            'key' => 'test2',
            'url' => 'https://example2.com',
            'short_link' => 'https://dub.sh/test2',
            'title' => 'Test Link 2',
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/links');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'links' => [
                    '*' => [
                        'id',
                        'domain',
                        'key',
                        'url',
                        'title',
                        'project_id',
                        'user_id',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'pagination' => [
                    'page',
                    'pageSize',
                    'total',
                    'totalPages',
                ],
            ]);

        $this->assertCount(2, $response->json('links'));
    }

    public function test_can_show_specific_link(): void
    {
        $link = Link::create([
            'id' => 'link_test123',
            'domain' => 'dub.sh',
            'key' => 'test123',
            'url' => 'https://example.com',
            'short_link' => 'https://dub.sh/test123',
            'title' => 'Test Link',
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson("/api/links/{$link->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $link->id,
                'domain' => 'dub.sh',
                'key' => 'test123',
                'url' => 'https://example.com',
                'title' => 'Test Link',
            ]);
    }

    public function test_can_update_link(): void
    {
        $link = Link::create([
            'id' => 'link_test123',
            'domain' => 'dub.sh',
            'key' => 'test123',
            'url' => 'https://example.com',
            'short_link' => 'https://dub.sh/test123',
            'title' => 'Test Link',
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $updateData = [
            'title' => 'Updated Test Link',
            'description' => 'Updated description',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->putJson("/api/links/{$link->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $link->id,
                'title' => 'Updated Test Link',
                'description' => 'Updated description',
            ]);

        $this->assertDatabaseHas('links', [
            'id' => $link->id,
            'title' => 'Updated Test Link',
            'description' => 'Updated description',
        ]);
    }

    public function test_can_delete_link(): void
    {
        $link = Link::create([
            'id' => 'link_test123',
            'domain' => 'dub.sh',
            'key' => 'test123',
            'url' => 'https://example.com',
            'short_link' => 'https://dub.sh/test123',
            'title' => 'Test Link',
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->deleteJson("/api/links/{$link->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Link deleted successfully',
            ]);

        // Check that the link is soft deleted
        $this->assertSoftDeleted('links', [
            'id' => $link->id,
        ]);
    }

    public function test_cannot_access_links_without_authentication(): void
    {
        $response = $this->getJson('/api/links');

        $response->assertStatus(401);
    }

    public function test_cannot_access_other_workspace_links(): void
    {
        // Create another user and project
        $otherUser = User::create([
            'id' => 'user_other',
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password_hash' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $otherProject = Project::create([
            'id' => 'proj_other',
            'name' => 'Other Project',
            'slug' => 'other-project',
            'plan' => 'free',
            'billing_cycle_start' => now()->startOfMonth(),
            'links_limit' => 25,
            'domains_limit' => 3,
            'tags_limit' => 5,
            'users_limit' => 1,
            'invite_code' => 'other_invite_code',
        ]);

        $otherProject->users()->attach($otherUser->id, ['role' => 'owner']);

        $otherLink = Link::create([
            'id' => 'link_other',
            'domain' => 'dub.sh',
            'key' => 'other',
            'url' => 'https://other.com',
            'short_link' => 'https://dub.sh/other',
            'project_id' => $otherProject->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson("/api/links/{$otherLink->id}");

        $response->assertStatus(404);
    }
}
