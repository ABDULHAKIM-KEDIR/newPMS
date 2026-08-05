<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id('dependency_id');
            $table->foreignId('task_id')->constrained('tasks', 'task_id')->cascadeOnDelete();
            $table->foreignId('depends_on_task_id')->constrained('tasks', 'task_id')->cascadeOnDelete();
            $table->string('dependency_type', 30)->default('Finish-to-Start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};
