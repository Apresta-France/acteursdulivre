<?php
// Test bout-en-bout du parcours guidé « Demander un devis » (local uniquement).
declare(strict_types=1);

const BASE = 'https://acteursdulivre.test';
const CLIENT = 'qa-client@acteursdulivre.test';
const PRO = 'qa-pro@acteursdulivre.test';
const PASS = 'TestDevis2026!';
const SLUG = 'correction-complete-roman-ou-essai';

function req(string $method, string $path, array $post = [], ?string $jar = null, ?string $file = null): array
{
    $ch = curl_init(BASE . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    if ($jar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    }
    if ($method === 'POST') {
        if ($file !== null) {
            $boundary = '----adltest' . bin2hex(random_bytes(8));
            $body = '';
            $add = static function (string $name, string $value) use (&$body, $boundary): void {
                $body .= "--$boundary\r\nContent-Disposition: form-data; name=\"$name\"\r\n\r\n$value\r\n";
            };
            foreach ($post as $k => $v) {
                foreach (is_array($v) ? $v : [$v] as $x) {
                    $add($k, (string) $x);
                }
            }
            $body .= "--$boundary\r\nContent-Disposition: form-data; name=\"attachment\"; filename=\"" . basename($file) . "\"\r\nContent-Type: text/plain\r\n\r\n" . file_get_contents($file) . "\r\n--$boundary--\r\n";
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data; boundary=' . $boundary]);
        } else {
            $pairs = [];
            foreach ($post as $k => $v) {
                foreach (is_array($v) ? $v : [$v] as $x) {
                    $pairs[] = urlencode($k) . '=' . urlencode((string) $x);
                }
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $pairs));
        }
    }
    $raw = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $hdrLen = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return [$code, substr($raw, 0, $hdrLen), substr($raw, $hdrLen)];
}

function login(string $email): string
{
    $jar = tempnam(sys_get_temp_dir(), 'ck');
    [, , $html] = req('GET', '/connexion', [], $jar);
    preg_match('/name="_token" value="([^"]+)"/', $html, $mt);
    preg_match('/name="_gate" value="(login[^"]+)"/', $html, $mg);
    [$code, $hdr] = req('POST', '/connexion', [
        '_token' => $mt[1] ?? '',
        '_gate' => $mg[1] ?? '',
        'email' => $email,
        'password' => PASS,
        'remember' => '1',
    ], $jar);
    if ($code !== 302 || !str_contains($hdr, '/espace')) {
        fwrite(STDERR, "LOGIN FAIL $email ($code)\n$hdr\n");
        exit(1);
    }
    echo "✓ login $email\n";
    return $jar;
}

function tokenFrom(string $html): string
{
    preg_match('/name="_token" value="([^"]+)"/', $html, $m);
    return $m[1] ?? '';
}

$tmp = sys_get_temp_dir() . '/extrait-devis.txt';
file_put_contents($tmp, "Extrait du manuscrit — chapitre 1.\nLe vent balayait les quais de Roscoff.");

// ---------- 1. Client : formulaire guidé + PJ ----------
$jarClient = login(CLIENT);
[, , $cmd] = req('GET', '/espace/commande?prestation=' . SLUG, [], $jarClient);
if (!str_contains($cmd, 'data-quote-wizard')) {
    fwrite(STDERR, "Wizard absent de la page commande\n");
    exit(1);
}
echo "✓ wizard présent (étapes: " . substr_count($cmd, 'data-quote-step=') . ")\n";

$tok = tokenFrom($cmd);
[$code, $hdr] = req('POST', '/espace/commande', [
    '_token' => $tok,
    'service_id' => '5',
    'package_id' => '6',
    'q_mode' => '1',
    'q_genre' => 'Policier & thriller',
    'q_volume' => '250 à 500 pages',
    'q_stade' => 'Premier jet',
    'q_besoins[]' => ['Correction orthotypographique', 'Relecture d’épreuves', 'ValeurInventée'],
    'q_echanges[]' => ['Des points d’avancement réguliers'],
    'q_budget' => '500 à 1 000 €',
    'q_delai' => 'Date précise',
    'q_date' => '2026-12-15',
    'brief' => 'Le roman se passe en Bretagne ; attention au vocabulaire maritime.',
], $jarClient, $tmp);
preg_match('#Location: .*(/espace/suivi/\d+)#', $hdr, $loc);
echo "POST commande → $code " . ($loc[1] ?? '(pas de suivi)') . "\n";
$suiviPath = $loc[1] ?? '';

// ---------- 2. Vérifs en base ----------
define('ADL_ROOT', 'C:/_DEV/acteursdulivre');
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/cli-test';
require ADL_ROOT . '/app/bootstrap.php';

$order = \Adl\Core\Database::fetch('SELECT * FROM orders ORDER BY id DESC LIMIT 1');
echo "── brief stocké ──\n{$order['brief']}\n──────────────────\n";
$ok = str_contains((string) $order['brief'], 'Ouvrage : Policier & thriller')
    && str_contains((string) $order['brief'], 'Volume : 250 à 500 pages')
    && str_contains((string) $order['brief'], 'Calendrier : Pour le 15/12/2026')
    && !str_contains((string) $order['brief'], 'ValeurInventée')
    && str_contains((string) $order['brief'], 'vocabulaire maritime');
echo $ok ? "✓ brief structuré correct (valeur non autorisée filtrée)\n" : "✗ brief incorrect\n";

$msg = \Adl\Core\Database::fetch(
    'SELECT m.body, m.attachment_name, m.attachment_path FROM messages m
     JOIN conversations c ON c.id = m.conversation_id WHERE c.order_id = ? ORDER BY m.id DESC LIMIT 1',
    [$order['id']]
);
echo 'message: ' . mb_substr((string) $msg['body'], 0, 60) . "…\n";
echo 'PJ: ' . var_export($msg['attachment_name'], true) . "\n";
echo ($msg['attachment_name'] === 'extrait-devis.txt' && $msg['attachment_path'] !== null)
    ? "✓ pièce jointe transmise dans la conversation\n"
    : "✗ PJ manquante\n";

// ---------- 3. Prestataire : voit le brief + répond ----------
$jarPro = login(PRO);
[, , $suivi] = req('GET', $suiviPath, [], $jarPro);
echo str_contains($suivi, 'Brief du client') && str_contains($suivi, 'Policier &amp; thriller')
    ? "✓ le prestataire voit le brief structuré dans le suivi\n"
    : "✗ brief absent du suivi prestataire\n";
echo str_contains($suivi, 'name="code" value="quote"')
    ? "✓ formulaire de devis présent côté prestataire\n"
    : "✗ pas de formulaire devis\n";

// Message + PJ visibles dans la messagerie
$convId = \Adl\Core\Database::fetch('SELECT id FROM conversations WHERE order_id = ?', [$order['id']])['id'];
[, , $thread] = req('GET', '/espace/messages/' . $convId, [], $jarPro);
echo str_contains($thread, 'extrait-devis.txt') || str_contains($thread, 'Pièce jointe')
    ? "✓ PJ visible dans la messagerie\n"
    : "✗ PJ invisible dans la messagerie\n";

// ---------- 4. Prestataire : envoie le devis guidé ----------
$tokPro = tokenFrom($suivi);
[$code, $hdr] = req('POST', $suiviPath . '/jalon', [
    '_token' => $tokPro,
    'code' => 'quote',
    'amount' => '850',
    'deposit_amount' => '255',
    'delay' => '3 semaines',
    'd_inclus[]' => ['Un aller-retour inclus', 'Échange de lancement', 'InclusionBidon'],
    'note' => 'Correction complète + relecture des épreuves.',
], $jarPro);
echo "POST devis → $code\n";
$quote = \Adl\Core\Database::fetch(
    'SELECT note, amount, delay FROM order_milestones WHERE order_id = ? AND code = "quote"',
    [$order['id']]
);
echo "note devis: {$quote['note']}\n";
echo (str_contains((string) $quote['note'], 'Comprend : Un aller-retour inclus · Échange de lancement')
    && !str_contains((string) $quote['note'], 'InclusionBidon'))
    ? "✓ inclusions composées et filtrées\n" : "✗ inclusions incorrectes\n";

// ---------- 5. Client : demande une révision avec motifs ----------
[, , $suiviClient] = req('GET', $suiviPath, [], $jarClient);
echo str_contains($suiviClient, 'Demander une révision')
    ? "✓ panneau décision client présent\n" : "✗ panneau décision absent\n";
$tokC = tokenFrom($suiviClient);
[$code] = req('POST', $suiviPath . '/devis/refuser', [
    '_token' => $tokC,
    'reasons[]' => ['Le montant dépasse mon budget', 'MotifBidon'],
    'note' => '800 € serait mieux pour moi.',
], $jarClient);
$refuseMsg = \Adl\Core\Database::fetch(
    'SELECT body FROM messages WHERE conversation_id = ? ORDER BY id DESC LIMIT 1', [$convId]
);
echo "ping révision: {$refuseMsg['body']}\n";
echo str_contains((string) $refuseMsg['body'], 'Motif : Le montant dépasse mon budget')
    && !str_contains((string) $refuseMsg['body'], 'MotifBidon')
    ? "✓ motifs composés et filtrés\n" : "✗ motifs incorrects\n";

// ---------- 6. Prestataire : renvoie un devis ajusté ----------
[, , $suivi2] = req('GET', $suiviPath, [], $jarPro);
echo str_contains($suivi2, 'Renvoyer le devis') || str_contains($suivi2, 'name="code" value="quote"')
    ? "✓ le prestataire peut renvoyer un devis\n" : "✗ reprise devis impossible\n";
$tokPro2 = tokenFrom($suivi2);
[$code] = req('POST', $suiviPath . '/jalon', [
    '_token' => $tokPro2,
    'code' => 'quote',
    'amount' => '790',
    'deposit_amount' => '0',
    'delay' => '4 semaines',
    'note' => 'Montant ajusté ; sans acompte.',
], $jarPro);
echo "re-devis → $code\n";

// ---------- 7. Client : accepte ----------
[, , $suivi3] = req('GET', $suiviPath, [], $jarClient);
echo str_contains($suivi3, '790') || str_contains($suivi3, '790&nbsp;')
    ? "✓ nouveau montant visible\n" : "? montant\n";
$tokC3 = tokenFrom($suivi3);
[$code] = req('POST', $suiviPath . '/jalon', [
    '_token' => $tokC3,
    'code' => 'quote_accept',
], $jarClient);
$st = \Adl\Core\Database::fetch('SELECT status FROM orders WHERE id = ?', [$order['id']]);
echo "acceptation → $code, statut={$st['status']}\n";
echo $st['status'] === 'in_progress' ? "✓ commande en cours\n" : "✗ statut inattendu\n";
