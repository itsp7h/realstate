<?php

namespace App\Models;

use App\Services\BuildingImageThumbnailer;
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

    /**
     * The ≤800px copy for grids, cards and heroes (see
     * BuildingImageThumbnailer). Falls back to the original until the
     * thumbnail exists, so a photo is never missing — only heavier.
     */
    public function getThumbUrlAttribute(): string
    {
        $thumb = BuildingImageThumbnailer::thumbPath($this->path);

        return Storage::disk('public')->exists($thumb)
            ? Storage::disk('public')->url($thumb)
            : $this->url;
    }
}
