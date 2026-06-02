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
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->string('quotation_1_file', 500)->nullable()->after('quotation_1');
            $table->string('quotation_2_file', 500)->nullable()->after('quotation_2');
            $table->string('quotation_3_file', 500)->nullable()->after('quotation_3');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropColumn(['quotation_1_file', 'quotation_2_file', 'quotation_3_file']);
        });
    }
};
