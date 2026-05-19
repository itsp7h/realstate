<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('form_type', ['building', 'unit']);
            $table->enum('config_type', ['form', 'template']);
            $table->json('fields');  // ordered array of field config objects
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['form_type', 'config_type']); // one active config per type
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_configs');
    }
};
