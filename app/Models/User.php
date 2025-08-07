<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /**
     * Indicates if the model's ID is auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'image',
        'is_machine',
        'password_hash',
        'subscribed',
        'source',
        'default_workspace',
        'default_partner_id',
        'supabase_id',        // Add Supabase user ID
        'supabase_metadata',  // Store Supabase user metadata
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
        'invalid_login_attempts',
        'locked_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_machine' => 'boolean',
            'subscribed' => 'boolean',
            'invalid_login_attempts' => 'integer',
            'locked_at' => 'datetime',
            'supabase_metadata' => 'json',  // Cast Supabase metadata to JSON
        ];
    }

    /**
     * Get the projects for the user.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the links for the user.
     */
    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    /**
     * Workspaces the user belongs to
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_users')
            ->withPivot(['role', 'default_folder_id', 'workspace_preferences'])
            ->withTimestamps();
    }

    /**
     * Get user's default workspace
     */
    public function defaultWorkspace(): ?Workspace
    {
        if (! $this->default_workspace) {
            return null;
        }

        return Workspace::where('slug', $this->default_workspace)->first();
    }

    /**
     * Check if user owns any workspaces
     */
    public function ownsWorkspaces(): bool
    {
        return $this->workspaces()
            ->wherePivot('role', 'owner')
            ->exists();
    }

    /**
     * Get workspaces owned by user
     */
    public function ownedWorkspaces(): BelongsToMany
    {
        return $this->workspaces()
            ->wherePivot('role', 'owner');
    }

    /**
     * Check if user can create more free workspaces
     */
    public function canCreateFreeWorkspace(): bool
    {
        $freeWorkspacesCount = $this->ownedWorkspaces()
            ->where('plan', 'free')
            ->count();

        return $freeWorkspacesCount < 2; // FREE_WORKSPACES_LIMIT from dub-main
    }

    /**
     * Update user with Supabase metadata
     */
    public function updateFromSupabase(array $supabaseUser): void
    {
        $updates = [];

        // Update basic fields if they've changed
        if ($this->email !== $supabaseUser['email']) {
            $updates['email'] = $supabaseUser['email'];
        }

        // Update metadata
        $updates['supabase_metadata'] = [
            'aal' => $supabaseUser['aal'],
            'session_id' => $supabaseUser['session_id'],
            'is_anonymous' => $supabaseUser['is_anonymous'],
            'app_metadata' => $supabaseUser['app_metadata'],
            'user_metadata' => $supabaseUser['user_metadata'],
            'amr' => $supabaseUser['amr'],
            'last_updated' => now()->toISOString(),
        ];

        // Update name from user_metadata if available
        $userMetadata = $supabaseUser['user_metadata'];
        if (! empty($userMetadata['name']) && $this->name !== $userMetadata['name']) {
            $updates['name'] = $userMetadata['name'];
        } elseif (! empty($userMetadata['full_name']) && $this->name !== $userMetadata['full_name']) {
            $updates['name'] = $userMetadata['full_name'];
        }

        if (! empty($updates)) {
            $this->update($updates);
        }
    }

    /**
     * Check if user has specific Supabase role
     */
    public function hasSupabaseRole(string $role): bool
    {
        $metadata = $this->supabase_metadata ?? [];
        $appMetadata = $metadata['app_metadata'] ?? [];

        return ($appMetadata['role'] ?? null) === $role;
    }

    /**
     * Get user's authentication assurance level
     */
    public function getAuthAssuranceLevel(): string
    {
        $metadata = $this->supabase_metadata ?? [];

        return $metadata['aal'] ?? 'aal1';
    }

    /**
     * Check if user has MFA enabled
     */
    public function hasMfaEnabled(): bool
    {
        return $this->getAuthAssuranceLevel() === 'aal2';
    }

    /**
     * Get the password for authentication.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash ?? '';
    }
}
