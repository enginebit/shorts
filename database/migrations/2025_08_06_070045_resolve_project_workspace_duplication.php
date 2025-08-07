<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Resolve the Project/Workspace duplication issue.
     *
     * Based on dub-main schema analysis:
     * - dub-main uses "Project" as the primary workspace entity
     * - Our Laravel implementation has both "projects" and "workspaces" tables
     * - We'll keep "workspaces" as primary since our auth system is built around it
     * - We'll remove the duplicate "projects" table after ensuring data consistency
     */
    public function up(): void
    {
        // First, check if we have any data in projects table that needs to be preserved
        $projectsExist = Schema::hasTable('projects');
        $workspacesExist = Schema::hasTable('workspaces');

        if ($projectsExist && $workspacesExist) {
            // Check if projects table has any data
            $projectCount = DB::table('projects')->count();

            if ($projectCount > 0) {
                // Log warning about data in projects table
                \Log::warning("Projects table contains {$projectCount} records. Manual data migration may be required.");

                // For safety, we'll rename the projects table instead of dropping it
                // First, drop foreign key constraints that reference projects table
                Schema::table('project_users', function (Blueprint $table) {
                    $table->dropForeign(['project_id']);
                });
                Schema::table('webhooks', function (Blueprint $table) {
                    $table->dropForeign(['project_id']);
                });
                Schema::table('invoices', function (Blueprint $table) {
                    $table->dropForeign(['project_id']);
                });

                Schema::rename('projects', 'projects_backup_'.date('Y_m_d_His'));
            } else {
                // Drop foreign key constraints first
                Schema::table('project_users', function (Blueprint $table) {
                    $table->dropForeign(['project_id']);
                });
                Schema::table('webhooks', function (Blueprint $table) {
                    $table->dropForeign(['project_id']);
                });
                Schema::table('invoices', function (Blueprint $table) {
                    $table->dropForeign(['project_id']);
                });

                // Safe to drop empty projects table
                Schema::dropIfExists('projects');
            }
        }

        // Ensure workspaces table is our primary entity
        // (It should already exist from our previous migrations)
        if (! $workspacesExist) {
            throw new \Exception('Workspaces table does not exist. Cannot resolve duplication.');
        }

        // Update any remaining references to ensure consistency
        // Note: domains and links should already reference project_id correctly per dub-main schema
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible due to potential data loss
        // Manual intervention would be required to restore the projects table
        throw new \Exception('This migration cannot be automatically reversed. Manual restoration required.');
    }
};
