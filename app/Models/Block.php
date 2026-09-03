<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Block extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'blocks';

    protected $fillable = [
        'building_id',
        'block_name',
        'block_code',
        'total_no_of_floors',
    ];

    protected $casts = [
        'total_no_of_floors' => 'integer',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function floors()
    {
        return $this->hasMany(Floor::class);
    }

    public function scopeFilter($query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where('block_name', 'like', "%{$search}%");
        });
    }
}
