<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->tinyInteger('selected_quotation')->nullable()->after('quotation_3_file');
        });

        // Migrate old 'open' status to new workflow entry point
        DB::table('maintenance_requests')
            ->where('status', 'open')
            ->update(['status' => 'waiting_supervisor']);
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropColumn('selected_quotation');
        });
    }
};
