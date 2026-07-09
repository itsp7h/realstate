<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->boolean('vat_enabled')->default(false)->after('custom_fields');
            $table->decimal('vat_rate', 5, 2)->default(0)->after('vat_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn(['vat_enabled', 'vat_rate']);
        });
    }
};
