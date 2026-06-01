<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BuildingImage extends Model
{
    protected $fillable = ['building_id', 'path', 'sort_order'];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
