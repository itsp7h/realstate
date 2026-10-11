<?php

namespace App\Console\Commands;

use App\Models\BuildingImage;
use App\Services\BuildingImageThumbnailer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Fills in display thumbnails for building photos that don't have one —
 * photos uploaded before thumbnails existed, or any whose generation failed.
 *
 * Only reads the database and writes thumbnail files, and skips photos that
 * already have one, so it is safe to run on every deploy.
 */
class GenerateBuildingThumbnails extends Command
{
    protected $signature = 'buildings:thumbnails
                            {--force : Regenerate thumbnails that already exist}';

    protected $description = 'Create missing display thumbnails for building photos';

    public function handle(BuildingImageThumbnailer $thumbnailer): int
    {
        $disk = Storage::disk('public');
        $made = $skipped = $failed = 0;

        foreach (BuildingImage::query()->orderBy('id')->cursor() as $image) {
            if (! $this->option('force') && $disk->exists(BuildingImageThumbnailer::thumbPath($image->path))) {
                $skipped++;
                continue;
            }

            if ($thumbnailer->generate($image->path)) {
                $made++;
            } else {
                $failed++;
                $this->warn("No thumbnail for {$image->path} (missing or unreadable); it will be shown at full size.");
            }
        }

        $this->info("Thumbnails: {$made} created, {$skipped} already present, {$failed} failed.");

        return self::SUCCESS;
    }
}
