<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Supabase authentication fields to users table.
     *
     * This migration adds fields needed for Supabase JWT authentication
     * integration while preserving existing Laravel authentication.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Supabase user ID (UUID format)
            $table->string('supabase_id')->nullable()->unique()->after('id');

            // Store Supabase user metadata (app_metadata, user_metadata, etc.)
            $table->json('supabase_metadata')->nullable()->after('remember_token');

            // Add index for performance on Supabase ID lookups
            $table->index('supabase_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex(['supabase_id']);

            // Drop columns
            $table->dropColumn(['supabase_id', 'supabase_metadata']);
        });
    }
};
