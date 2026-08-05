<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id('task_id');
            $table->foreignId('phase_id')->constrained('phases', 'phase_id')->cascadeOnDelete();
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks', 'task_id')->nullOnDelete();
            $table->string('task_name', 150);
            $table->text('description')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->string('status', 30)->default('Pending'); // Pending / In Progress / Done
            $table->string('priority', 20)->default('Medium');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('duration')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
