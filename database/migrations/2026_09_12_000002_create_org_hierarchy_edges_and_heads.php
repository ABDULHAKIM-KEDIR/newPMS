<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('org_unit_edges')) {
            Schema::create('org_unit_edges', function (Blueprint $table) {
                $table->id();
                $table->string('parent_type', 30);
                $table->unsignedBigInteger('parent_id');
                $table->string('child_type', 30);
                $table->unsignedBigInteger('child_id');
                $table->index(['parent_type', 'parent_id']);
                $table->index(['child_type', 'child_id']);
                $table->unique(['parent_type', 'parent_id', 'child_type', 'child_id'], 'org_unit_edges_unique');
            });
        }

        if (! Schema::hasTable('org_unit_heads')) {
            Schema::create('org_unit_heads', function (Blueprint $table) {
                $table->id();
                $table->string('headable_type', 100);
                $table->unsignedBigInteger('headable_id');
                $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
                $table->timestamps();
                $table->index(['headable_type', 'headable_id']);
                $table->unique(['headable_type', 'headable_id', 'user_id'], 'org_unit_heads_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('org_unit_heads');
        Schema::dropIfExists('org_unit_edges');
    }
};
