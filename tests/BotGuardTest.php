<?php

declare(strict_types=1);

define('ADL_ROOT', dirname(__DIR__));

if (($argv[1] ?? '') === '--rate-worker') {
    require ADL_ROOT . '/app/helpers.php';
    $action = (string) ($argv[2] ?? 'test');
    $id = (string) ($argv[3] ?? 'x');
    $max = (int) ($argv[4] ?? 1);
    $window = (int) ($argv[5] ?? 60);
    $n = (int) ($argv[6] ?? 1);
    $accepted = 0;
    for ($i = 0; $i < $n; $i++) {
        if (!rate_limited($action, $id, $max, $window)) {
            $accepted++;
        }
    }
    echo (string) $accepted;
    exit(0);
}

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'Adl\\')) {
        return;
    }
    $path = ADL_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($path)) {
        require $path;
    }
});
require ADL_ROOT . '/app/helpers.php';

use Adl\Core\BotGuard;
use Adl\Core\Request;

ob_start();
session_name('adl_bot_test');
session_save_path(sys_get_temp_dir());
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$failed = 0;
$passed = 0;

function expect(bool $ok, string $label): void
{
    global $failed, $passed;
    if ($ok) {
        $passed++;
        echo "ok  $label\n";
        return;
    }
    $failed++;
    echo "KO  $label\n";
}

$_SESSION = [];
session_regenerate_id(true);

$token = BotGuard::issue('contact');
expect($token !== '', 'émission d’un challenge');
$inspect = BotGuard::inspect('contact', $token, '', false);
expect($inspect['status'] === BotGuard::OK, 'challenge valide sans consommation');
expect($inspect['fast'] === true, 'soumission trop rapide = signal seulement');

$consumed = BotGuard::inspect('contact', $token, '', true);
expect($consumed['status'] === BotGuard::OK, 'consommation d’un challenge valide');
$replay = BotGuard::inspect('contact', $token, '', true);
expect($replay['status'] === BotGuard::RETRY, 'rejeu refusé');

$expired = BotGuard::issue('contact', time() - BotGuard::TTL - 10);
expect(BotGuard::inspect('contact', $expired, '', true)['status'] === BotGuard::RETRY, 'jeton expiré');

$fresh = BotGuard::issue('login');
$tampered = preg_replace('/[0-9a-f]{8}$/', 'deadbeef', $fresh) ?? $fresh;
expect($tampered !== $fresh, 'jeton altéré différent');
expect(BotGuard::inspect('login', $tampered, '', true)['status'] === BotGuard::RETRY, 'signature invalide');

$otherForm = BotGuard::issue('newsletter');
expect(BotGuard::inspect('contact', $otherForm, '', false)['status'] === BotGuard::RETRY, 'mauvais formulaire');

$honey = BotGuard::issue('contact');
expect(BotGuard::inspect('contact', $honey, 'https://spam.test', true)['status'] === BotGuard::HONEYPOT, 'honeypot rempli');

$fields = BotGuard::fields('register');
expect(str_contains($fields, 'name="company_website"'), 'honeypot rendu');
expect(str_contains($fields, 'name="_gate"'), 'jeton rendu');
expect(str_contains($fields, 'tabindex="-1"'), 'hors tabulation');
expect(str_contains($fields, 'aria-hidden="true"'), 'masqué aux aides techniques');

$_POST = [];
$request = new Request();
expect(BotGuard::consume('contact', $request) === BotGuard::RETRY, 'POST sans jeton');

$bound = BotGuard::issue('forgot');
$oldId = session_id();
session_regenerate_id(true);
expect(session_id() !== $oldId, 'nouvelle session');
expect(BotGuard::inspect('forgot', $bound, '', true)['status'] === BotGuard::RETRY, 'signature liée à la session');

$action = 'bot-test-' . bin2hex(random_bytes(8));
expect(rate_limited($action, 'a', 2, 60) === false, '1er hit accepté');
expect(rate_limited($action, 'a', 2, 60) === false, '2e hit accepté');
expect(rate_limited($action, 'a', 2, 60) === true, '3e hit limité');

$concAction = 'bot-conc-' . bin2hex(random_bytes(8));
$php = PHP_BINARY;
$script = __FILE__;
$procs = [];
$descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
for ($i = 0; $i < 4; $i++) {
    $cmd = escapeshellarg($php) . ' ' . escapeshellarg($script)
        . ' --rate-worker ' . escapeshellarg($concAction) . ' ip 10 60 8';
    $proc = proc_open($cmd, $descriptors, $pipes);
    if (!is_resource($proc)) {
        expect(false, 'lancement worker concurrence');
        continue;
    }
    $procs[] = ['proc' => $proc, 'out' => $pipes[1], 'err' => $pipes[2]];
}
$totalAccepted = 0;
foreach ($procs as $item) {
    $out = stream_get_contents($item['out']);
    fclose($item['out']);
    fclose($item['err']);
    proc_close($item['proc']);
    $totalAccepted += (int) trim((string) $out);
}
expect($totalAccepted === 10, 'limiteur atomique sous concurrence (acceptés=' . $totalAccepted . ')');

$hash = client_ip_hash();
expect(strlen($hash) === 64 && ctype_xdigit($hash), 'empreinte IP SHA-256');

echo "\n$passed ok, $failed ko\n";
ob_end_flush();
exit($failed === 0 ? 0 : 1);
