<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Webhook Model
 *
 * Represents webhook endpoints for delivering events
 * following dub-main webhook patterns
 */
final class Webhook extends Model
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
        'project_id',
        'name',
        'url',
        'secret',
        'triggers',
        'disabled_at',
        'failure_count',
        'last_success_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'triggers' => 'array',
        'disabled_at' => 'datetime',
        'last_success_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * Get the project that owns the webhook.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Check if webhook is enabled
     */
    public function isEnabled(): bool
    {
        return $this->disabled_at === null;
    }

    /**
     * Check if webhook supports a specific trigger
     */
    public function supportsTrigger(string $trigger): bool
    {
        return in_array($trigger, $this->triggers ?? []);
    }

    /**
     * Disable the webhook
     */
    public function disable(): void
    {
        $this->update(['disabled_at' => now()]);
    }

    /**
     * Enable the webhook
     */
    public function enable(): void
    {
        $this->update(['disabled_at' => null]);
    }
}
