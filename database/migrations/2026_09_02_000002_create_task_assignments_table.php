<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('task_assignments')) {
            Schema::create('task_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks', 'task_id')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
                $table->string('role_label', 100)->nullable(); // e.g. Frontend, Backend, UI/UX, Tester
                $table->string('acceptance_status', 30)->default('Pending Acceptance'); // Pending Acceptance / Accepted / Rejected
                $table->text('rejection_reason')->nullable();
                $table->foreignId('assigned_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->timestamps();

                $table->unique(['task_id', 'user_id']);
            });
        }

        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (! Schema::hasColumn('tasks', 'is_locked')) {
                    $table->boolean('is_locked')->default(false);
                }
                if (! Schema::hasColumn('tasks', 'locked_at')) {
                    $table->timestamp('locked_at')->nullable();
                }
                if (! Schema::hasColumn('tasks', 'locked_by')) {
                    $table->foreignId('locked_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
                }
                if (! Schema::hasColumn('tasks', 'lock_reason')) {
                    $table->text('lock_reason')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (Schema::hasColumn('tasks', 'locked_by')) {
                    $table->dropConstrainedForeignId('locked_by');
                }
                if (Schema::hasColumn('tasks', 'is_locked')) {
                    $table->dropColumn('is_locked');
                }
                if (Schema::hasColumn('tasks', 'locked_at')) {
                    $table->dropColumn('locked_at');
                }
                if (Schema::hasColumn('tasks', 'lock_reason')) {
                    $table->dropColumn('lock_reason');
                }
            });
        }

        Schema::dropIfExists('task_assignments');
    }
};
