<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Domain extends Model
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
        'slug',
        'verified',
        'placeholder',
        'expired_url',
        'not_found_url',
        'primary',
        'archived',
        'last_checked',
        'logo',
        'link_retention_days',
        'apple_app_site_association',
        'asset_links',
        'project_id',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'primary' => 'boolean',
            'archived' => 'boolean',
            'last_checked' => 'datetime',
            'link_retention_days' => 'integer',
            'apple_app_site_association' => 'array',
            'asset_links' => 'array',
        ];
    }

    /**
     * Get the project that owns the domain.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
