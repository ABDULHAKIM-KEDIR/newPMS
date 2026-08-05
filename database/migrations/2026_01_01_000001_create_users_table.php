<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('full_name', 150);
            $table->string('email', 150)->unique();
            $table->string('password_hash', 255);
            $table->string('phone', 20)->nullable();
            $table->string('status', 20)->default('Active'); // Active / Inactive
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
