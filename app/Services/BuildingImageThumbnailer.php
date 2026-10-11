<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Small display copies of building photos.
 *
 * Originals are kept at full resolution (1.5-3 MB each after the browser's
 * upload compression) for the lightbox, but every other place a photo appears
 * — the Photos grid, building cards, the dashboard, the mobile hero — shows it
 * a few hundred pixels wide. Serving the original there made a page with six
 * photos download ~12 MB. Those places use this copy instead: the longest side
 * capped at MAX_SIDE, re-encoded as JPEG.
 *
 * The thumbnail sits next to its original at a derived path
 * (buildings/4/abc.jpg → buildings/4/thumbs/abc.jpg), so there is no column to
 * migrate and no row to keep in step: BuildingImage::thumb_url falls back to
 * the original while a thumbnail doesn't exist. `php artisan
 * buildings:thumbnails` fills in any that are missing, and deploys run it.
 *
 * Uses GD. If GD is missing or a photo can't be decoded, generation reports
 * false and the photo keeps being served at full size — never an error.
 */
class BuildingImageThumbnailer
{
    public const MAX_SIDE = 800;

    public const QUALITY = 80;

    /**
     * Decoding is uncompressed: a 24-megapixel photo needs ~100 MB of GD
     * bitmap, more than PHP's default 128M limit leaves after the framework.
     */
    private const MEMORY_LIMIT = '512M';

    public static function thumbPath(string $path): string
    {
        $dir = pathinfo($path, PATHINFO_DIRNAME);
        $name = pathinfo($path, PATHINFO_FILENAME) . '.jpg';

        return ($dir === '.' ? '' : $dir . '/') . 'thumbs/' . $name;
    }

    /** Create the thumbnail for a stored photo. True when it now exists. */
    public function generate(string $path): bool
    {
        if (! function_exists('imagecreatefromstring')) {
            return false;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return false;
        }

        $previousLimit = ini_get('memory_limit');
        if ($this->limitBytes($previousLimit) !== -1 && $this->limitBytes($previousLimit) < $this->limitBytes(self::MEMORY_LIMIT)) {
            ini_set('memory_limit', self::MEMORY_LIMIT);
        }

        $source = $thumb = null;
        try {
            $source = @imagecreatefromstring($disk->get($path));
            if ($source === false) {
                return false;
            }
            $source = $this->orient($source, $disk->path($path));

            $width = imagesx($source);
            $height = imagesy($source);
            $scale = min(1, self::MAX_SIDE / max($width, $height));
            $w = max(1, (int) round($width * $scale));
            $h = max(1, (int) round($height * $scale));

            // JPEG has no alpha: lay a transparent PNG/WEBP on white rather
            // than letting its transparent areas turn black.
            $thumb = imagecreatetruecolor($w, $h);
            imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 255));
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $w, $h, $width, $height);

            ob_start();
            imageinterlace($thumb, true);
            imagejpeg($thumb, null, self::QUALITY);
            $disk->put(self::thumbPath($path), ob_get_clean());

            return true;
        } catch (Throwable $e) {
            Log::warning('Building photo thumbnail failed', ['path' => $path, 'error' => $e->getMessage()]);

            return false;
        } finally {
            if ($source) {
                imagedestroy($source);
            }
            if ($thumb) {
                imagedestroy($thumb);
            }
            ini_set('memory_limit', $previousLimit);
        }
    }

    public function delete(string $path): void
    {
        Storage::disk('public')->delete(self::thumbPath($path));
    }

    /**
     * Photos straight off a camera store "rotate me" in EXIF instead of
     * rotated pixels; GD ignores it, so apply it here. Browser-compressed
     * uploads are already upright and carry no EXIF, so this is a no-op.
     */
    private function orient(\GdImage $image, string $file): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($file)['Orientation'] ?? 1);
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };
        if ($rotated) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    private function limitBytes(string|false $limit): int
    {
        if ($limit === false || $limit === '' || $limit === '-1') {
            return -1;
        }
        $number = (int) $limit;

        return match (strtoupper(substr($limit, -1))) {
            'G' => $number * 1024 ** 3,
            'M' => $number * 1024 ** 2,
            'K' => $number * 1024,
            default => $number,
        };
    }
}
