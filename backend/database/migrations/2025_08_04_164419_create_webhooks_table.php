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
        Schema::create('webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Project relationship
            $table->uuid('project_id');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');

            // Webhook configuration
            $table->string('name');
            $table->string('url');
            $table->string('secret');
            $table->json('triggers'); // Array of webhook triggers

            // Status and monitoring
            $table->timestamp('disabled_at')->nullable();
            $table->integer('failure_count')->default(0);
            $table->timestamp('last_success_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes following dub-main patterns
            $table->index('project_id');
            $table->index(['project_id', 'disabled_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
