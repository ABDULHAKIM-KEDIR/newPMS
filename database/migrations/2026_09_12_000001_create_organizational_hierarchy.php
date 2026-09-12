<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id('department_id');
                $table->string('department_name', 150);
                $table->string('department_code', 30)->unique();
                $table->foreignId('parent_department_id')->nullable()
                    ->constrained('departments', 'department_id')->nullOnDelete();
                $table->foreignId('head_user_id')->nullable()
                    ->constrained('users', 'user_id')->nullOnDelete();
                $table->string('status', 20)->default('Active');
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('offices', 'department_id')) {
            Schema::table('offices', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()
                    ->after('office_code')->constrained('departments', 'department_id')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('offices', 'parent_office_id')) {
            Schema::table('offices', function (Blueprint $table) {
                $table->foreignId('parent_office_id')->nullable()
                    ->after('department_id')->constrained('offices', 'office_id')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('projects', 'department_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()
                    ->after('primary_office_id')->constrained('departments', 'department_id')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('teams', 'parent_team_id')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->foreignId('parent_team_id')->nullable()
                    ->after('office_id')->constrained('teams', 'team_id')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('teams', 'parent_team_id')) {
            Schema::table('teams', fn (Blueprint $table) => $table->dropConstrainedForeignId('parent_team_id'));
        }

        if (Schema::hasColumn('projects', 'department_id')) {
            Schema::table('projects', fn (Blueprint $table) => $table->dropConstrainedForeignId('department_id'));
        }

        if (Schema::hasColumn('offices', 'parent_office_id')) {
            Schema::table('offices', fn (Blueprint $table) => $table->dropConstrainedForeignId('parent_office_id'));
        }

        if (Schema::hasColumn('offices', 'department_id')) {
            Schema::table('offices', fn (Blueprint $table) => $table->dropConstrainedForeignId('department_id'));
        }

        Schema::dropIfExists('departments');
    }
};
