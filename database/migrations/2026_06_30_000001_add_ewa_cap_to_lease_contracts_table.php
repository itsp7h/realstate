<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lease_contracts', function (Blueprint $table) {
            $table->decimal('ewa_cap', 12, 3)->nullable()->after('security_deposit');
        });
    }

    public function down(): void
    {
        Schema::table('lease_contracts', function (Blueprint $table) {
            $table->dropColumn('ewa_cap');
        });
    }
};
