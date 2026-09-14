<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds multi-office support to the existing schema. All foreign keys are
 * nullable so an existing installation upgrades safely without data loss.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Every user can belong to an office.
        if (! Schema::hasColumn('users', 'office_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('office_id')
                    ->nullable()
                    ->after('phone')
                    ->constrained('offices', 'office_id')
                    ->nullOnDelete();
            });
        }

        // Every team belongs to an office.
        if (! Schema::hasColumn('teams', 'office_id')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->foreignId('office_id')
                    ->nullable()
                    ->after('team_leader_id')
                    ->constrained('offices', 'office_id')
                    ->nullOnDelete();
            });
        }

        // A project has one primary (financially responsible) office.
        if (! Schema::hasColumn('projects', 'primary_office_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->foreignId('primary_office_id')
                    ->nullable()
                    ->after('team_id')
                    ->constrained('offices', 'office_id')
                    ->nullOnDelete();
            });
        }

        // Cross-office collaboration: many-to-many participating offices.
        if (! Schema::hasTable('project_office')) {
            Schema::create('project_office', function (Blueprint $table) {
                $table->id('project_office_id');
                $table->foreignId('project_id')->constrained('projects', 'project_id')->cascadeOnDelete();
                $table->foreignId('office_id')->constrained('offices', 'office_id')->cascadeOnDelete();
                $table->string('participation_type', 30)->default('participating'); // participating / supporting / consulting
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['project_id', 'office_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_office');

        Schema::table('projects', fn (Blueprint $table) => $table->dropConstrainedForeignId('primary_office_id'));
        Schema::table('teams', fn (Blueprint $table) => $table->dropConstrainedForeignId('office_id'));
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('office_id'));
    }
};
