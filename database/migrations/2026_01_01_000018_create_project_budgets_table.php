<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_budgets', function (Blueprint $table) {
            $table->id('budget_id');
            $table->foreignId('project_id')->constrained('projects', 'project_id')->cascadeOnDelete();
            $table->decimal('allocated_amount', 12, 2)->default(0);
            $table->decimal('spent_amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('ETB');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_budgets');
    }
};
