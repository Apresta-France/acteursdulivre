<?php

declare(strict_types=1);

return static function (\PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM profiles')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('does', $cols, true)) {
        $pdo->exec('ALTER TABLE profiles ADD COLUMN does TEXT NULL AFTER presentation');
    }
    if (!in_array('does_not', $cols, true)) {
        $pdo->exec('ALTER TABLE profiles ADD COLUMN does_not TEXT NULL AFTER does');
    }
};
