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
            // Add workspace_id to link projects to workspaces
            $table->string('workspace_id')->nullable()->after('id');
            $table->index('workspace_id');

            // Add foreign key constraint
            $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropIndex(['workspace_id']);
            $table->dropColumn('workspace_id');
        });
    }
};
