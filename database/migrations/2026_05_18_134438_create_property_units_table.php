<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_units', function (Blueprint $table) {
            $table->id();

            // Property Level
            $table->string('property_name');
            $table->string('property_code', 10)->index();
            $table->string('type_of_ownership', 100)->nullable();
            $table->string('property_type', 100)->nullable();
            $table->string('land_lord_name')->nullable();

            // Address
            $table->integer('building_no')->nullable();
            $table->string('road')->nullable();
            $table->integer('block')->nullable();
            $table->string('area')->nullable();
            $table->string('city')->nullable();

            // Block Level
            $table->integer('total_no_of_blocks')->nullable();
            $table->string('block_name', 100)->nullable();
            $table->string('block_code', 50)->nullable();
            $table->integer('building_no_2')->nullable();

            // Floor Level
            $table->integer('total_no_of_floors')->nullable();
            $table->string('floor_name', 100)->nullable()->index();
            $table->string('floor_code', 50)->nullable();

            // Unit Level
            $table->integer('total_no_of_units')->nullable();
            $table->string('unit_name')->index();
            $table->string('description')->nullable();
            $table->string('unit_type', 50)->nullable()->index();
            $table->date('creation_date')->nullable();
            $table->string('unit_condition', 100)->nullable()->index();
            $table->string('view', 100)->nullable();
            $table->integer('no_of_parkings_foc')->nullable();

            // Area & Pricing
            $table->string('area_unit', 20)->nullable();
            $table->decimal('area_inside', 10, 2)->nullable();
            $table->decimal('area_terrace', 10, 2)->nullable();
            $table->decimal('rate_per_area_unit', 10, 2)->nullable();
            $table->decimal('rent_per_month', 10, 2)->nullable();
            $table->decimal('security_deposit_amount', 10, 2)->nullable();

            // Legal
            $table->string('municipality_nos')->nullable();

            // Utilities
            $table->date('electricity_installation_date')->nullable();
            $table->string('electricity_meter_no', 100)->nullable();
            $table->date('water_installation_date')->nullable();
            $table->string('water_meter_no', 100)->nullable();
            $table->string('electricity_ac_no', 100)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_units');
    }
};
