<?php

declare(strict_types=1);

define('ADL_ROOT', dirname(__DIR__));
require ADL_ROOT . '/app/bootstrap.php';

use Adl\Core\Database;

$pdo = Database::pdo();
$cols = $pdo->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_COLUMN);
echo "orders cols: " . implode(', ', $cols) . PHP_EOL;
$tables = $pdo->query("SHOW TABLES LIKE 'service_options'")->fetchAll();
echo "service_options: " . (count($tables) ? 'yes' : 'no') . PHP_EOL;
$migs = $pdo->query('SELECT name FROM migrations ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
echo "migrations:\n" . implode("\n", $migs) . PHP_EOL;
$conv = $pdo->query('SHOW COLUMNS FROM conversations')->fetchAll(PDO::FETCH_COLUMN);
echo "conversations cols: " . implode(', ', $conv) . PHP_EOL;
$users = $pdo->query('SELECT id, email, role, seeks_services, offers_services FROM users ORDER BY id ASC LIMIT 20')->fetchAll(PDO::FETCH_ASSOC);
echo "users:\n";
foreach ($users as $u) {
    echo json_encode($u, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
$svcs = $pdo->query('SELECT id, slug, title, status, user_id FROM services WHERE status = "published" LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
echo "services:\n";
foreach ($svcs as $s) {
    echo json_encode($s, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
