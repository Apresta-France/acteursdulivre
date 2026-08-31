<?php

declare(strict_types=1);

define('ADL_ROOT', dirname(__DIR__));
require ADL_ROOT . '/app/bootstrap.php';

use Adl\Core\HourlyCron;
use Adl\Core\NewsletterCron;

$jobs = [
    'relances' => [HourlyCron::class, 'run'],
    'newsletter' => [NewsletterCron::class, 'run'],
];

$args = array_values(array_filter($argv ?? [], static fn (string $a): bool => $a !== '--force'));
array_shift($args);
$task = $args[0] ?? '';
$force = in_array('--force', $argv ?? [], true);

if ($task === '' || $task === 'list') {
    echo json_encode([
        'ok' => true,
        'hint' => 'php bin/cron.php {tache}',
        'jobs' => array_keys($jobs),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    exit(0);
}

if (!isset($jobs[$task])) {
    fwrite(STDERR, "Tâche inconnue : {$task}\nTâches : " . implode(', ', array_keys($jobs)) . "\n");
    exit(1);
}

$result = $jobs[$task]($force);
$result['job'] = $task;
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
exit(!empty($result['ok']) ? 0 : 1);
