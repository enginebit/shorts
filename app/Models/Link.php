<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Link extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

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
        'domain',
        'key',
        'url',
        'short_link',
        'archived',
        'expires_at',
        'expired_url',
        'password',
        'track_conversion',
        'proxy',
        'title',
        'description',
        'image',
        'video',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'rewrite',
        'link_retention_cleanup_disabled_at',
        'do_index',
        'ios',
        'android',
        'geo',
        'test_variants',
        'test_started_at',
        'test_completed_at',
        'user_id',
        'project_id',
        'folder_id',
        'external_id',
        'tenant_id',
        'public_stats',
        'clicks',
        'unique_clicks',
        'last_clicked',
        'leads',
        'sales',
        'sale_amount',
        'program_id',
        'partner_id',
        'comments',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'archived' => 'boolean',
            'expires_at' => 'datetime',
            'track_conversion' => 'boolean',
            'proxy' => 'boolean',
            'rewrite' => 'boolean',
            'link_retention_cleanup_disabled_at' => 'datetime',
            'do_index' => 'boolean',
            'geo' => 'array',
            'test_variants' => 'array',
            'test_started_at' => 'datetime',
            'test_completed_at' => 'datetime',
            'public_stats' => 'boolean',
            'clicks' => 'integer',
            'last_clicked' => 'datetime',
            'leads' => 'integer',
            'sales' => 'integer',
            'sale_amount' => 'integer',
        ];
    }

    /**
     * Get the user that owns the link.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project that owns the link.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
