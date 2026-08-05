<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('change_requests', function (Blueprint $table) {
            $table->id('change_request_id');
            $table->foreignId('project_id')->constrained('projects', 'project_id')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users', 'user_id');
            $table->text('description');
            $table->string('status', 30)->default('Pending'); // Pending / Approved / Rejected
            $table->timestamp('requested_date')->useCurrent();
            $table->foreignId('approved_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamp('approved_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_requests');
    }
};
