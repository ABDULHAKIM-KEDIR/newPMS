<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('phase_budgets', function (Blueprint $table) {
            $table->id('budget_id');
            $table->foreignId('phase_id')->constrained('phases', 'phase_id')->cascadeOnDelete();
            $table->decimal('allocated_amount', 12, 2)->default(0);
            $table->decimal('spent_amount', 12, 2)->default(0);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phase_budgets');
    }
};
