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
        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary(); // Using CUID instead of auto-increment
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_machine')->default(false);

            // Password data
            $table->string('password_hash')->nullable();
            $table->integer('invalid_login_attempts')->default(0);
            $table->timestamp('locked_at')->nullable();

            // Additional user data following dub-main schema
            $table->boolean('subscribed')->default(true); // email subscription
            $table->string('source')->nullable(); // where the user came from
            $table->string('default_workspace')->nullable(); // slug of the user's default workspace
            $table->string('default_partner_id')->nullable(); // the user's default partner ID

            $table->timestamps();

            // Indexes following dub-main patterns
            $table->index('source');
            $table->index('default_workspace');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();

            // Foreign key constraint for user_id
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
