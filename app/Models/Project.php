<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Project extends Model
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
        'monthly_clicks',
        'current_month',
        'active_links',
        'stripe_customer_id',
        'stripe_subscription_id',
        'plan',
        'billing_cycle_start',
        'billing_enabled',
        'trial_ends_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
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
        ];
    }

    /**
     * Get the links for the project.
     */
    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    /**
     * Get the domains for the project.
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    /**
     * Get the users for the project.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_users')
            ->withPivot(['role', 'created_at', 'updated_at'])
            ->withTimestamps();
    }

    /**
     * Get the invoices for the project.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Check if project is on a paid plan
     */
    public function isPaidPlan(): bool
    {
        return ! in_array($this->plan, ['free', 'trial']);
    }

    /**
     * Check if project is on trial
     */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if trial has expired
     */
    public function isTrialExpired(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    /**
     * Check if payment has failed
     */
    public function hasPaymentFailed(): bool
    {
        return $this->payment_failed_at !== null;
    }
}
