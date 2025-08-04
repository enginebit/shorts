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
        Schema::create('links', function (Blueprint $table) {
            $table->string('id')->primary(); // Using CUID instead of auto-increment
            $table->string('domain'); // domain of the link (e.g. dub.sh)
            $table->string('key'); // key of the link (e.g. /github)
            $table->longText('url'); // target url (e.g. https://github.com/dubinc/dub)
            $table->string('short_link', 400)->unique(); // full short link
            $table->boolean('archived')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->text('expired_url')->nullable(); // URL to redirect when expired
            $table->string('password')->nullable(); // password to access the link
            $table->boolean('track_conversion')->default(false);

            // Proxy and OG data
            $table->boolean('proxy')->default(false); // Use custom OG tags
            $table->string('title')->nullable(); // OG title
            $table->string('description', 280)->nullable(); // OG description
            $table->longText('image')->nullable(); // OG image
            $table->text('video')->nullable(); // OG video

            // UTM parameters
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();

            $table->boolean('rewrite')->default(false);
            $table->timestamp('link_retention_cleanup_disabled_at')->nullable();
            $table->boolean('do_index')->default(false); // don't index short links by default

            // Custom device targeting
            $table->text('ios')->nullable(); // custom link for iOS devices
            $table->text('android')->nullable(); // custom link for Android devices
            $table->json('geo')->nullable(); // custom link for specific countries

            // A/B Testing
            $table->json('test_variants')->nullable();
            $table->timestamp('test_started_at')->nullable();
            $table->timestamp('test_completed_at')->nullable();

            // Foreign keys
            $table->string('user_id')->nullable();
            $table->string('project_id')->nullable();
            $table->string('folder_id')->nullable();

            // External & tenant IDs
            $table->string('external_id')->nullable();
            $table->string('tenant_id')->nullable();

            // Statistics
            $table->boolean('public_stats')->default(false);
            $table->integer('clicks')->default(0);
            $table->timestamp('last_clicked')->nullable();
            $table->integer('leads')->default(0);
            $table->integer('sales')->default(0);
            $table->integer('sale_amount')->default(0); // in cents

            // Partner program
            $table->string('program_id')->nullable();
            $table->string('partner_id')->nullable();

            // Comments
            $table->text('comments')->nullable();

            $table->timestamps();

            // Indexes following dub-main patterns
            $table->unique(['domain', 'key']); // for getting a link by domain and key
            $table->unique(['project_id', 'external_id']); // for getting a link by externalId
            $table->index(['project_id', 'tenant_id']); // for filtering by tenantId
            $table->index(['project_id', 'folder_id', 'archived', 'created_at']); // most getLinksForWorkspace queries
            $table->index(['program_id', 'partner_id']); // for getting a referral link
            $table->index(['domain', 'created_at']); // for bulk link deletion workflows
            $table->index('folder_id'); // used in /api/folders
            $table->index('user_id'); // for relation to User table
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
