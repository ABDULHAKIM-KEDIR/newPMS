<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair pass: some installations created the project_office pivot before
 * the timestamps columns were introduced. Add updated_at when missing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_office') && ! Schema::hasColumn('project_office', 'updated_at')) {
            Schema::table('project_office', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('project_office', 'updated_at')) {
            Schema::table('project_office', function (Blueprint $table) {
                $table->dropColumn('updated_at');
            });
        }
    }
};
