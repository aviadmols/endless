<?php
/**
 * Generates the soft "dune" side image used behind the auth / dashboard pages.
 * Pure GD, no external assets.
 */
$W = 1200;
$H = 1800;

$img = imagecreatetruecolor($W, $H);
imagealphablending($img, true);
imagesavealpha($img, false);

// Base vertical gradient: warm paper at the top → deeper sand at the bottom.
$top = [246, 244, 240];
$bottom = [176, 166, 156];
for ($y = 0; $y < $H; $y++) {
    $t = $y / ($H - 1);
    $e = $t * $t * (3 - 2 * $t); // smoothstep
    $c = imagecolorallocate(
        $img,
        (int) round($top[0] + ($bottom[0] - $top[0]) * $e),
        (int) round($top[1] + ($bottom[1] - $top[1]) * $e),
        (int) round($top[2] + ($bottom[2] - $top[2]) * $e)
    );
    imageline($img, 0, $y, $W, $y, $c);
}

/** Smooth pseudo-random 1-D curve. */
function curve(float $x, array $seeds): float
{
    $v = 0.0;
    foreach ($seeds as $i => [$amp, $freq, $phase]) {
        $v += $amp * sin($x * $freq + $phase);
    }

    return $v;
}

// Dune ridges: soft shaded bands sweeping across the frame.
$bands = [
    ['y' => 0.34, 'amp' => 0.055, 'shade' => [214, 205, 194], 'alpha' => 24],
    ['y' => 0.48, 'amp' => 0.070, 'shade' => [200, 190, 178], 'alpha' => 30],
    ['y' => 0.62, 'amp' => 0.060, 'shade' => [186, 175, 163], 'alpha' => 36],
    ['y' => 0.78, 'amp' => 0.045, 'shade' => [168, 157, 146], 'alpha' => 42],
];

foreach ($bands as $b => $band) {
    $seeds = [
        [1.0, 0.0026 + $b * 0.0004, 0.7 * $b],
        [0.45, 0.0061 + $b * 0.0007, 2.1 * $b],
        [0.22, 0.0129 + $b * 0.0011, 4.3 * $b],
    ];
    $baseY = $band['y'] * $H;
    $amp = $band['amp'] * $H;
    $color = imagecolorallocatealpha($img, $band['shade'][0], $band['shade'][1], $band['shade'][2], 127 - $band['alpha']);

    for ($x = 0; $x < $W; $x++) {
        $y = (int) round($baseY + curve($x, $seeds) * $amp);
        imagefilledrectangle($img, $x, $y, $x, $H, $color);
    }
}

// Light catching the ridge crests.
$light = imagecolorallocatealpha($img, 255, 253, 248, 104);
foreach ($bands as $b => $band) {
    $seeds = [
        [1.0, 0.0026 + $b * 0.0004, 0.7 * $b],
        [0.45, 0.0061 + $b * 0.0007, 2.1 * $b],
        [0.22, 0.0129 + $b * 0.0011, 4.3 * $b],
    ];
    $baseY = $band['y'] * $H;
    $amp = $band['amp'] * $H;
    for ($x = 0; $x < $W; $x++) {
        $y = (int) round($baseY + curve($x, $seeds) * $amp);
        imagefilledrectangle($img, $x, max(0, $y - 26), $x, $y, $light);
    }
}

// Very fine grain so it reads as a photograph rather than a gradient.
for ($i = 0; $i < 140000; $i++) {
    $x = random_int(0, $W - 1);
    $y = random_int(0, $H - 1);
    $rgb = imagecolorat($img, $x, $y);
    $d = random_int(-5, 5);
    $r = max(0, min(255, (($rgb >> 16) & 0xFF) + $d));
    $g = max(0, min(255, (($rgb >> 8) & 0xFF) + $d));
    $b2 = max(0, min(255, ($rgb & 0xFF) + $d));
    imagesetpixel($img, $x, $y, imagecolorallocate($img, $r, $g, $b2));
}

// Soften everything.
for ($i = 0; $i < 3; $i++) {
    imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR);
}

$dir = dirname(__DIR__, 1);
$out = $argv[1] ?? 'side-default.webp';
imagewebp($img, $out, 86);
imagedestroy($img);

echo "written: {$out}\n";
