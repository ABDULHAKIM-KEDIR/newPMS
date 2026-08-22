<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add department and avatar to users if not present
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'department')) {
                $table->string('department', 100)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar', 255)->nullable()->after('department');
            }
        });

        // 2. Add status to teams
        Schema::table('teams', function (Blueprint $table) {
            if (! Schema::hasColumn('teams', 'status')) {
                $table->string('status', 20)->default('Active')->after('description');
            }
        });

        // 3. Add client and priority to projects
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'client')) {
                $table->string('client', 150)->nullable()->after('description');
            }
            if (! Schema::hasColumn('projects', 'priority')) {
                $table->string('priority', 20)->default('Medium')->after('end_date');
            }
            if (! Schema::hasColumn('projects', 'progress')) {
                $table->integer('progress')->default(0)->after('priority');
            }
        });

        // 4. Create project_teams pivot table (Many-to-Many Project <-> Team)
        if (! Schema::hasTable('project_teams')) {
            Schema::create('project_teams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects', 'project_id')->cascadeOnDelete();
                $table->foreignId('team_id')->constrained('teams', 'team_id')->cascadeOnDelete();
                $table->timestamp('assigned_date')->useCurrent();
                $table->unique(['project_id', 'team_id']);
            });
        }

        // 5. Add team_id, project_id, and progress to tasks
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('task_id')->constrained('projects', 'project_id')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'team_id')) {
                $table->foreignId('team_id')->nullable()->after('phase_id')->constrained('teams', 'team_id')->nullOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'progress')) {
                $table->integer('progress')->default(0)->after('priority');
            }
            if (! Schema::hasColumn('tasks', 'budget')) {
                $table->decimal('budget', 12, 2)->default(0)->after('progress');
            }
            if (! Schema::hasColumn('tasks', 'blocker_reason')) {
                $table->text('blocker_reason')->nullable()->after('budget');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_teams');

        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'team_id')) {
                $table->dropConstrainedForeignId('team_id');
            }
            if (Schema::hasColumn('tasks', 'project_id')) {
                $table->dropConstrainedForeignId('project_id');
            }
            if (Schema::hasColumn('tasks', 'blocker_reason')) {
                $table->dropColumn('blocker_reason');
            }
            if (Schema::hasColumn('tasks', 'budget')) {
                $table->dropColumn('budget');
            }
            if (Schema::hasColumn('tasks', 'progress')) {
                $table->dropColumn('progress');
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'progress')) {
                $table->dropColumn('progress');
            }
            if (Schema::hasColumn('projects', 'priority')) {
                $table->dropColumn('priority');
            }
            if (Schema::hasColumn('projects', 'client')) {
                $table->dropColumn('client');
            }
        });

        Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'avatar')) {
                $table->dropColumn('avatar');
            }
            if (Schema::hasColumn('users', 'department')) {
                $table->dropColumn('department');
            }
        });
    }
};
