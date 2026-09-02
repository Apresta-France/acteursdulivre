<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    if (!in_array('notify_forum_followed', $cols, true)) {
        $pdo->exec(
            'ALTER TABLE users
             ADD COLUMN notify_forum_followed TINYINT(1) NOT NULL DEFAULT 1
             AFTER notify_newsletter'
        );
    }
    if (!in_array('notify_forum_mine', $cols, true)) {
        $pdo->exec(
            'ALTER TABLE users
             ADD COLUMN notify_forum_mine TINYINT(1) NOT NULL DEFAULT 1
             AFTER notify_forum_followed'
        );
    }
};
