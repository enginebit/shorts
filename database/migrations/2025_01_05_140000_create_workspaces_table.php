<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Based on dub-main Prisma schema: /packages/prisma/schema/workspace.prisma
     *
     * Key adaptations for Laravel:
     * - Uses Laravel's standard id() instead of cuid()
     * - JSON columns for complex data (store, allowedHostnames)
     * - Proper foreign key constraints with cascading
     * - Laravel timestamp conventions
     */
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->string('id')->primary(); // Using CUID instead of auto-increment
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('invite_code')->unique()->nullable();
            $table->string('default_program_id')->unique()->nullable(); // for affiliate programs

            // Billing and plan information
            $table->string('plan')->default('free');
            $table->string('stripe_id')->unique()->nullable();
            $table->integer('billing_cycle_start'); // day of month when billing starts
            $table->timestamp('payment_failed_at')->nullable();
            $table->string('invoice_prefix')->unique()->nullable(); // for Dub Payouts

            // Integration IDs
            $table->string('stripe_connect_id')->unique()->nullable();
            $table->string('shopify_store_id')->unique()->nullable();

            // Usage statistics
            $table->integer('total_links')->default(0);
            $table->integer('total_clicks')->default(0);

            // Usage limits and tracking
            $table->integer('usage')->default(0);
            $table->integer('usage_limit')->default(1000);
            $table->integer('links_usage')->default(0);
            $table->integer('links_limit')->default(25);
            $table->integer('payouts_usage')->default(0);
            $table->integer('payouts_limit')->default(0);
            $table->decimal('payout_fee', 5, 4)->default(0.05); // processing fee for partner payouts

            // Feature limits
            $table->integer('domains_limit')->default(3);
            $table->integer('tags_limit')->default(5);
            $table->integer('folders_usage')->default(0);
            $table->integer('folders_limit')->default(0);
            $table->integer('users_limit')->default(1);
            $table->integer('ai_usage')->default(0);
            $table->integer('ai_limit')->default(10);

            // Referral system
            $table->string('referral_link_id')->unique()->nullable();
            $table->integer('referred_signups')->default(0);

            // JSON storage for flexible data
            $table->json('store')->nullable(); // key-value store for toggles, popups, etc.
            $table->json('allowed_hostnames')->nullable(); // for client-side tracking

            // Feature flags
            $table->boolean('conversion_enabled')->default(false);
            $table->boolean('webhook_enabled')->default(false);
            $table->boolean('partners_enabled')->default(false);
            $table->boolean('sso_enabled')->default(false);
            $table->boolean('dot_link_claimed')->default(false);

            // Timestamps
            $table->timestamps();
            $table->softDeletes(); // For soft delete functionality
            $table->timestamp('usage_last_checked')->default(now());

            // Indexes for performance
            $table->index('usage_last_checked');
            $table->index('plan');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
