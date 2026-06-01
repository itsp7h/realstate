<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_units', function (Blueprint $table) {
            $table->foreignId('building_id')->nullable()->after('id')
                  ->constrained('buildings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('property_units', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Building::class);
            $table->dropColumn('building_id');
        });
    }
};
