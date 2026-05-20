<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Building extends Model
{
    use HasFactory;

    protected $table = 'buildings';

    protected $fillable = [
        'property_name',
        'property_code',
        'type_of_ownership',
        'property_type',
        'land_lord_name',
        'building_no',
        'road',
        'block',
        'area',
        'city',
        'total_no_of_blocks',
        'total_no_of_floors',
        'total_no_of_units',
        'custom_fields',
    ];

    protected $casts = [
        'building_no'        => 'integer',
        'block'              => 'integer',
        'total_no_of_blocks' => 'integer',
        'total_no_of_floors' => 'integer',
        'total_no_of_units'  => 'integer',
        'custom_fields'      => 'array',
    ];

    public function units()
    {
        return $this->hasMany(PropertyUnit::class);
    }

    public function floors()
    {
        return $this->hasMany(Floor::class);
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
    }
}
