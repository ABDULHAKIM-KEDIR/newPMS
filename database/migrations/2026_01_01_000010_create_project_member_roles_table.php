<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_member_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects', 'project_id')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles', 'role_id')->cascadeOnDelete();
            $table->date('assigned_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_member_roles');
    }
};
