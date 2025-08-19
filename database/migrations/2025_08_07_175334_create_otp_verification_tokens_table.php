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
        Schema::create('otp_verification_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('identifier'); // email address
            $table->string('token', 6); // 6-digit OTP code
            $table->timestamp('expires'); // expiration time
            $table->timestamps();

            // Indexes for performance
            $table->index(['identifier', 'token']);
            $table->index('expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_verification_tokens');
    }
};
