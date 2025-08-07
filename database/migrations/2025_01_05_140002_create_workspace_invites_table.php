<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Based on dub-main Prisma schema: ProjectInvite model
     *
     * This table manages pending invitations to workspaces.
     */
    public function up(): void
    {
        Schema::create('workspace_invites', function (Blueprint $table) {
            $table->id();

            // Invitation details
            $table->string('email');
            $table->timestamp('expires_at');
            $table->enum('role', ['owner', 'member'])->default('member');

            // Foreign key to workspace - using string for UUID reference
            $table->string('workspace_id');
            $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');

            // Timestamps
            $table->timestamps();

            // Ensure unique email-workspace combinations for pending invites
            $table->unique(['email', 'workspace_id']);

            // Indexes for performance
            $table->index('workspace_id');
            $table->index('expires_at');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_invites');
    }
};
