<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id('payment_id');
                $table->foreignId('project_id')->constrained('projects', 'project_id')->cascadeOnDelete();
                $table->foreignId('phase_id')->nullable()->constrained('phases', 'phase_id')->nullOnDelete();
                $table->foreignId('task_id')->nullable()->constrained('tasks', 'task_id')->nullOnDelete();
                $table->decimal('amount', 12, 2);
                $table->date('payment_date');
                $table->string('recipient', 255);
                $table->string('payment_status', 30)->default('Completed'); // Completed / Pending / Approved / Cancelled
                $table->string('reference_number', 100)->nullable();
                $table->text('description')->nullable();
                $table->string('sop_process', 150)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
                $table->timestamps();

                $table->index(['project_id', 'payment_status']);
                $table->index(['task_id']);
                $table->index(['phase_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
