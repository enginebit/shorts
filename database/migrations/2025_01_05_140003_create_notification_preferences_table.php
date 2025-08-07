<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Based on dub-main Prisma schema: NotificationPreference model
     *
     * This table manages user notification preferences for each workspace.
     */
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();

            // Foreign key to workspace_users pivot table
            $table->foreignId('workspace_user_id')->constrained()->onDelete('cascade');

            // Notification preferences (all default to true)
            $table->boolean('link_usage_summary')->default(true);
            $table->boolean('domain_configuration_updates')->default(true);
            $table->boolean('new_partner_sale')->default(true);
            $table->boolean('new_partner_application')->default(true);

            // Timestamps
            $table->timestamps();

            // Ensure unique preferences per workspace user
            $table->unique('workspace_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
