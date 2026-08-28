<?php

declare(strict_types=1);

$dir = dirname(__DIR__) . '/public/assets/img/photos';
foreach (glob($dir . '/*.jpg') ?: [] as $jpg) {
    $im = @imagecreatefromjpeg($jpg);
    if (!$im) {
        echo "skip $jpg\n";
        continue;
    }
    $webp = preg_replace('/\.jpg$/', '.webp', $jpg);
    imagewebp($im, $webp, 78);
    imagedestroy($im);
    echo basename((string) $webp) . ' ' . filesize((string) $webp) . "\n";
}
