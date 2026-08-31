<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_types', function (Blueprint $table) {
            $table->id('project_type_id');
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'project_type_id')) {
                $table->foreignId('project_type_id')
                    ->nullable()
                    ->after('project_type')
                    ->constrained('project_types', 'project_type_id')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'project_type_id')) {
                $table->dropForeign(['project_type_id']);
                $table->dropColumn('project_type_id');
            }
        });

        Schema::dropIfExists('project_types');
    }
};
