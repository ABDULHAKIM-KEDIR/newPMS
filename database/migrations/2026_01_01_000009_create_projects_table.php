<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id('project_id');
            $table->string('project_name', 150);
            $table->text('description')->nullable();
            $table->string('project_type', 50); // Software / Network-Infrastructure / Training & Consultancy
            $table->foreignId('team_id')->constrained('teams', 'team_id')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('project_templates', 'template_id')->nullOnDelete();
            $table->text('scope_statement')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 30)->default('Planning'); // project lifecycle status
            $table->foreignId('created_by')->constrained('users', 'user_id');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
