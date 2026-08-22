<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A project can name a Project Manager — the person accountable for
        // delivery. They don't have to be the team leader; the team leader
        // runs the team, the project manager runs the project.
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('project_manager_id')->nullable()->after('team_id')
                ->constrained('users', 'user_id')->nullOnDelete();
        });

        // Team members on a project can carry a specialty (Designer,
        // Developer, Tester, ...) so tasks can be assigned flexibly to the
        // right person for the job, not just to whoever is on the team.
        Schema::table('project_member_roles', function (Blueprint $table) {
            $table->string('specialty', 100)->nullable()->after('role_id');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_manager_id');
        });

        Schema::table('project_member_roles', function (Blueprint $table) {
            $table->dropColumn('specialty');
        });
    }
};
