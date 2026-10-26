<?php
// Génère la couverture de l'article « demande de devis guidée » (usage unique, local).
declare(strict_types=1);

$W = 1200; $H = 630;
$img = imagecreatetruecolor($W, $H);

$hex = static function (string $h) use ($img): int {
    $h = ltrim($h, '#');
    return imagecolorallocate($img, hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2)));
};

$fog = $hex('F4F6F9'); $white = $hex('FFFFFF'); $navy = $hex('15212F');
$orange = $hex('EB963B'); $muted = $hex('4A5A6B'); $line = $hex('E8ECF1');
$light = $hex('8496A8'); $peach = $hex('FDF3F0'); $beige = $hex('EFDFCE');

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
        // bordure fine
        imagerectangle($img, $x, $y, $x + $w, $y + $h, $line);
    }
    imagettftext($img, $size, 0, $x + $pad, $y + 24, $on ? $white : $muted, $fontR, $label);
    return $x + $w + 10;
};

// ── Titre du visuel ──
imagettftext($img, 30, 0, 60, 78, $navy, $fontB, 'Demander un devis, sans rédiger');
imagettftext($img, 16, 0, 60, 112, $muted, $fontR, 'Quatre étapes de choix simples — le prestataire reçoit un brief clair.');

// ── Carte formulaire (gauche) ──
$rr($img, 60, 150, 640, 560, 18, $white);
imagerectangle($img, 60, 150, 640, 560, $line);

// barre de progression
imagettftext($img, 13, 0, 90, 192, $light, $fontR, 'Étape 1 sur 4 — Votre ouvrage');
imagefilledrectangle($img, 90, 204, 320, 211, $orange);
imagerectangle($img, 90, 204, 610, 211, $line);

imagettftext($img, 15, 0, 90, 250, $navy, $fontB, 'Quel type d’ouvrage ?');
$x = 90; $y = 268;
$x = $chip($img, $x, $y, 'Roman', false);
$x = $chip($img, $x, $y, 'Policier & thriller', true);
$x = $chip($img, $x, $y, 'Jeunesse', false);
$x = 90; $y += 46;
$x = $chip($img, $x, $y, 'Essai & document', false);
$x = $chip($img, $x, $y, 'Biographie', false);

imagettftext($img, 15, 0, 90, 376, $navy, $fontB, 'Quel volume de texte ?');
$x = 90; $y = 394;
$x = $chip($img, $x, $y, 'Moins de 100 pages', false);
$x = $chip($img, $x, $y, '250 à 500 pages', true);
$x = 90; $y += 46;
$x = $chip($img, $x, $y, 'Plus de 500 pages', false);

// bouton continuer
$rr($img, 470, 505, 610, 545, 9, $orange);
imagettftext($img, 15, 0, 507, 529, $white, $fontB, 'Continuer');
imagettftext($img, 13, 0, 90, 533, $light, $fontR, 'Retour');

// ── Carte message structuré (droite) ──
$rr($img, 680, 150, 1140, 560, 18, $white);
imagerectangle($img, 680, 150, 1140, 560, $line);

imagettftext($img, 12, 0, 710, 192, $orange, $fontB, 'CE QUE REÇOIT LE PRESTATAIRE');
imagettftext($img, 14, 0, 710, 222, $muted, $fontR, 'Bonjour Claire, je cherche une correctrice :');

// bloc structuré
$rr($img, 710, 240, 1110, 470, 10, $fog);
$lines = [
    ['Ouvrage', 'Policier & thriller'],
    ['Volume', '250 à 500 pages'],
    ['Avancement', 'Premier jet'],
    ['Besoin', 'Correction orthotypographique'],
    ['Budget', '500 à 1 000 €'],
    ['Calendrier', 'Pour le 15/12/2026'],
];
$ly = 272;
foreach ($lines as [$k, $v]) {
    imagettftext($img, 13, 0, 730, $ly, $light, $fontB, $k);
    imagettftext($img, 13, 0, 850, $ly, $navy, $fontR, $v);
    $ly += 36;
}

imagettftext($img, 14, 0, 710, 508, $navy, $fontR, 'Pouvez-vous m’envoyer un devis ?');

// badge PJ
$rr($img, 710, 524, 960, 550, 13, $peach);
imagettftext($img, 12, 0, 726, 541, $muted, $fontR, 'Pièce jointe : extrait-chapitre1.txt');

imagejpeg($img, 'C:/_DEV/acteursdulivre/public/assets/img/journal/demande-devis-guidee.jpg', 90);
imagedestroy($img);
echo "cover generated\n";
