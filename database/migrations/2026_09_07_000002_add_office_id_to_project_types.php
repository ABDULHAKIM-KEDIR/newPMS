<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Office-scoped project types.
 *
 * A project type may optionally belong to one office (office_id set).
 * Types with office_id = NULL remain global and available to every office.
 * When a project picks a primary office, the type list narrows to that
 * office's types plus all global ones.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('project_types', 'office_id')) {
            Schema::table('project_types', function (Blueprint $table) {
                $table->foreignId('office_id')->nullable()
                    ->after('description')->constrained('offices', 'office_id')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('project_types', 'office_id')) {
            Schema::table('project_types', function (Blueprint $table) {
                $table->dropConstrainedForeignId('office_id');
            });
        }
    }
};
