<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Building extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'buildings';

    protected $fillable = [
        'property_name',
        'property_code',
        'type_of_ownership',
        'property_type',
        'land_lord_name',
        'company_name',
        'building_no',
        'road',
        'block',
        'area',
        'city',
        'total_no_of_blocks',
        'total_no_of_floors',
        'total_no_of_units',
        'custom_fields',
        'vat_enabled',
        'vat_rate',
    ];

    protected $casts = [
        'building_no'        => 'integer',
        'block'              => 'integer',
        'total_no_of_blocks' => 'integer',
        'total_no_of_floors' => 'integer',
        'total_no_of_units'  => 'integer',
        'custom_fields'      => 'array',
        'vat_enabled'        => 'boolean',
        'vat_rate'           => 'decimal:2',
    ];

    public function units()
    {
        return $this->hasMany(PropertyUnit::class);
    }

    /**
     * A unit counts as occupied while it has any non-terminated lease,
     * regardless of whether that lease's end date has passed — expiry
     * alone just means the agreement needs renewal, not that the tenant
     * has moved out. Only an explicit termination frees up the unit.
     */
    public function occupiedUnits()
    {
        return $this->hasMany(PropertyUnit::class)->whereExists(function ($q) {
            $q->selectRaw(1)
              ->from('lease_contracts')
              ->whereColumn('lease_contracts.unit_id', 'property_units.id')
              ->whereNull('lease_contracts.terminated_at');
        });
    }

    public function floors()
    {
        return $this->hasMany(Floor::class);
    }

    public function blocks()
    {
        return $this->hasMany(Block::class);
    }

    public function images()
    {
        return $this->hasMany(BuildingImage::class)->orderBy('sort_order');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function revenues()
    {
        return $this->hasMany(Revenue::class);
    }

    public function getEffectiveVatRateAttribute(): float
    {
        return $this->vat_enabled ? (float) $this->vat_rate : 0.0;
    }

    public function getFullAddressAttribute(): ?string
    {
        $parts = [
            $this->building_no ? 'Building ' . $this->building_no : null,
            $this->road,
            $this->block ? 'Block ' . $this->block : null,
            $this->area,
            $this->city,
        ];

        $address = implode(', ', array_filter($parts, fn ($p) => filled($p)));

        return $address !== '' ? $address : null;
    }

    public function scopeFilter($query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(function ($q) use ($search) {
                $q->where('property_name', 'like', "%{$search}%")
                  ->orWhere('property_code', 'like', "%{$search}%");
            });
        });

        $query->when($filters['property_type'] ?? null,      fn($q, $v) => $q->where('property_type', $v));
        $query->when($filters['type_of_ownership'] ?? null,  fn($q, $v) => $q->where('type_of_ownership', $v));
        $query->when($filters['company_name'] ?? null,       fn($q, $v) => $q->where('company_name', $v));
    }
}
