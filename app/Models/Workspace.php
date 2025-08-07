<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Workspace Model
 *
 * Based on dub-main Prisma schema: Project model
 *
 * Represents a workspace (project) in the application with all associated
 * resources like links, domains, users, and billing information.
 */
final class Workspace extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'invite_code',
        'default_program_id',
        'plan',
        'stripe_id',
        'billing_cycle_start',
        'payment_failed_at',
        'invoice_prefix',
        'stripe_connect_id',
        'shopify_store_id',
        'total_links',
        'total_clicks',
        'usage',
        'usage_limit',
        'links_usage',
        'links_limit',
        'payouts_usage',
        'payouts_limit',
        'payout_fee',
        'domains_limit',
        'tags_limit',
        'folders_usage',
        'folders_limit',
        'users_limit',
        'ai_usage',
        'ai_limit',
        'referral_link_id',
        'referred_signups',
        'store',
        'allowed_hostnames',
        'conversion_enabled',
        'webhook_enabled',
        'partners_enabled',
        'sso_enabled',
        'dot_link_claimed',
        'usage_last_checked',
    ];

    protected $casts = [
        'billing_cycle_start' => 'integer',
        'payment_failed_at' => 'datetime',
        'total_links' => 'integer',
        'total_clicks' => 'integer',
        'usage' => 'integer',
        'usage_limit' => 'integer',
        'links_usage' => 'integer',
        'links_limit' => 'integer',
        'payouts_usage' => 'integer',
        'payouts_limit' => 'integer',
        'payout_fee' => 'decimal:4',
        'domains_limit' => 'integer',
        'tags_limit' => 'integer',
        'folders_usage' => 'integer',
        'folders_limit' => 'integer',
        'users_limit' => 'integer',
        'ai_usage' => 'integer',
        'ai_limit' => 'integer',
        'referred_signups' => 'integer',
        'store' => 'array',
        'allowed_hostnames' => 'array',
        'conversion_enabled' => 'boolean',
        'webhook_enabled' => 'boolean',
        'partners_enabled' => 'boolean',
        'sso_enabled' => 'boolean',
        'dot_link_claimed' => 'boolean',
        'usage_last_checked' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Users belonging to this workspace
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_users')
            ->withPivot(['role', 'default_folder_id', 'workspace_preferences'])
            ->withTimestamps();
    }

    /**
     * Pending invitations for this workspace
     */
    public function invites(): HasMany
    {
        return $this->hasMany(WorkspaceInvite::class);
    }

    /**
     * Links belonging to this workspace
     */
    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    /**
     * Domains belonging to this workspace (through projects relationship)
     * Note: In dub-main, domains belong to projects, not directly to workspaces
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'project_id', 'id');
    }

    /**
     * Tags belonging to this workspace
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * Check if user is owner of this workspace
     */
    public function isOwner(User $user): bool
    {
        return $this->users()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('role', 'owner')
            ->exists();
    }

    /**
     * Check if user is member of this workspace
     */
    public function isMember(User $user): bool
    {
        return $this->users()
            ->wherePivot('user_id', $user->id)
            ->exists();
    }

    /**
     * Get user's role in this workspace
     */
    public function getUserRole(User $user): ?string
    {
        $pivotData = $this->users()
            ->wherePivot('user_id', $user->id)
            ->first();

        return $pivotData?->pivot->role;
    }

    /**
     * Check if workspace has reached its limits
     */
    public function hasReachedLinksLimit(): bool
    {
        return $this->links_usage >= $this->links_limit;
    }

    public function hasReachedUsersLimit(): bool
    {
        return $this->users()->count() >= $this->users_limit;
    }

    public function hasReachedDomainsLimit(): bool
    {
        return $this->domains()->count() >= $this->domains_limit;
    }

    /**
     * Scope for free workspaces
     */
    public function scopeFree($query)
    {
        return $query->where('plan', 'free');
    }

    /**
     * Scope for workspaces owned by user
     */
    public function scopeOwnedBy($query, User $user)
    {
        return $query->whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->where('role', 'owner');
        });
    }
}
