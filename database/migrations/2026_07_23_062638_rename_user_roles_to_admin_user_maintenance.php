<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The old `role` column is an enum restricted to super_admin/admin/staff,
     * so new values can't be written into it directly — remap through a
     * temporary unconstrained column, then drop the old one and rename.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role_new', 20)->default('user')->after('role');
        });

        DB::statement("UPDATE users SET role_new = CASE WHEN role = 'staff' THEN 'user' ELSE 'admin' END");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_new', 'role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role_old', 20)->default('staff')->after('role');
        });

        DB::statement("UPDATE users SET role_old = CASE WHEN role = 'user' THEN 'staff' WHEN role = 'maintenance' THEN 'staff' ELSE 'admin' END");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_old', 'role');
        });
    }
};
