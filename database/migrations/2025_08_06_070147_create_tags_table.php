<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create tags table following dub-main schema.
     *
     * Based on dub-main/packages/prisma/schema/tag.prisma:
     * - Tags belong to projects (workspaces in our case)
     * - Each tag has a name and color
     * - Tag names must be unique within a workspace
     */
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->string('id')->primary(); // Using CUID format like dub-main
            $table->string('name');
            $table->string('color')->default('#8B5CF6'); // Default purple color
            $table->string('workspace_id'); // Reference to workspaces (project in dub-main)
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');

            // Unique constraint: tag names must be unique within a workspace
            $table->unique(['workspace_id', 'name']);

            // Indexes for performance
            $table->index('workspace_id');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
