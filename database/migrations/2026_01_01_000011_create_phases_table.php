<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('phases', function (Blueprint $table) {
            $table->id('phase_id');
            $table->foreignId('project_id')->constrained('projects', 'project_id')->cascadeOnDelete();
            $table->string('phase_name', 100); // Initiation, Planning, Execution, Monitoring, Closure
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('duration')->nullable(); // days
            $table->string('status', 30)->default('Not started');
            $table->integer('sequence_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phases');
    }
};
