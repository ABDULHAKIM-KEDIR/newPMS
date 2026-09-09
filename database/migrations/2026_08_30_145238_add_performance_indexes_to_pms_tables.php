<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['status', 'assigned_to'], 'tasks_status_assigned_to_index');
            $table->index('end_date', 'tasks_end_date_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read'], 'notifications_user_id_is_read_index');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index('status', 'projects_status_index');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('user_id', 'audit_logs_user_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_status_assigned_to_index');
            $table->dropIndex('tasks_end_date_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_user_id_is_read_index');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_status_index');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_user_id_index');
        });
    }
};
