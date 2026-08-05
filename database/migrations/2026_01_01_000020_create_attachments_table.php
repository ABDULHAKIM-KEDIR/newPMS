<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id('attachment_id');
            $table->string('entity_type', 30); // Task, Project, Deliverable
            $table->unsignedBigInteger('entity_id');
            $table->string('file_name', 255);
            $table->string('file_path', 255);
            $table->foreignId('uploaded_by')->constrained('users', 'user_id');
            $table->timestamp('uploaded_at')->useCurrent();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
