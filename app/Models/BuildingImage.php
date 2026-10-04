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

    /**
     * Root-relative, via the public disk's configured url — see
     * config/filesystems.php. An absolute URL here would pin photos to APP_URL's
     * host, which the browser cannot always resolve (staging is reached by IP).
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
