<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_deliverables', function (Blueprint $table) {
            $table->id('deliverable_id');
            $table->foreignId('project_id')->constrained('projects', 'project_id')->cascadeOnDelete();
            $table->string('deliverable_name', 150);
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('Not started');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_deliverables');
    }
};
