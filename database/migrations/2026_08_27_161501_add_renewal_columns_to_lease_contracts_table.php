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
        Schema::table('lease_contracts', function (Blueprint $table) {
            $table->timestamp('renewed_at')->nullable()->after('terminated_at');
            $table->foreignId('renewed_from_id')->nullable()->after('renewed_at')
                ->constrained('lease_contracts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lease_contracts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('renewed_from_id');
            $table->dropColumn('renewed_at');
        });
    }
};
