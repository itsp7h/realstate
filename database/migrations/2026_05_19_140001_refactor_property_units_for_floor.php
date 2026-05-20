<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add floor_id FK (guard against partial previous run)
        if (!Schema::hasColumn('property_units', 'floor_id')) {
            Schema::table('property_units', function (Blueprint $table) {
                $table->foreignId('floor_id')->nullable()->after('building_id')
                      ->constrained('floors')->nullOnDelete();
            });
        }

        // Drop the index on floor_name before dropping the column (SQLite)
        if (Schema::hasColumn('property_units', 'floor_name')) {
            // SQLite stores index names — attempt drop, ignore if already gone
            try {
                Schema::table('property_units', function (Blueprint $table) {
                    $table->dropIndex('property_units_floor_name_index');
                });
            } catch (\Exception $e) {
                // Index may have already been dropped or doesn't exist under that name
            }
            Schema::table('property_units', function (Blueprint $table) {
                $table->dropColumn('floor_name');
            });
        }

        // Drop redundant columns one at a time (SQLite requirement)
        foreach (['floor_code', 'total_no_of_units', 'total_no_of_floors', 'total_no_of_blocks', 'block_name', 'block_code', 'building_no_2'] as $col) {
            if (Schema::hasColumn('property_units', $col)) {
                Schema::table('property_units', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('property_units', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Floor::class);
            $table->dropColumn('floor_id');
        });

        Schema::table('property_units', function (Blueprint $table) {
            $table->string('floor_name', 100)->nullable()->index();
            $table->string('floor_code', 50)->nullable();
            $table->integer('total_no_of_units')->nullable();
            $table->integer('total_no_of_floors')->nullable();
            $table->integer('total_no_of_blocks')->nullable();
            $table->string('block_name', 100)->nullable();
            $table->string('block_code', 50)->nullable();
            $table->integer('building_no_2')->nullable();
        });
    }
};
