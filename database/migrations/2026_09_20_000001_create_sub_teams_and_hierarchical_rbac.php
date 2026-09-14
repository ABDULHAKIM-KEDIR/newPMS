<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hierarchical RBAC: Department -> Office -> Project -> Team -> [optional] Sub-Team.
 *
 * Team remains the mandatory operational assignment scope; Sub-Team is an
 * optional refinement. This migration creates the sub_teams table and adds
 * the nullable sub_team_id to tasks and team members so a task/membership
 * can always fall back to its team.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Leader foreign keys on the parent hierarchy levels. Most already
        // exist; add them defensively where a legacy install lacks one.
        if (! Schema::hasColumn('departments', 'head_user_id')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->foreignId('head_user_id')->nullable()->after('parent_department_id')
                    ->constrained('users', 'user_id')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('offices', 'head_user_id')) {
            Schema::table('offices', function (Blueprint $table) {
                $table->foreignId('head_user_id')->nullable()->after('department_id')
                    ->constrained('users', 'user_id')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('projects', 'project_manager_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->foreignId('project_manager_id')->nullable()->after('team_id')
                    ->constrained('users', 'user_id')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('teams', 'team_leader_id')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->foreignId('team_leader_id')->nullable()->after('team_name')
                    ->constrained('users', 'user_id')->nullOnDelete();
            });
        }

        Schema::create('sub_teams', function (Blueprint $table) {
            $table->id('sub_team_id');
            $table->foreignId('team_id')->constrained('teams', 'team_id')->cascadeOnDelete();
            $table->string('sub_team_name', 100);
            $table->foreignId('lead_user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('Active');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('sub_team_id')->nullable()->after('team_id')
                ->constrained('sub_teams', 'sub_team_id')->nullOnDelete();
        });

        Schema::table('team_members', function (Blueprint $table) {
            $table->foreignId('sub_team_id')->nullable()->after('team_id')
                ->constrained('sub_teams', 'sub_team_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sub_team_id');
        });

        Schema::table('team_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sub_team_id');
        });

        Schema::dropIfExists('sub_teams');
    }
};
