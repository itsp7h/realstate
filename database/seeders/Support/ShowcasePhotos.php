<?php

namespace Database\Seeders\Support;

/**
 * Generates the imagery the showcase dataset needs, so a demo of the app has
 * real pixels in it without shipping binary assets in the repo or reaching for
 * the network.
 *
 * Two kinds of picture:
 *
 *   facade()    a stylised elevation of a tower — sky gradient, window grid,
 *               neighbouring silhouettes — rendered in the app's own navy/gold
 *               tokens (app-core.css §1.1) so a building's photo strip sits in
 *               the page rather than fighting it. Five times of day give the
 *               gallery five visibly different images.
 *   signature() an ink stroke for the maintenance approval blocks, which print
 *               a signature image on the job-order PDF.
 *
 * Every image carries a low-contrast DEMO mark. These are illustrations, not
 * photographs of a real building, and nothing in a screenshot should suggest
 * otherwise.
 */
final class ShowcasePhotos
{
    /** Deterministic output: same seed in, same picture out, every run. */
    private const SEED = 20260908;

    private const WIDTH  = 1600;
    private const HEIGHT = 1000;

    /**
     * The five looks, each a full palette rather than a tint of one base —
     * dawn is warm and low-contrast, night is cold with lit windows.
     *
     * sky_top / sky_bottom  the gradient behind everything
     * glow                  sun or moon, [x-fraction, y-fraction, radius, rgb]
     * tower                 the lit face of the building
     * tower_shade           its returning face
     * lit                   a window with someone home
     * dark                  a window without
     * lit_chance            how many windows are lit, 0–100
     */
    private const LOOKS = [
        'dawn' => [
            'sky_top' => [0x2B, 0x36, 0x5A], 'sky_bottom' => [0xE8, 0xB9, 0x8A],
            'glow' => [0.78, 0.62, 190, [0xFF, 0xE3, 0xB8]],
            'tower' => [0x31, 0x3F, 0x63], 'tower_shade' => [0x1E, 0x28, 0x45],
            'lit' => [0xF6, 0xD9, 0xA4], 'dark' => [0x27, 0x33, 0x54], 'lit_chance' => 34,
            'ground' => [0x22, 0x2C, 0x49],
        ],
        'morning' => [
            'sky_top' => [0x4E, 0x7C, 0xB8], 'sky_bottom' => [0xCF, 0xE2, 0xF2],
            'glow' => [0.22, 0.20, 150, [0xFF, 0xFA, 0xEC]],
            'tower' => [0xE4, 0xE9, 0xF2], 'tower_shade' => [0xA9, 0xB6, 0xCA],
            'lit' => [0x8C, 0xA6, 0xC8], 'dark' => [0x5A, 0x6C, 0x8B], 'lit_chance' => 12,
            'ground' => [0x8E, 0x99, 0xA8],
        ],
        'midday' => [
            'sky_top' => [0x2F, 0x63, 0xA8], 'sky_bottom' => [0xBE, 0xDA, 0xF0],
            'glow' => [0.50, 0.10, 130, [0xFF, 0xFF, 0xFA]],
            'tower' => [0xF2, 0xF5, 0xFA], 'tower_shade' => [0xB6, 0xC2, 0xD5],
            'lit' => [0x7E, 0x9A, 0xBE], 'dark' => [0x4C, 0x5F, 0x7E], 'lit_chance' => 8,
            'ground' => [0x9A, 0xA4, 0xB2],
        ],
        'dusk' => [
            'sky_top' => [0x1A, 0x21, 0x3E], 'sky_bottom' => [0xD8, 0x8F, 0x63],
            'glow' => [0.30, 0.66, 210, [0xFF, 0xC9, 0x8A]],
            'tower' => [0x2A, 0x35, 0x56], 'tower_shade' => [0x16, 0x1E, 0x36],
            'lit' => [0xED, 0xCD, 0x85], 'dark' => [0x20, 0x2A, 0x48], 'lit_chance' => 58,
            'ground' => [0x18, 0x20, 0x38],
        ],
        'night' => [
            'sky_top' => [0x06, 0x0A, 0x16], 'sky_bottom' => [0x16, 0x22, 0x40],
            'glow' => [0.70, 0.16, 90, [0xE6, 0xEC, 0xFA]],
            'tower' => [0x14, 0x1D, 0x35], 'tower_shade' => [0x0B, 0x11, 0x20],
            'lit' => [0xF4, 0xD7, 0x96], 'dark' => [0x11, 0x18, 0x2C], 'lit_chance' => 72,
            'ground' => [0x0A, 0x0F, 0x1D],
        ],
    ];

    /** @return list<string> the look names, in gallery order */
    public static function looks(): array
    {
        return array_keys(self::LOOKS);
    }

    /**
     * Renders one elevation and writes it as a JPEG. $index shifts the tower's
     * proportions and its neighbours so the five frames read as five views of
     * the same district rather than one drawing recoloured.
     */
    public static function facade(string $look, int $index, string $path): void
    {
        $p = self::LOOKS[$look] ?? self::LOOKS['dusk'];

        mt_srand(self::SEED + $index * 977);

        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imageantialias($img, true);

        self::skyGradient($img, $p['sky_top'], $p['sky_bottom']);
        self::glow($img, $p['glow']);

        $horizon = (int) (self::HEIGHT * 0.86);

        // Neighbours first, so the subject overlaps them.
        self::neighbours($img, $horizon, $p, $index);

        // The subject tower. Narrower and taller as the index climbs, and
        // offset left or right so the composition changes frame to frame.
        $bodyW = (int) (self::WIDTH * (0.40 - $index * 0.022));
        $bodyH = (int) (self::HEIGHT * (0.56 + $index * 0.045));
        $left  = (int) (self::WIDTH * (0.30 + ($index % 2 ? 0.10 : -0.06)));
        $top   = $horizon - $bodyH;

        self::tower($img, $left, $top, $bodyW, $bodyH, $horizon, $p);
        self::ground($img, $horizon, $p['ground'], $left, $left + $bodyW + (int) ($bodyW * 0.16));
        self::vignette($img);
        self::demoMark($img);

        imagejpeg($img, $path, 88);
        imagedestroy($img);
    }

    /**
     * The same stroke as a data URI. The maintenance approval blocks store the
     * signature itself in the column — the job-order view renders it straight
     * into `<img src>` — rather than a path to a file, so this is the form the
     * seeder needs.
     */
    public static function signatureDataUri(string $name): string
    {
        ob_start();
        self::signature($name, null);
        $png = ob_get_clean();

        return 'data:image/png;base64,' . base64_encode($png);
    }

    /**
     * A 420×140 transparent PNG of an ink stroke, for an approval block.
     * A null $path writes to the output buffer instead of to disk, which is
     * what signatureDataUri() captures.
     */
    public static function signature(string $name, ?string $path): void
    {
        $w = 420;
        $h = 140;

        // Seeded from the name, so one approver signs the same way every time.
        mt_srand(crc32($name));

        $img = imagecreatetruecolor($w, $h);
        imagesavealpha($img, true);
        imagealphablending($img, false);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        imagealphablending($img, true);
        imageantialias($img, true);

        $ink = imagecolorallocate($img, 0x1E, 0x2C, 0x4F);
        imagesetthickness($img, 3);

        // Four joined cubic curves, each starting where the last ended, give a
        // continuous hand-written line instead of four separate squiggles.
        $x = 34;
        $y = (int) ($h * 0.62);

        for ($seg = 0; $seg < 4; $seg++) {
            $x2 = $x + mt_rand(70, 110);
            $y2 = (int) ($h * 0.62) + mt_rand(-34, 26);

            $c1x = $x + mt_rand(10, 40);
            $c1y = $y - mt_rand(20, 62);
            $c2x = $x2 - mt_rand(10, 40);
            $c2y = $y2 + mt_rand(10, 48);

            self::bezier($img, $x, $y, $c1x, $c1y, $c2x, $c2y, $x2, $y2, $ink);

            $x = $x2;
            $y = $y2;
        }

        // The underline a signature usually ends with.
        imagesetthickness($img, 2);
        imageline($img, 30, $h - 26, $x + 24, $h - 32, $ink);

        imagepng($img, $path);
        imagedestroy($img);
    }

    // ── pieces ───────────────────────────────────────────────────────────────

    private static function skyGradient($img, array $top, array $bottom): void
    {
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $t = $y / (self::HEIGHT - 1);
            // Eased, so the horizon band is wider than a linear ramp gives —
            // a flat ramp reads as a printing artefact rather than as air.
            $e = $t * $t * (3 - 2 * $t);
            $c = imagecolorallocate(
                $img,
                (int) round($top[0] + ($bottom[0] - $top[0]) * $e),
                (int) round($top[1] + ($bottom[1] - $top[1]) * $e),
                (int) round($top[2] + ($bottom[2] - $top[2]) * $e),
            );
            imageline($img, 0, $y, self::WIDTH, $y, $c);
        }
    }

    private static function glow($img, array $glow): void
    {
        [$fx, $fy, $radius, $rgb] = $glow;
        $cx = (int) (self::WIDTH * $fx);
        $cy = (int) (self::HEIGHT * $fy);

        // Painted outside-in as widening translucent discs: the accumulated
        // alpha falls off smoothly without needing a per-pixel loop.
        for ($r = $radius; $r > 0; $r -= 3) {
            $alpha = (int) round(118 - 118 * ($r / $radius));
            $c = imagecolorallocatealpha($img, $rgb[0], $rgb[1], $rgb[2], max(0, min(127, 127 - $alpha)));
            imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $c);
        }
    }

    private static function neighbours($img, int $horizon, array $p, int $index): void
    {
        $shade = imagecolorallocatealpha($img, $p['tower_shade'][0], $p['tower_shade'][1], $p['tower_shade'][2], 38);
        $lit   = imagecolorallocatealpha($img, $p['lit'][0], $p['lit'][1], $p['lit'][2], 40);

        $x = -40;
        while ($x < self::WIDTH) {
            $w = mt_rand(70, 150);
            $h = mt_rand((int) (self::HEIGHT * 0.16), (int) (self::HEIGHT * 0.46));
            $top = $horizon - $h;

            imagefilledrectangle($img, $x, $top, $x + $w, $horizon, $shade);

            // A sparse scatter of lit windows reads as distance; a full grid
            // would compete with the subject.
            for ($wy = $top + 14; $wy < $horizon - 14; $wy += 26) {
                for ($wx = $x + 12; $wx < $x + $w - 12; $wx += 22) {
                    if (mt_rand(0, 100) < $p['lit_chance'] / 2) {
                        imagefilledrectangle($img, $wx, $wy, $wx + 7, $wy + 11, $lit);
                    }
                }
            }

            $x += $w + mt_rand(6, 26);
        }
    }

    private static function tower($img, int $left, int $top, int $w, int $h, int $horizon, array $p): void
    {
        $face  = imagecolorallocate($img, ...$p['tower']);
        $side  = imagecolorallocate($img, ...$p['tower_shade']);
        $lit   = imagecolorallocate($img, ...$p['lit']);
        $dark  = imagecolorallocate($img, ...$p['dark']);
        $gold  = imagecolorallocate($img, 0xD8, 0xB2, 0x5F);

        $right = $left + $w;

        // A shallow returning face on the right gives the mass depth without
        // needing real perspective.
        $depth = (int) ($w * 0.16);
        imagefilledpolygon($img, [
            $right, $top,
            $right + $depth, $top + (int) ($depth * 0.5),
            $right + $depth, $horizon,
            $right, $horizon,
        ], $side);

        imagefilledrectangle($img, $left, $top, $right, $horizon, $face);

        // Window grid: 4 bays per floor, one floor every 46px, with a gold
        // spandrel line under each — the accent the app uses for emphasis.
        $floorH = 46;
        $bays   = 4;
        $bayW   = (int) (($w - 40) / $bays);

        // Stops well clear of the retail band below, so the lowest row of
        // windows is not half-covered by it.
        for ($fy = $top + 26; $fy < $horizon - 112; $fy += $floorH) {
            for ($b = 0; $b < $bays; $b++) {
                $wx = $left + 20 + $b * $bayW;
                $on = mt_rand(0, 100) < $p['lit_chance'];
                imagefilledrectangle($img, $wx, $fy, $wx + $bayW - 12, $fy + 26, $on ? $lit : $dark);
            }

            $spandrel = imagecolorallocatealpha($img, 0xD8, 0xB2, 0x5F, 96);
            imagefilledrectangle($img, $left + 16, $fy + 30, $right - 8, $fy + 32, $spandrel);
        }

        // Ground-floor retail: taller glazing, always lit, since the showcase
        // building is mixed use and its ground floor is commercial. Mullions
        // break the band up so it does not read as one solid bar.
        imagefilledrectangle($img, $left + 16, $horizon - 66, $right - 12, $horizon - 4, $lit);

        $mullion = imagecolorallocatealpha($img, $p['tower_shade'][0], $p['tower_shade'][1], $p['tower_shade'][2], 40);
        for ($mx = $left + 16 + $bayW; $mx < $right - 12; $mx += $bayW) {
            imagefilledrectangle($img, $mx, $horizon - 66, $mx + 4, $horizon - 4, $mullion);
        }

        // Crown and parapet.
        imagefilledrectangle($img, $left - 8, $top - 12, $right + 8, $top, $side);
        imagefilledrectangle($img, $left - 8, $top - 14, $right + 8, $top - 11, $gold);

        // A mast, so the silhouette has a point of interest against the sky.
        imagesetthickness($img, 3);
        imageline($img, (int) (($left + $right) / 2), $top - 14, (int) (($left + $right) / 2), $top - 74, $gold);
        imagesetthickness($img, 1);
    }

    private static function ground($img, int $horizon, array $rgb, int $towerLeft, int $towerRight): void
    {
        $c = imagecolorallocate($img, ...$rgb);
        imagefilledrectangle($img, 0, $horizon, self::WIDTH, self::HEIGHT, $c);

        // A lighter band right at the horizon separates the mass from the
        // apron instead of letting both read as one dark block.
        $edge = imagecolorallocatealpha($img, 0xFF, 0xFF, 0xFF, 108);
        imagefilledrectangle($img, 0, $horizon, self::WIDTH, $horizon + 3, $edge);

        // A contact shadow under the footprint. Without it the tower floats:
        // its base and the apron are two abutting flat fills and nothing says
        // one is standing on the other.
        //
        // Six layers at a near-transparent alpha, not ten at a heavier one —
        // the accumulated opacity reaches about a third, which reads as shadow
        // on the pale daylight aprons instead of as a black smear. Centred far
        // enough below the horizon that the widest ring still clears it, so
        // the shadow never paints over the ground-floor glazing in front.
        $w = $towerRight - $towerLeft;
        for ($i = 6; $i > 0; $i--) {
            $shadow = imagecolorallocatealpha($img, 0, 0, 0, 119);
            imagefilledellipse(
                $img,
                (int) (($towerLeft + $towerRight) / 2),
                $horizon + 26,
                (int) ($w * (0.72 + $i * 0.055)),
                (int) (20 + $i * 2.6),
                $shadow,
            );
        }

        // Kerb: one line parallel to the horizon, far enough down to read as
        // the near edge of the pavement rather than a second horizon.
        $kerb = imagecolorallocatealpha($img, 0xFF, 0xFF, 0xFF, 116);
        imagefilledrectangle($img, 0, $horizon + 62, self::WIDTH, $horizon + 64, $kerb);
    }

    private static function vignette($img): void
    {
        // Four edge ramps rather than a radial falloff: cheap, and enough to
        // stop the frame's corners competing with its centre.
        for ($i = 0; $i < 90; $i++) {
            $c = imagecolorallocatealpha($img, 0, 0, 0, 127 - (int) (18 * (1 - $i / 90)));
            imageline($img, 0, $i, self::WIDTH, $i, $c);
            imageline($img, 0, self::HEIGHT - $i, self::WIDTH, self::HEIGHT - $i, $c);
            imageline($img, $i, 0, $i, self::HEIGHT, $c);
            imageline($img, self::WIDTH - $i, 0, self::WIDTH - $i, self::HEIGHT, $c);
        }
    }

    /**
     * A quiet DEMO mark. Deliberately legible on inspection and invisible at a
     * glance: these are generated illustrations and must never be mistaken for
     * a photograph of a real property.
     */
    private static function demoMark($img): void
    {
        $ink  = imagecolorallocatealpha($img, 0xFF, 0xFF, 0xFF, 96);
        $font = public_path('fonts/Figtree-700.ttf');

        if (is_file($font) && function_exists('imagettftext')) {
            imagettftext($img, 15, 0, self::WIDTH - 138, self::HEIGHT - 26, $ink, $font, 'DEMO IMAGE');
            return;
        }

        imagestring($img, 3, self::WIDTH - 120, self::HEIGHT - 34, 'DEMO IMAGE', $ink);
    }

    /** Flattens a cubic bezier to a polyline — GD has no curve primitive. */
    private static function bezier($img, $x1, $y1, $cx1, $cy1, $cx2, $cy2, $x2, $y2, int $colour): void
    {
        $steps = 48;
        $px = $x1;
        $py = $y1;

        for ($i = 1; $i <= $steps; $i++) {
            $t = $i / $steps;
            $u = 1 - $t;

            $x = $u ** 3 * $x1 + 3 * $u ** 2 * $t * $cx1 + 3 * $u * $t ** 2 * $cx2 + $t ** 3 * $x2;
            $y = $u ** 3 * $y1 + 3 * $u ** 2 * $t * $cy1 + 3 * $u * $t ** 2 * $cy2 + $t ** 3 * $y2;

            imageline($img, (int) $px, (int) $py, (int) $x, (int) $y, $colour);

            $px = $x;
            $py = $y;
        }
    }
}
