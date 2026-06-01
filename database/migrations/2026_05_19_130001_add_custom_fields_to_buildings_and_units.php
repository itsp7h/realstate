<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('total_no_of_units');
        });
        Schema::table('property_units', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('electricity_ac_no');
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });
        Schema::table('property_units', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });
    }
};
