<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrades the fixed directorate-role system to a fully dynamic,
 * scope-aware RBAC:
 *
 *  - Roles carry a scope (organization | project | team), may inherit from
 *    a parent role (single-parent tree, cycle-checked in the service layer)
 *    and are flagged as system roles (protected from deletion).
 *  - user_roles gains a polymorphic scope: NULL scope = organization-wide,
 *    (scope_type, scope_id) = role held only within that project/team.
 *  - project_teams gains access_level + a default project-scoped role, so
 *    multiple teams can collaborate on one project at configurable levels
 *    (Asana-style multi-team membership).
 *  - Permissions gain a group column for the admin permission matrix UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'scope')) {
                $table->string('scope', 20)->default('organization')->after('role_name');
            }
            if (! Schema::hasColumn('roles', 'parent_role_id')) {
                $table->foreignId('parent_role_id')->nullable()->after('scope')
                    ->constrained('roles', 'role_id')->nullOnDelete();
            }
            if (! Schema::hasColumn('roles', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('parent_role_id');
            }
            if (! Schema::hasColumn('roles', 'rank')) {
                $table->unsignedSmallInteger('rank')->default(100)->after('is_system');
            }
        });

        Schema::table('user_roles', function (Blueprint $table) {
            if (! Schema::hasColumn('user_roles', 'scope_type')) {
                $table->string('scope_type', 30)->nullable()->after('role_id');
                $table->unsignedBigInteger('scope_id')->nullable()->after('scope_type');
                $table->index(['scope_type', 'scope_id']);
                $table->unique(['user_id', 'role_id', 'scope_type', 'scope_id'], 'user_roles_scope_unique');
            }
        });

        Schema::table('project_teams', function (Blueprint $table) {
            if (! Schema::hasColumn('project_teams', 'access_level')) {
                $table->string('access_level', 20)->default('contribute')->after('assigned_date');
            }
            if (! Schema::hasColumn('project_teams', 'project_role_id')) {
                $table->foreignId('project_role_id')->nullable()->after('access_level')
                    ->constrained('roles', 'role_id')->nullOnDelete();
            }
        });

        Schema::table('permissions', function (Blueprint $table) {
            if (! Schema::hasColumn('permissions', 'group')) {
                $table->string('group', 40)->default('General')->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_role_id');
            $table->dropColumn(['scope', 'is_system', 'rank']);
        });

        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropIndex(['scope_type', 'scope_id']);
            $table->dropUnique('user_roles_scope_unique');
            $table->dropColumn(['scope_type', 'scope_id']);
        });

        Schema::table('project_teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_role_id');
            $table->dropColumn('access_level');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('group');
        });
    }
};
