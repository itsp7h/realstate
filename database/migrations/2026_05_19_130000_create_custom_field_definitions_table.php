<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->enum('form_type', ['building', 'unit']);
            $table->string('name', 100);        // snake_case slug, e.g. "parking_level"
            $table->string('label', 255);       // display label, e.g. "Parking Level"
            $table->enum('field_type', ['text', 'number', 'date', 'select', 'textarea']);
            $table->json('options')->nullable(); // for select: ["Option A", "Option B"]
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['form_type', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_definitions');
    }
};
