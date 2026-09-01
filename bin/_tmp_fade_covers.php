<?php

declare(strict_types=1);

$srcDir = 'C:/Users/julie/.cursor/projects/c-DEV-acteursdulivre/assets';
$outDir = dirname(__DIR__) . '/public/assets/img/covers';
$stems = [
    1 => 'auteurs', 2 => 'correcteurs', 3 => 'beta-lecteurs', 4 => 'illustrateurs',
    5 => 'traducteurs', 6 => 'maquettistes', 7 => 'editeurs', 8 => 'imprimeurs',
    9 => 'presse-communication', 10 => 'libraires', 11 => 'narrateurs-audio',
    12 => 'agents-litteraires', 13 => 'salons-evenements', 14 => 'iconographes',
    15 => 'lecteurs-editoriaux', 16 => 'photographes', 17 => 'relieurs', 18 => 'juristes',
];

$inkR = 11;
$inkG = 19;
$inkB = 27;

foreach (glob($srcDir . '/*ChatGPT_Image*.jpg') ?: [] as $path) {
    if (!preg_match('/__(\d+)_-/', basename($path), $m)) {
        continue;
    }
    $stem = $stems[(int) $m[1]] ?? null;
    if ($stem === null) {
        continue;
    }
    $src = imagecreatefromjpeg($path);
    if ($src === false) {
        continue;
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $out = imagecreatetruecolor($w, $h);
    for ($x = 0; $x < $w; $x++) {
        $u = $w > 1 ? $x / ($w - 1) : 1;
        if ($u < 0.42) {
            $e = 0.10 + 0.12 * ($u / 0.42);
        } else {
            $t = ($u - 0.42) / 0.28;
            if ($t > 1) {
                $t = 1;
            }
            $s = $t * $t * $t * ($t * ($t * 6 - 15) + 10);
            $e = 0.22 + 0.78 * $s;
        }
        for ($y = 0; $y < $h; $y++) {
            $rgb = imagecolorat($src, $x, $y);
            $r = ($rgb >> 16) & 255;
            $g = ($rgb >> 8) & 255;
            $b = $rgb & 255;
            imagesetpixel($out, $x, $y, imagecolorallocate(
                $out,
                (int) round($inkR * (1 - $e) + $r * $e),
                (int) round($inkG * (1 - $e) + $g * $e),
                (int) round($inkB * (1 - $e) + $b * $e)
            ));
        }
    }
    imagedestroy($src);
    imagewebp($out, $outDir . '/' . $stem . '.webp', 90);
    imageinterlace($out, true);
    imagejpeg($out, $outDir . '/' . $stem . '.jpg', 90);
    imagedestroy($out);
    echo $stem . "\n";
}
