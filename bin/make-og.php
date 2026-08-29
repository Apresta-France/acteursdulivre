<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$srcPath = $root . '/bin/og-source.jpg';
$out = $root . '/public/assets/img/og-default.jpg';
$w = 1200;
$h = 630;

if (!is_file($srcPath)) {
    fwrite(STDERR, "Source manquante : {$srcPath}\n");
    exit(1);
}

$src = imagecreatefromjpeg($srcPath);
if ($src === false) {
    fwrite(STDERR, "JPEG illisible : {$srcPath}\n");
    exit(1);
}

$sw = imagesx($src);
$sh = imagesy($src);

$im = imagecreatetruecolor($w, $h);
$sample = imagecolorat($src, 0, 0);
$cream = imagecolorallocate($im, ($sample >> 16) & 0xFF, ($sample >> 8) & 0xFF, $sample & 0xFF);
imagefilledrectangle($im, 0, 0, $w, $h, $cream);

$scale = max($w / $sw, $h / $sh);
$nw = (int) round($sw * $scale);
$nh = (int) round($sh * $scale);
$dx = (int) (($w - $nw) / 2);
$dy = (int) (($h - $nh) / 2);
imagecopyresampled($im, $src, $dx, $dy, 0, 0, $nw, $nh, $sw, $sh);
imagedestroy($src);

imageinterlace($im, true);
imagejpeg($im, $out, 93);
imagedestroy($im);
@unlink($root . '/public/assets/img/og-default.png');

echo $out . ' (' . filesize($out) . " bytes)\n";
