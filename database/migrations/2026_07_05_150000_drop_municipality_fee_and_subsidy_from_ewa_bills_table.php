<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ewa_bills', function (Blueprint $table) {
            if (Schema::hasColumn('ewa_bills', 'municipality_fee')) {
                $table->dropColumn('municipality_fee');
            }
            if (Schema::hasColumn('ewa_bills', 'subsidy')) {
                $table->dropColumn('subsidy');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ewa_bills', function (Blueprint $table) {
            $table->decimal('municipality_fee', 10, 3)->default(0);
            $table->decimal('subsidy', 10, 3)->default(0);
        });
    }
};
