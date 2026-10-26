<?php
// Test local : rendu admin du cycle de devis (liste finances + dossier commande).
declare(strict_types=1);

$BASE = 'https://acteursdulivre.test';
const ADMIN = 'qa-admin@acteursdulivre.test';
const PASS = 'TestDevis2026!';

function req(string $method, string $url, array $fields, string $jar): array
{
    $ch = curl_init($GLOBALS['BASE'] . $url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CUSTOMREQUEST => $method,
    ]);
    if ($fields !== []) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    }
    $raw = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $hdrLen = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return [$code, substr($raw, 0, $hdrLen), substr($raw, $hdrLen)];
}

$jar = tempnam(sys_get_temp_dir(), 'ck');
[, , $html] = req('GET', '/connexion', [], $jar);
preg_match('/name="_token" value="([^"]+)"/', $html, $mt);
preg_match('/name="_gate" value="(login[^"]+)"/', $html, $mg);
[$code, $hdr] = req('POST', '/connexion', [
    '_token' => $mt[1] ?? '', '_gate' => $mg[1] ?? '',
    'email' => ADMIN, 'password' => PASS, 'remember' => '1',
], $jar);
if ($code !== 302) { fwrite(STDERR, "LOGIN FAIL ($code)\n"); exit(1); }
echo "✓ login admin\n";

[$code, , $fin] = req('GET', '/admin/finances', [], $jar);
echo $code === 200 ? "✓ /admin/finances 200\n" : "✗ finances $code\n";
echo str_contains($fin, '<th>Devis</th>') ? "✓ colonne Devis\n" : "✗ colonne Devis absente\n";
foreach (['Attente prestataire','Envoyé, attente client','Révision demandée','Accepté'] as $lbl) {
    if (str_contains($fin, $lbl)) echo "✓ état « $lbl » affiché\n";
}
preg_match_all('/admin-pill tone-(green|orange|grey)">(Accepté|Envoyé, attente client|Révision demandée|Attente prestataire)<\/span>/', $fin, $pm);
echo count($pm[0]) . " badge(s) devis dans la liste\n";

// dossier de la dernière commande de test (id 3, devis accepté → in_progress)
[$code, , $det] = req('GET', '/admin/finances/3', [], $jar);
echo $code === 200 ? "✓ dossier commande 200\n" : "✗ dossier $code\n";
echo str_contains($det, 'Ouvrage : Policier') ? "✓ brief structuré visible\n" : "✗ brief absent\n";
echo str_contains($det, 'Délai') ? "✓ délai du devis affiché\n" : "✗ délai absent\n";
echo str_contains($det, 'admin-order-files') || str_contains($det, 'extrait-devis.txt') ? "✓ fichiers suivis\n" : "✗ fichiers absents\n";
if (preg_match('/Délai : ([^<·]+)/', $det, $m)) echo "   délai: " . trim($m[1]) . "\n";
if (preg_match('/(Comprend :[^<]+)/', $det, $m)) echo "   note devis: " . trim($m[1]) . "\n";
