<?php

declare(strict_types=1);

return static function (\PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM profiles')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('socials_json', $cols, true)) {
        $pdo->exec('ALTER TABLE profiles ADD COLUMN socials_json TEXT NULL AFTER website');
    }
};
