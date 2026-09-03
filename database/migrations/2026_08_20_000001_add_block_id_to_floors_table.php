<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('floors', function (Blueprint $table) {
            $table->foreignId('block_id')->nullable()->after('building_id')
                  ->constrained('blocks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('floors', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Block::class);
            $table->dropColumn('block_id');
        });
    }
};
