<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WorkspaceInvite Model
 *
 * Based on dub-main Prisma schema: ProjectInvite model
 *
 * Represents pending invitations to join a workspace.
 */
final class WorkspaceInvite extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'expires_at',
        'role',
        'workspace_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The workspace this invite belongs to
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Check if the invite has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Scope for non-expired invites
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope for expired invites
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}
