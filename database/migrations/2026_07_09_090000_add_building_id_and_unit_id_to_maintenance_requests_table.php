<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->foreignId('building_id')->nullable()->after('flat')->constrained('buildings')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->after('building_id')->constrained('property_units')->nullOnDelete();
        });

        $buildingMap = DB::table('buildings')->whereNotNull('property_name')->pluck('id', 'property_name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id]);

        $unitMap = DB::table('property_units')->whereNotNull('unit_name')->get(['id', 'unit_name', 'building_id'])
            ->groupBy(fn ($row) => $row->building_id)
            ->map(fn ($rows) => $rows->mapWithKeys(fn ($row) => [strtolower(trim($row->unit_name)) => $row->id]));

        DB::table('maintenance_requests')->select('id', 'property', 'flat')->orderBy('id')->each(function ($request) use ($buildingMap, $unitMap) {
            $buildingId = $buildingMap->get(strtolower(trim($request->property ?? '')));
            if (! $buildingId) {
                return;
            }

            $unitId = $unitMap->get($buildingId)?->get(strtolower(trim($request->flat ?? '')));

            DB::table('maintenance_requests')->where('id', $request->id)->update([
                'building_id' => $buildingId,
                'unit_id'     => $unitId,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropConstrainedForeignId('building_id');
        });
    }
};
