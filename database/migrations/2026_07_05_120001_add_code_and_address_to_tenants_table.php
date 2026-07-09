<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('tenant_code', 30)->nullable()->unique()->after('id');
            $table->string('address', 500)->nullable()->after('nationality_country');
        });

        // Backfill a code for any tenants created before this column existed.
        DB::table('tenants')->whereNull('tenant_code')->orderBy('id')->get()->each(function ($tenant) {
            DB::table('tenants')->where('id', $tenant->id)->update([
                'tenant_code' => 'Tenant-' . str_pad((string) $tenant->id, 5, '0', STR_PAD_LEFT),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['tenant_code', 'address']);
        });
    }
};
