<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('remarks', 500)->nullable()->after('notes');
        });

        Schema::table('ewa_bills', function (Blueprint $table) {
            $table->string('remarks', 500)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });

        Schema::table('ewa_bills', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }
};
