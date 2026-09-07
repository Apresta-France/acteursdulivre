<?php

declare(strict_types=1);

$html = file_get_contents('https://acteursdulivre.test/journal/autoedition-guide-complet');
if ($html === false) {
    fwrite(STDERR, "fetch failed\n");
    exit(1);
}
echo 'bytes=' . strlen($html) . PHP_EOL;
echo 'title=' . (preg_match('#<title>([^<]+)</title>#', $html, $m) ? $m[1] : '?') . PHP_EOL;
echo 'canonical=' . (preg_match('#rel="canonical" href="([^"]+)"#', $html, $m) ? $m[1] : '?') . PHP_EOL;
echo 'cover=' . (str_contains($html, 'autoedition-guide-complet.jpg') ? 'yes' : 'no') . PHP_EOL;
echo 'FAQPage=' . (str_contains($html, '"FAQPage"') ? 'yes' : 'no') . PHP_EOL;
echo 'HowTo=' . (str_contains($html, '"HowTo"') ? 'yes' : 'no') . PHP_EOL;
echo 'ItemList=' . (str_contains($html, '"ItemList"') ? 'yes' : 'no') . PHP_EOL;
echo 'sameAs=' . (str_contains($html, 'wikipedia.org') ? 'yes' : 'no') . PHP_EOL;
echo 'speakable=' . (str_contains($html, 'SpeakableSpecification') ? 'yes' : 'no') . PHP_EOL;

$llms = file_get_contents('https://acteursdulivre.test/llms.txt');
echo 'llms=' . (is_string($llms) && str_contains($llms, 'autoedition-guide-complet') ? 'yes' : 'no') . PHP_EOL;
$sitemap = file_get_contents('https://acteursdulivre.test/sitemap.xml');
echo 'sitemap=' . (is_string($sitemap) && str_contains($sitemap, 'autoedition-guide-complet') ? 'yes' : 'no') . PHP_EOL;
