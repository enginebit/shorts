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
        Schema::create('domains', function (Blueprint $table) {
            $table->string('id')->primary(); // Using CUID instead of auto-increment
            $table->string('slug')->unique();
            $table->boolean('verified')->default(false);
            $table->string('placeholder')->nullable();
            $table->longText('expired_url')->nullable(); // URL to redirect to for expired links
            $table->longText('not_found_url')->nullable(); // URL to redirect to for links that don't exist
            $table->boolean('primary')->default(false);
            $table->boolean('archived')->default(false);
            $table->timestamp('last_checked')->default(now());
            $table->string('logo')->nullable();
            $table->integer('link_retention_days')->nullable(); // default is null (links retained forever)
            $table->json('apple_app_site_association')->nullable();
            $table->json('asset_links')->nullable();

            // Foreign key
            $table->string('project_id')->nullable();

            $table->timestamps();

            // Indexes following dub-main patterns
            $table->index('project_id');
            $table->index('last_checked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
