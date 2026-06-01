<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->string('floor_name', 100);
            $table->string('floor_code', 50)->nullable();
            $table->string('block_name', 100)->nullable();
            $table->string('block_code', 50)->nullable();
            $table->integer('total_no_of_units')->nullable();
            $table->timestamps();
            $table->unique(['building_id', 'floor_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('floors');
    }
};
