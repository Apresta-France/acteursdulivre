<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$out = $root . '/public/assets/img/og-default.jpg';
$w = 1200;
$h = 630;

$im = imagecreatetruecolor($w, $h);
imagealphablending($im, true);

$navy = imagecolorallocate($im, 21, 33, 47);
$navy2 = imagecolorallocate($im, 28, 43, 60);
$beige = imagecolorallocate($im, 239, 223, 206);
$white = imagecolorallocate($im, 255, 255, 255);
$orange = imagecolorallocate($im, 235, 150, 59);
$muted = imagecolorallocate($im, 185, 203, 220);

imagefilledrectangle($im, 0, 0, $w, $h, $navy);

$photo = $root . '/public/assets/img/photos/books.jpg';
if (is_file($photo)) {
    $src = @imagecreatefromjpeg($photo);
    if ($src) {
        $sw = imagesx($src);
        $sh = imagesy($src);
        $scale = max($w / $sw, $h / $sh);
        $nw = (int) round($sw * $scale);
        $nh = (int) round($sh * $scale);
        $dx = (int) (($w - $nw) / 2);
        $dy = (int) (($h - $nh) / 2);
        imagecopyresampled($im, $src, $dx, $dy, 0, 0, $nw, $nh, $sw, $sh);
        imagedestroy($src);
        for ($y = 0; $y < $h; $y++) {
            $alpha = (int) (70 + ($y / $h) * 40);
            $overlay = imagecolorallocatealpha($im, 21, 33, 47, min(127, $alpha));
            imageline($im, 0, $y, $w, $y, $overlay);
        }
    }
}

imagefilledrectangle($im, 0, 0, 14, $h, $orange);

$fonts = [
    'C:/Windows/Fonts/segoeuib.ttf',
    'C:/Windows/Fonts/arialbd.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
];
$font = null;
foreach ($fonts as $candidate) {
    if (is_file($candidate)) {
        $font = $candidate;
        break;
    }
}

if ($font) {
    imagettftext($im, 22, 0, 72, 150, $orange, $font, 'ACTEURSDULIVRE.FR');
    imagettftext($im, 48, 0, 72, 250, $white, $font, 'La place de marché');
    imagettftext($im, 48, 0, 72, 318, $white, $font, 'des métiers du livre');
    imagettftext($im, 22, 0, 72, 400, $beige, $font, 'Correcteurs, illustrateurs, traducteurs, imprimeurs…');
    imagettftext($im, 20, 0, 72, 540, $muted, $font, 'Sans IA générative. Première mission offerte, puis 8 %.');
} else {
    imagestring($im, 5, 72, 130, 'acteursdulivre.fr', $orange);
    imagestring($im, 5, 72, 220, 'La place de marche des metiers du livre', $white);
}

imagejpeg($im, $out, 82);
imagedestroy($im);
@unlink($root . '/public/assets/img/og-default.png');

echo $out . ' (' . filesize($out) . " bytes)\n";
