<?php

declare(strict_types=1);

define('ADL_ROOT', dirname(__DIR__));
require ADL_ROOT . '/app/bootstrap.php';

use Adl\Core\RichText;

$cases = [
    ['<p>Texte <strong>ok</strong></p>', 'basic', true, 'strong'],
    ['<p>A</p><script>alert(1)</script>', 'basic', false, 'script'],
    ['<p onclick="x()">A</p>', 'basic', false, 'onclick'],
    ['<a href="javascript:alert(1)">x</a>', 'basic', false, 'javascript'],
    ['<a href="https://example.com">lien</a>', 'basic', true, 'noopener'],
    ['<form><label>Métier</label><select><option>BD</option></select><button>G</button></form><p>Vrai texte</p>', 'basic', false, 'form'],
    ['<h2>Titre</h2><p>Corps</p>', 'basic', false, '<h2'],
    ['<h2>Titre</h2><p>Corps</p>', 'full', true, '<h2'],
    ['<img src=x onerror=alert(1)>', 'basic', false, 'img'],
    ['<iframe src="https://evil.test"></iframe>', 'basic', false, 'iframe'],
    ['<div class="wysiwyg-toolbar"><button type="button">G</button></div><p>Description</p>', 'basic', false, 'button'],
];

$fail = 0;
foreach ($cases as [$html, $profile, $mustContain, $needle]) {
    $out = RichText::sanitize($html, $profile);
    $hay = strtolower($out);
    $ok = $mustContain ? str_contains($hay, strtolower($needle)) : !str_contains($hay, strtolower($needle));
    echo ($ok ? 'OK  ' : 'FAIL') . ' [' . $profile . '] ' . $needle . ' => ' . $out . PHP_EOL;
    if (!$ok) {
        $fail++;
    }
}

echo ($fail === 0 ? 'ALL PASS' : $fail . ' FAILED') . PHP_EOL;
echo 'user_html: ' . user_html('<div class="wysiwyg"><button>G</button><p>Périmètre <em>clair</em></p></div>') . PHP_EOL;
