<?php

declare(strict_types=1);

namespace Tests\Feature\Workspace;

use App\Models\User;
use App\Models\Workspace;
use App\Services\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Workspace Management Tests
 *
 * Tests workspace creation, management, and team collaboration including:
 * - Workspace creation after registration
 * - Workspace settings updates
 * - Team member invitations
 * - Workspace deletion and transfer
 */
class WorkspaceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock EmailService to prevent actual email sending during tests
        $this->mock(EmailService::class, function ($mock) {
            $mock->shouldReceive('sendWelcomeEmail')->andReturn(true);
            $mock->shouldReceive('sendWorkspaceInvitation')->andReturn(true);
        });
    }

    /** @test */
    public function user_can_create_workspace(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/workspaces', [
            'name' => 'Test Workspace',
            'slug' => 'test-workspace',
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('workspaces', [
            'name' => 'Test Workspace',
            'slug' => 'test-workspace',
        ]);

        // User should be added as owner
        $workspace = Workspace::where('slug', 'test-workspace')->first();
        $this->assertTrue($workspace->users->contains($user));
        $this->assertEquals('owner', $workspace->users->first()->pivot->role);
    }

    /** @test */
    public function workspace_creation_requires_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/workspaces', [
            'name' => '',
            'slug' => 'test-workspace',
        ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function workspace_creation_requires_unique_slug(): void
    {
        $user = User::factory()->create();
        Workspace::factory()->create(['slug' => 'existing-workspace']);

        $response = $this->actingAs($user)->post('/workspaces', [
            'name' => 'Test Workspace',
            'slug' => 'existing-workspace',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    /** @test */
    public function workspace_slug_must_be_valid_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/workspaces', [
            'name' => 'Test Workspace',
            'slug' => 'Invalid Slug!',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    /** @test */
    public function workspace_owner_can_update_workspace_settings(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'owner']);

        $response = $this->actingAs($user)->patch("/workspaces/{$workspace->id}", [
            'name' => 'Updated Workspace Name',
            'description' => 'Updated description',
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'name' => 'Updated Workspace Name',
            'description' => 'Updated description',
        ]);
    }

    /** @test */
    public function workspace_admin_can_update_workspace_settings(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'admin']);

        $response = $this->actingAs($user)->patch("/workspaces/{$workspace->id}", [
            'name' => 'Updated Workspace Name',
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'name' => 'Updated Workspace Name',
        ]);
    }

    /** @test */
    public function workspace_member_cannot_update_workspace_settings(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        $response = $this->actingAs($user)->patch("/workspaces/{$workspace->id}", [
            'name' => 'Updated Workspace Name',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function workspace_owner_can_upload_logo(): void
    {
        Storage::fake('public');
        
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'owner']);

        $file = UploadedFile::fake()->image('logo.png', 100, 100);

        $response = $this->actingAs($user)->patch("/workspaces/{$workspace->id}", [
            'name' => $workspace->name,
            'logo' => $file,
        ]);

        $response->assertRedirect();
        
        $workspace->refresh();
        $this->assertNotNull($workspace->logo);
        Storage::disk('public')->assertExists($workspace->logo);
    }

    /** @test */
    public function workspace_logo_must_be_image(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'owner']);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($user)->patch("/workspaces/{$workspace->id}", [
            'name' => $workspace->name,
            'logo' => $file,
        ]);

        $response->assertSessionHasErrors('logo');
    }

    /** @test */
    public function workspace_owner_can_invite_team_members(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'owner']);

        $response = $this->actingAs($user)->post("/workspaces/{$workspace->id}/invites", [
            'invites' => [
                ['email' => 'newmember@example.com', 'role' => 'member'],
                ['email' => 'newadmin@example.com', 'role' => 'admin'],
            ],
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('workspace_invites', [
            'workspace_id' => $workspace->id,
            'email' => 'newmember@example.com',
            'role' => 'member',
        ]);

        $this->assertDatabaseHas('workspace_invites', [
            'workspace_id' => $workspace->id,
            'email' => 'newadmin@example.com',
            'role' => 'admin',
        ]);
    }

    /** @test */
    public function workspace_admin_can_invite_members_but_not_admins(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'admin']);

        $response = $this->actingAs($user)->post("/workspaces/{$workspace->id}/invites", [
            'invites' => [
                ['email' => 'newmember@example.com', 'role' => 'member'],
            ],
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('workspace_invites', [
            'workspace_id' => $workspace->id,
            'email' => 'newmember@example.com',
            'role' => 'member',
        ]);
    }

    /** @test */
    public function workspace_member_cannot_invite_team_members(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        $response = $this->actingAs($user)->post("/workspaces/{$workspace->id}/invites", [
            'invites' => [
                ['email' => 'newmember@example.com', 'role' => 'member'],
            ],
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function workspace_owner_can_delete_workspace(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'owner']);

        $response = $this->actingAs($user)->delete("/workspaces/{$workspace->id}");

        $response->assertRedirect('/dashboard');
        
        $this->assertSoftDeleted('workspaces', [
            'id' => $workspace->id,
        ]);
    }

    /** @test */
    public function only_workspace_owner_can_delete_workspace(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'admin']);

        $response = $this->actingAs($user)->delete("/workspaces/{$workspace->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
        ]);
    }

    /** @test */
    public function workspace_settings_page_can_be_accessed_by_members(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        $response = $this->actingAs($user)->get("/{$workspace->slug}/settings");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('dashboard/settings'));
    }

    /** @test */
    public function non_members_cannot_access_workspace_settings(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        $response = $this->actingAs($user)->get("/{$workspace->slug}/settings");

        $response->assertRedirect('/dashboard');
    }
}
