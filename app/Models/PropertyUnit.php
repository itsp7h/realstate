<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertyUnit extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'property_units';

    protected $fillable = [
        'building_id',
        'floor_id',
        // Property Level
        'property_name',
        'property_code',
        'type_of_ownership',
        'property_type',
        'land_lord_name',
        // Address
        'building_no',
        'road',
        'block',
        'area',
        'city',
        // Unit Level
        'unit_name',
        'description',
        'unit_type',
        'creation_date',
        'unit_condition',
        'view',
        'no_of_parkings_foc',
        // Area & Pricing
        'area_unit',
        'area_inside',
        'area_terrace',
        'rate_per_area_unit',
        'rent_per_month',
        'security_deposit_amount',
        // Legal
        'municipality_nos',
        // Utilities
        'electricity_installation_date',
        'electricity_meter_no',
        'water_installation_date',
        'water_meter_no',
        'electricity_ac_no',
        'custom_fields',
    ];

    protected $casts = [
        'creation_date'                 => 'date',
        'electricity_installation_date' => 'date',
        'water_installation_date'       => 'date',
        'building_no'                   => 'integer',
        'block'                         => 'integer',
        'no_of_parkings_foc'            => 'integer',
        'area_inside'                   => 'decimal:2',
        'area_terrace'                  => 'decimal:2',
        'rate_per_area_unit'            => 'decimal:2',
        'rent_per_month'                => 'decimal:2',
        'security_deposit_amount'       => 'decimal:2',
        'custom_fields'                 => 'array',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function scopeFilter($query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(function ($q) use ($search) {
                $q->where('unit_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('property_name', 'like', "%{$search}%");
            });
        });

        $query->when($filters['property_code'] ?? null, fn($q, $v) => $q->where('property_code', $v));
        $query->when($filters['unit_type'] ?? null,     fn($q, $v) => $q->where('unit_type', $v));
        $query->when($filters['unit_condition'] ?? null, fn($q, $v) => $q->where('unit_condition', $v));
    }
}
