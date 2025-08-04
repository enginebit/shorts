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
        Schema::table('projects', function (Blueprint $table) {
            // Only add fields that don't already exist
            $table->integer('monthly_clicks')->default(0);
            $table->string('current_month')->nullable();
            $table->integer('active_links')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'monthly_clicks',
                'current_month',
                'active_links',
            ]);
        });
    }
};
