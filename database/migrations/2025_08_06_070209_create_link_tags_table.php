<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create link_tags pivot table following dub-main schema.
     *
     * Based on dub-main/packages/prisma/schema/tag.prisma:
     * - Many-to-many relationship between links and tags
     * - Each link can have multiple tags
     * - Each tag can be applied to multiple links
     */
    public function up(): void
    {
        Schema::create('link_tags', function (Blueprint $table) {
            $table->string('id')->primary(); // Using CUID format like dub-main
            $table->string('link_id');
            $table->string('tag_id');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('link_id')->references('id')->on('links')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');

            // Unique constraint: prevent duplicate link-tag associations
            $table->unique(['link_id', 'tag_id']);

            // Indexes for performance
            $table->index('tag_id');
            $table->index('link_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('link_tags');
    }
};
