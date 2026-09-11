<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Generates soft, photo-like placeholder images with GD (gradients + blurred circles) for the demo seed.
 * Keeps the repository free of third-party photos.
 */
class DemoImages
{
    /** Warm, muted palette that sits well on the #F9F8F5 background. */
    protected array $palettes = [
        [[214, 196, 178], [96, 84, 78]],
        [[196, 204, 212], [72, 84, 96]],
        [[222, 210, 190], [120, 100, 86]],
        [[190, 200, 190], [70, 86, 78]],
        [[226, 214, 206], [132, 96, 90]],
        [[204, 196, 186], [64, 62, 66]],
    ];

    /** @return array{path:string, thumb_path:string, width:int, height:int} */
    public function photo(string $dir, int $rw, int $rh, int $seed = 0): array
    {
        $width = 1200;
        $height = (int) round($width * $rh / $rw);
        $id = Str::uuid()->toString();
        $palette = $this->palettes[$seed % count($this->palettes)];

        $img = $this->render($width, $height, $palette, $seed);
        $path = "{$dir}/{$id}.webp";
        $thumb = "{$dir}/{$id}_thumb.webp";

        Storage::disk('public')->put($path, $this->encode($img));
        $small = imagescale($img, 700, (int) round(700 * $height / $width));
        Storage::disk('public')->put($thumb, $this->encode($small));
        imagedestroy($img);
        imagedestroy($small);

        return ['path' => $path, 'thumb_path' => $thumb, 'width' => $width, 'height' => $height];
    }

    public function portrait(string $dir): string
    {
        $img = $this->render(900, 1200, [[228, 220, 210], [90, 80, 76]], 3);
        // simple "figure" silhouette: head + shoulders
        $shade = imagecolorallocatealpha($img, 60, 56, 58, 40);
        imagefilledellipse($img, 450, 470, 300, 340, $shade);
        imagefilledellipse($img, 450, 1080, 760, 620, $shade);
        $path = "{$dir}/".Str::uuid()->toString().'.webp';
        Storage::disk('public')->put($path, $this->encode($img));
        imagedestroy($img);

        return $path;
    }

    /** @return \GdImage */
    protected function render(int $w, int $h, array $palette, int $seed)
    {
        mt_srand(1000 + $seed);
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, true);
        [$a, $b] = $palette;

        // Vertical gradient
        for ($y = 0; $y < $h; $y++) {
            $t = $y / max(1, $h - 1);
            $c = imagecolorallocate($img,
                (int) round($a[0] + ($b[0] - $a[0]) * $t),
                (int) round($a[1] + ($b[1] - $a[1]) * $t),
                (int) round($a[2] + ($b[2] - $a[2]) * $t));
            imageline($img, 0, $y, $w, $y, $c);
        }

        // Soft translucent circles (bokeh)
        for ($i = 0; $i < 7; $i++) {
            $cx = mt_rand(0, $w);
            $cy = mt_rand(0, $h);
            $r = mt_rand((int) ($w * 0.12), (int) ($w * 0.45));
            $light = mt_rand(0, 1);
            $col = imagecolorallocatealpha($img, $light ? 255 : $b[0], $light ? 250 : $b[1], $light ? 240 : $b[2], mt_rand(96, 118));
            imagefilledellipse($img, $cx, $cy, $r, $r, $col);
        }

        // Subtle vignette
        $vig = imagecolorallocatealpha($img, 20, 18, 20, 112);
        imagefilledrectangle($img, 0, (int) ($h * 0.82), $w, $h, $vig);

        return $img;
    }

    protected function encode($img): string
    {
        ob_start();
        imagewebp($img, null, 80);

        return (string) ob_get_clean();
    }
}
