<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add missing fields to workspaces table to match dub-main Project schema.
     *
     * Based on comprehensive schema analysis, these fields are missing:
     * - Billing integration fields (stripe_customer_id, stripe_subscription_id, trial_ends_at)
     * - Additional usage limits (monthly_clicks, current_month, active_links)
     * - Feature flags (billing_enabled)
     */
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            // Additional Stripe billing fields (missing from original schema)
            $table->string('stripe_customer_id')->nullable()->unique()->after('stripe_id');
            $table->string('stripe_subscription_id')->nullable()->unique()->after('stripe_customer_id');
            $table->timestamp('trial_ends_at')->nullable()->after('payment_failed_at');

            // Monthly usage tracking (for billing cycles)
            $table->integer('monthly_clicks')->default(0)->after('total_clicks');
            $table->integer('current_month')->default(date('n'))->after('monthly_clicks');
            $table->integer('active_links')->default(0)->after('current_month');

            // Additional feature flags
            $table->boolean('billing_enabled')->default(true)->after('dot_link_claimed');

            // Add indexes for performance
            $table->index('stripe_customer_id');
            $table->index('stripe_subscription_id');
            $table->index('trial_ends_at');
            $table->index('current_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['stripe_customer_id']);
            $table->dropIndex(['stripe_subscription_id']);
            $table->dropIndex(['trial_ends_at']);
            $table->dropIndex(['current_month']);

            // Drop columns
            $table->dropColumn([
                'stripe_customer_id',
                'stripe_subscription_id',
                'trial_ends_at',
                'monthly_clicks',
                'current_month',
                'active_links',
                'billing_enabled',
            ]);
        });
    }
};
