<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_units', function (Blueprint $table) {
            $table->renameColumn('landlord_name', 'land_lord_name');
            $table->renameColumn('security_deposit', 'security_deposit_amount');
            $table->renameColumn('electricity_account_no', 'electricity_ac_no');
        });
    }

    public function down(): void
    {
        Schema::table('property_units', function (Blueprint $table) {
            $table->renameColumn('land_lord_name', 'landlord_name');
            $table->renameColumn('security_deposit_amount', 'security_deposit');
            $table->renameColumn('electricity_ac_no', 'electricity_account_no');
        });
    }
};
