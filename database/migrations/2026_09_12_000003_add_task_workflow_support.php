<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->boolean('is_locked')->default(false)->after('blocker_reason');
            $table->timestamp('locked_at')->nullable()->after('is_locked');
            $table->foreignId('locked_by')->nullable()->after('locked_at')
                ->constrained('users', 'user_id')->nullOnDelete();
            $table->index(['parent_task_id', 'project_id']);
        });

        Schema::create('task_assignments', function (Blueprint $table): void {
            $table->id('task_assignment_id');
            $table->foreignId('task_id')->constrained('tasks', 'task_id')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
            $table->string('response_reason', 1000)->nullable();
            $table->unique(['task_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        DB::table('tasks')
            ->whereNotNull('assigned_to')
            ->orderBy('task_id')
            ->each(function (object $task): void {
                DB::table('task_assignments')->insertOrIgnore([
                    'task_id' => $task->task_id,
                    'user_id' => $task->assigned_to,
                    'status' => 'accepted',
                    'assigned_at' => $task->created_at ?? now(),
                    'responded_at' => $task->created_at ?? now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignments');

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('locked_by');
            $table->dropIndex(['parent_task_id', 'project_id']);
            $table->dropColumn(['is_locked', 'locked_at']);
        });
    }
};
