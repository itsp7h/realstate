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
        Schema::table('lease_contracts', function (Blueprint $table) {
            $table->boolean('vat_enabled')->default(false)->after('service_amount_bd_excl_vat');
            $table->decimal('vat_rate', 5, 2)->default(0)->after('vat_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lease_contracts', function (Blueprint $table) {
            $table->dropColumn(['vat_enabled', 'vat_rate']);
        });
    }
};
