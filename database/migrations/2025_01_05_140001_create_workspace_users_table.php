<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Based on dub-main Prisma schema: ProjectUsers model
     *
     * This table manages the many-to-many relationship between users and workspaces
     * with additional pivot data like roles and preferences.
     */
    public function up(): void
    {
        Schema::create('workspace_users', function (Blueprint $table) {
            $table->id();

            // Foreign keys - using string for UUID references
            $table->string('user_id');
            $table->string('workspace_id');

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');

            // Role in workspace
            $table->enum('role', ['owner', 'member'])->default('member');

            // User preferences and settings
            $table->string('default_folder_id')->nullable();
            $table->json('workspace_preferences')->nullable(); // user-specific workspace settings

            // Timestamps
            $table->timestamps();

            // Ensure unique user-workspace combinations
            $table->unique(['user_id', 'workspace_id']);

            // Indexes for performance
            $table->index('workspace_id');
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_users');
    }
};
