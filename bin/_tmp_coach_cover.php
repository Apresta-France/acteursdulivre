<?php

$src = dirname(__DIR__) . '/public/assets/img/covers/coachs-litteraires.jpg';
$dst = dirname(__DIR__) . '/public/assets/img/covers/coachs-litteraires.webp';
if (!function_exists('imagecreatefromjpeg') || !function_exists('imagewebp')) {
    fwrite(STDERR, "GD webp indisponible\n");
    exit(1);
}
$im = imagecreatefromjpeg($src);
if ($im === false) {
    fwrite(STDERR, "JPEG illisible\n");
    exit(1);
}
imagewebp($im, $dst, 82);
imagedestroy($im);
echo "ok\n";
