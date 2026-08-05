<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_templates', function (Blueprint $table) {
            $table->id('template_id');
            $table->string('template_name', 100);
            $table->string('project_type', 50); // Software / Network-Infrastructure / Training & Consultancy
            $table->text('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_templates');
    }
};
