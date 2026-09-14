<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('offices')) {
            Schema::table('offices', function (Blueprint $table) {
                if (! Schema::hasColumn('offices', 'parent_office_id')) {
                    $table->foreignId('parent_office_id')->nullable()->after('office_id')
                        ->constrained('offices', 'office_id')->nullOnDelete();
                }
                if (! Schema::hasColumn('offices', 'unit_type')) {
                    $table->string('unit_type', 50)->default('Office')->after('office_code');
                }
            });
        }

        if (Schema::hasTable('teams')) {
            Schema::table('teams', function (Blueprint $table) {
                if (! Schema::hasColumn('teams', 'parent_team_id')) {
                    $table->foreignId('parent_team_id')->nullable()->after('team_id')
                        ->constrained('teams', 'team_id')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'is_global')) {
                    $table->boolean('is_global')->default(false)->after('office_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_global')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_global');
            });
        }

        if (Schema::hasTable('teams') && Schema::hasColumn('teams', 'parent_team_id')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->dropConstrainedForeignId('parent_team_id');
            });
        }

        if (Schema::hasTable('offices')) {
            Schema::table('offices', function (Blueprint $table) {
                if (Schema::hasColumn('offices', 'parent_office_id')) {
                    $table->dropConstrainedForeignId('parent_office_id');
                }
                if (Schema::hasColumn('offices', 'unit_type')) {
                    $table->dropColumn('unit_type');
                }
            });
        }
    }
};
