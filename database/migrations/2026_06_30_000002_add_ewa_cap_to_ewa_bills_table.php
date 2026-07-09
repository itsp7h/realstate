<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ewa_bills', function (Blueprint $table) {
            $table->decimal('ewa_cap', 10, 3)->nullable()->after('subsidy');
            $table->decimal('tenant_portion', 10, 3)->nullable()->after('ewa_cap');
        });
    }

    public function down(): void
    {
        Schema::table('ewa_bills', function (Blueprint $table) {
            $table->dropColumn(['ewa_cap', 'tenant_portion']);
        });
    }
};
