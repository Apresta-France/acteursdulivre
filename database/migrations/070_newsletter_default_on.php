<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('notify_newsletter', $cols, true)) {
        $pdo->exec('ALTER TABLE users MODIFY notify_newsletter TINYINT(1) NOT NULL DEFAULT 1');
    }
};
