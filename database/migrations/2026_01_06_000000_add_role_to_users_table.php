<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                /*
                 * Guests are public registrants who have no role yet.
                 * A System Administrator assigns the real role on approval.
                 */
                $table->string('role', 30)->default('guest')->after('password_hash');
            }

            if (Schema::hasColumn('users', 'status')) {
                $table->string('status', 20)->default('Pending')->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }

            if (Schema::hasColumn('users', 'status')) {
                $table->string('status', 20)->default('Active')->change();
            }
        });
    }
};
