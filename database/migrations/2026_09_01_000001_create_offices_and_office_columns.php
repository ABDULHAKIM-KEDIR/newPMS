<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-office support.
 *
 * - `offices` catalogue table (idempotent-safe, additive only).
 * - Nullable `office_id` on users and teams so existing rows keep working.
 * - Nullable `primary_office_id` on projects.
 * - `project_office` pivot for cross-office participation.
 *
 * Additive only — never touches existing data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('offices')) {
            Schema::create('offices', function (Blueprint $table) {
                $table->id('office_id');
                $table->string('office_name', 150)->unique();
                $table->string('office_code', 20)->unique();
                $table->text('description')->nullable();
                $table->foreignId('head_user_id')->nullable()
                    ->constrained('users', 'user_id')->nullOnDelete();
                $table->string('status', 20)->default('Active');
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('users', 'office_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('office_id')->nullable()
                    ->after('status')->constrained('offices', 'office_id')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('teams', 'office_id')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->foreignId('office_id')->nullable()
                    ->after('team_leader_id')->constrained('offices', 'office_id')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('projects', 'primary_office_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->foreignId('primary_office_id')->nullable()
                    ->constrained('offices', 'office_id')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('project_office')) {
            Schema::create('project_office', function (Blueprint $table) {
                $table->id('project_office_id');
                $table->foreignId('project_id')->constrained('projects', 'project_id')->cascadeOnDelete();
                $table->foreignId('office_id')->constrained('offices', 'office_id')->cascadeOnDelete();
                $table->string('participation_type', 30)->default('participating'); // primary / participating
                $table->timestamps();

                $table->unique(['project_id', 'office_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_office');

        if (Schema::hasColumn('projects', 'primary_office_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropConstrainedForeignId('primary_office_id');
            });
        }

        if (Schema::hasColumn('teams', 'office_id')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->dropConstrainedForeignId('office_id');
            });
        }

        if (Schema::hasColumn('users', 'office_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('office_id');
            });
        }

        Schema::dropIfExists('offices');
    }
};
