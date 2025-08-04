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
        Schema::table('projects', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('projects', 'stripe_customer_id')) {
                $table->string('stripe_customer_id')->nullable()->index();
            }
            if (!Schema::hasColumn('projects', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id')->nullable()->index();
            }
            if (!Schema::hasColumn('projects', 'billing_enabled')) {
                $table->boolean('billing_enabled')->default(false);
            }
            if (!Schema::hasColumn('projects', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_customer_id',
                'stripe_subscription_id',
                'billing_enabled',
                'trial_ends_at',
            ]);
        });
    }
};
