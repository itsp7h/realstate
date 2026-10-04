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
        Schema::create('branding_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name', 100)->nullable();
            $table->string('company_name', 150)->nullable();
            $table->string('tagline', 150)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->string('favicon_path', 255)->nullable();
            $table->string('primary_color', 7)->nullable();
            $table->string('secondary_color', 7)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branding_settings');
    }
};
