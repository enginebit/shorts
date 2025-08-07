<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->string('id')->primary(); // Using CUID instead of auto-increment
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('invite_code')->nullable()->unique();
            $table->string('default_program_id')->nullable()->unique(); // default affiliate program ID

            // Billing and subscription data
            $table->string('plan')->default('free');
            $table->string('stripe_id')->nullable()->unique(); // Stripe subscription ID
            $table->integer('billing_cycle_start'); // day of the month when billing cycle starts
            $table->timestamp('payment_failed_at')->nullable();
            $table->string('invoice_prefix')->nullable()->unique(); // for Dub Payouts

            // Integration IDs
            $table->string('stripe_connect_id')->nullable()->unique(); // for Stripe Integration
            $table->string('shopify_store_id')->nullable()->unique(); // for Shopify Integration

            // Usage statistics
            $table->integer('total_links')->default(0);
            $table->integer('total_clicks')->default(0);

            // Usage limits and quotas
            $table->integer('usage')->default(0);
            $table->integer('usage_limit')->default(1000);
            $table->integer('links_usage')->default(0);
            $table->integer('links_limit')->default(25);
            $table->integer('payouts_usage')->default(0);
            $table->integer('payouts_limit')->default(0);
            $table->decimal('payout_fee', 5, 4)->default(0.05); // processing fee for partner payouts

            $table->integer('domains_limit')->default(3);
            $table->integer('tags_limit')->default(5);
            $table->integer('folders_usage')->default(0);
            $table->integer('folders_limit')->default(0);
            $table->integer('users_limit')->default(1);
            $table->integer('ai_usage')->default(0);
            $table->integer('ai_limit')->default(10);

            // Referral data
            $table->string('referral_link_id')->nullable()->unique();
            $table->integer('referred_signups')->default(0);

            // JSON storage for flexible data
            $table->json('store')->nullable(); // General key-value store
            $table->json('allowed_hostnames')->nullable();

            // Feature flags
            $table->boolean('conversion_enabled')->default(false);
            $table->boolean('webhook_enabled')->default(false);
            $table->boolean('partners_enabled')->default(false);
            $table->boolean('sso_enabled')->default(false);
            $table->boolean('dot_link_claimed')->default(false);

            $table->timestamps();
            $table->timestamp('usage_last_checked')->default(now());

            // Soft deletes
            $table->softDeletes();

            // Indexes following dub-main patterns
            $table->index('usage_last_checked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
