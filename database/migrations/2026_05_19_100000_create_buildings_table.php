<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('property_name');
            $table->string('property_code', 10)->unique();
            $table->string('type_of_ownership', 100)->nullable();
            $table->string('property_type', 100)->nullable();
            $table->string('land_lord_name')->nullable();
            $table->integer('building_no')->nullable();
            $table->string('road')->nullable();
            $table->integer('block')->nullable();
            $table->string('area')->nullable();
            $table->string('city')->nullable();
            $table->integer('total_no_of_blocks')->nullable();
            $table->integer('total_no_of_floors')->nullable();
            $table->integer('total_no_of_units')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};
