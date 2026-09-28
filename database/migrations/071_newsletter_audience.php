<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM newsletter_campaigns')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('audience', $cols, true)) {
        $pdo->exec(
            'ALTER TABLE newsletter_campaigns
             ADD COLUMN audience VARCHAR(20) NOT NULL DEFAULT "confirmed" AFTER source'
        );
    }
};
