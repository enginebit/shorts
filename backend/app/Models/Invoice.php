<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Invoice Model
 *
 * Represents billing invoices from Stripe
 * following dub-main patterns from invoice.prisma
 */
final class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'stripe_invoice_id',
        'stripe_customer_id',
        'stripe_subscription_id',
        'number',
        'status',
        'amount_due',
        'amount_paid',
        'amount_remaining',
        'currency',
        'invoice_date',
        'due_date',
        'paid_at',
        'voided_at',
        'period_start',
        'period_end',
        'description',
        'line_items',
        'hosted_invoice_url',
        'invoice_pdf',
    ];

    protected $casts = [
        'amount_due' => 'integer',
        'amount_paid' => 'integer',
        'amount_remaining' => 'integer',
        'invoice_date' => 'datetime',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'line_items' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the project that owns the invoice
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Check if invoice is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'open' &&
               $this->due_date &&
               $this->due_date->isPast();
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount_due / 100, 2);
    }

    /**
     * Get formatted amount paid
     */
    public function getFormattedAmountPaidAttribute(): string
    {
        return '$' . number_format($this->amount_paid / 100, 2);
    }

    /**
     * Scope for paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for unpaid invoices
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['open', 'draft']);
    }

    /**
     * Scope for overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'open')
                    ->where('due_date', '<', now());
    }
}
