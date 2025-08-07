<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * NotificationPreference Model
 *
 * Based on dub-main Prisma schema: NotificationPreference model
 *
 * Manages user notification preferences for each workspace.
 */
final class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_user_id',
        'link_usage_summary',
        'domain_configuration_updates',
        'new_partner_sale',
        'new_partner_application',
    ];

    protected $casts = [
        'link_usage_summary' => 'boolean',
        'domain_configuration_updates' => 'boolean',
        'new_partner_sale' => 'boolean',
        'new_partner_application' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The workspace user this preference belongs to
     */
    public function workspaceUser(): BelongsTo
    {
        return $this->belongsTo(WorkspaceUser::class);
    }
}
