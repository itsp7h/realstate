<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->string('block_name', 100);
            $table->string('block_code', 50)->nullable();
            $table->integer('total_no_of_floors')->nullable();
            $table->timestamps();
            $table->unique(['building_id', 'block_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
