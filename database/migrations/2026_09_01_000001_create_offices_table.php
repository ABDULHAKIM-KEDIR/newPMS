<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('offices')) {
            // Table already created (e.g. by a pre-merge hotfix); just mark done.
            return;
        }

        Schema::create('offices', function (Blueprint $table) {
            $table->id('office_id');
            $table->string('office_name', 150)->unique();
            $table->string('office_code', 20)->unique();
            $table->text('description')->nullable();
            $table->foreignId('head_user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->string('status', 20)->default('Active'); // Active / Inactive
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
