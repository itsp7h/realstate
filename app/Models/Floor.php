<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Floor extends Model
{
    use HasFactory;

    protected $table = 'floors';

    protected $fillable = [
        'building_id',
        'floor_name',
        'floor_code',
        'block_name',
        'block_code',
        'total_no_of_units',
    ];

    protected $casts = [
        'total_no_of_units' => 'integer',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function units()
    {
        return $this->hasMany(PropertyUnit::class);
    }

    public function scopeFilter($query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where('floor_name', 'like', "%{$search}%");
        });

        $query->when($filters['block_name'] ?? null, fn($q, $v) => $q->where('block_name', $v));
    }
}
