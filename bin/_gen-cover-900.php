<?php
// Génère la couverture de l'article « 900 visites par jour » (usage unique, local).
declare(strict_types=1);

$W = 1200; $H = 630;
$img = imagecreatetruecolor($W, $H);

$hex = static function (string $h) use ($img): int {
    $h = ltrim($h, '#');
    return imagecolorallocate($img, hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2)));
};

$fog = $hex('F4F6F9'); $white = $hex('FFFFFF'); $navy = $hex('15212F');
$orange = $hex('EB963B'); $muted = $hex('4A5A6B'); $line = $hex('E8ECF1');
$light = $hex('8496A8'); $peach = $hex('FDF3F0');

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

$chip = static function ($img, $x, $y, string $label, bool $on) use ($fontR, $navy, $white, $line, $muted, $rr): int {
    $pad = 14; $size = 15;
    $bb = imagettfbbox($size, 0, $fontR, $label);
    $tw = $bb[2] - $bb[0];
    $w = $tw + 2 * $pad; $h = 34;
    $rr($img, $x, $y, $x + $w, $y + $h, 17, $on ? $navy : $white);
    if (!$on) {
        imagerectangle($img, $x, $y, $x + $w, $y + $h, $line);
    }
    imagettftext($img, $size, 0, $x + $pad, $y + 24, $on ? $white : $muted, $fontR, $label);
    return $x + $w + 10;
};

// ── Titre du visuel ──
imagettftext($img, 30, 0, 60, 78, $navy, $fontB, 'Plus de 900 visites par jour');
imagettftext($img, 16, 0, 60, 112, $muted, $fontR, 'Deux mois après la rentrée, la plateforme s’installe.');

// ── Carte graphique (gauche) ──
$rr($img, 60, 150, 720, 560, 18, $white);
imagerectangle($img, 60, 150, 720, 560, $line);

imagettftext($img, 12, 0, 90, 192, $orange, $fontB, 'VISITES QUOTIDIENNES');
imagettftext($img, 13, 0, 90, 216, $light, $fontR, 'Septembre → début novembre');

// grille horizontale + graduations
$base = 520; $top = 250;
foreach ([0, 300, 600, 900] as $v) {
    $y = $base - (int) round(($v / 900) * ($base - $top));
    imageline($img, 120, $y, 690, $y, $line);
    imagettftext($img, 12, 0, 86, $y + 4, $light, $fontR, (string) $v);
}

// barres de progression hebdomadaire vers 900+
$bars = [
    ['Début sept.', 140],
    ['Fin sept.', 260],
    ['Ouverture', 420],
    ['Mi-oct.', 610],
    ['Fin oct.', 780],
    ['Début nov.', 920],
];
$x = 135;
$barW = 72;
foreach ($bars as $i => [$label, $v]) {
    $h = (int) round(($v / 900) * ($base - $top));
    $y1 = $base - $h;
    $last = $i === count($bars) - 1;
    $rr($img, $x, $y1, $x + $barW, $base, 8, $last ? $orange : $navy);
    imagettftext($img, 12, 0, $x, $base + 22, $muted, $fontR, $label);
    imagettftext($img, 13, 0, $x + 6, $y1 - 10, $last ? $orange : $navy, $fontB, (string) $v . ($last ? '+' : ''));
    $x += $barW + 20;
}

// ── Carte chiffres (droite) ──
$rr($img, 760, 150, 1140, 560, 18, $white);
imagerectangle($img, 760, 150, 1140, 560, $line);

imagettftext($img, 12, 0, 790, 192, $orange, $fontB, 'DÉBUT NOVEMBRE');

$stats = [
    ['900+', 'visites par jour'],
    ['~200', 'professionnels référencés'],
    ['~100', 'prestations à prix affiché'],
    ['18', 'métiers couverts'],
    ['~500', 'salons à l’agenda'],
];
$sy = 228;
foreach ($stats as [$num, $lbl]) {
    imagettftext($img, 26, 0, 790, $sy, $navy, $fontB, $num);
    imagettftext($img, 14, 0, 900, $sy - 4, $muted, $fontR, $lbl);
    $sy += 58;
}

// pastille finale
$rr($img, 790, 510, 1110, 544, 17, $peach);
imagettftext($img, 13, 0, 806, 531, $muted, $fontR, 'Cap suivant : 200 pros · 100 prestations');

// chips métiers
$x = 60; $y = 580;
$x = $chip($img, $x, $y, 'Correction', false);
$x = $chip($img, $x, $y, 'Illustration', false);
$x = $chip($img, $x, $y, 'Maquette', false);
$x = $chip($img, $x, $y, 'Impression', false);
$x = $chip($img, $x, $y, 'Traduction', false);
$x = $chip($img, $x, $y, 'Coaching', false);

imagejpeg($img, 'C:/_DEV/acteursdulivre/public/assets/img/journal/900-visites-par-jour.jpg', 90);
imagedestroy($img);
echo "cover generated\n";
