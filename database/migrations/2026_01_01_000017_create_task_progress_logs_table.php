<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_progress_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('task_id')->constrained('tasks', 'task_id')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users', 'user_id');
            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->text('remarks')->nullable();
            $table->timestamp('changed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_progress_logs');
    }
};
