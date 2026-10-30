<?php

declare(strict_types=1);

$W = 1200;
$H = 630;
$img = imagecreatetruecolor($W, $H);

$hex = static function (string $h) use ($img): int {
    $h = ltrim($h, '#');
    return imagecolorallocate($img, hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2)));
};

$fog = $hex('F4F6F9');
$white = $hex('FFFFFF');
$navy = $hex('15212F');
$orange = $hex('EB963B');
$muted = $hex('4A5A6B');
$line = $hex('E8ECF1');
$light = $hex('8496A8');
$peach = $hex('FDF3F0');

imagefill($img, 0, 0, $fog);

$fontR = 'C:/Windows/Fonts/arial.ttf';
$fontB = 'C:/Windows/Fonts/arialbd.ttf';

$rr = static function ($img, $x1, $y1, $x2, $y2, $r, $col): void {
    imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $col);
    imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $col);
    imagefilledellipse($img, $x1 + $r, $y1 + $r, 2 * $r, 2 * $r, $col);
    imagefilledellipse($img, $x2 - $r, $y1 + $r, 2 * $r, 2 * $r, $col);
    imagefilledellipse($img, $x1 + $r, $y2 - $r, 2 * $r, 2 * $r, $col);
    imagefilledellipse($img, $x2 - $r, $y2 - $r, 2 * $r, 2 * $r, $col);
};

imagettftext($img, 30, 0, 60, 78, $navy, $fontB, 'Trois créneaux, une visio');
imagettftext($img, 16, 0, 60, 112, $muted, $fontR, 'Le prestataire propose. Le client choisit. Le lien reste dans l’échange.');

$rr($img, 60, 150, 620, 560, 18, $white);
imagerectangle($img, 60, 150, 620, 560, $line);
imagettftext($img, 12, 0, 90, 192, $orange, $fontB, 'LE PRESTATAIRE PROPOSE');
imagettftext($img, 18, 0, 90, 228, $navy, $fontB, 'Proposer une visio');
imagettftext($img, 14, 0, 90, 258, $muted, $fontR, 'Trois dates à venir, heure de Paris.');

$slots = [
    ['Jeudi 12 novembre', '14 h 00', true],
    ['Vendredi 13 novembre', '10 h 30', false],
    ['Lundi 16 novembre', '16 h 00', false],
];
$y = 286;
foreach ($slots as [$day, $hour, $on]) {
    $rr($img, 90, $y, 590, $y + 58, 12, $on ? $peach : $fog);
    if ($on) {
        imagefilledellipse($img, 118, $y + 29, 18, 18, $orange);
        imagefilledellipse($img, 118, $y + 29, 8, 8, $white);
    } else {
        imagefilledellipse($img, 118, $y + 29, 18, 18, $light);
        imagefilledellipse($img, 118, $y + 29, 10, 10, $fog);
    }
    imagettftext($img, 15, 0, 142, $y + 35, $navy, $on ? $fontB : $fontR, $day . '  ·  ' . $hour);
    $y += 70;
}

$rr($img, 660, 150, 1140, 560, 18, $white);
imagerectangle($img, 660, 150, 1140, 560, $line);
imagettftext($img, 12, 0, 690, 192, $orange, $fontB, 'DANS LA CONVERSATION');
imagettftext($img, 22, 0, 690, 232, $navy, $fontB, 'Visio prévue');
imagettftext($img, 15, 0, 690, 268, $muted, $fontR, 'jeudi 12 novembre, 14 h 00');
imagettftext($img, 15, 0, 690, 294, $muted, $fontR, 'Le rendez-vous se tient sur kMeet.');

$rr($img, 690, 330, 980, 384, 10, $orange);
imagettftext($img, 16, 0, 724, 364, $white, $fontB, 'Rejoindre la visio');

imagettftext($img, 13, 0, 690, 430, $light, $fontR, 'kmeet.infomaniak.com/');
imagettftext($img, 13, 0, 690, 452, $navy, $fontR, 'acteur-du-livre-…');

$rr($img, 690, 490, 1040, 528, 14, $fog);
imagettftext($img, 13, 0, 708, 515, $muted, $fontR, 'Le client a retenu ce créneau.');

imagejpeg($img, 'C:/_DEV/acteursdulivre/public/assets/img/journal/proposer-une-visio.jpg', 90);
imagedestroy($img);
echo "cover generated\n";
