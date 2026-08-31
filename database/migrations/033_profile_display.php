<?php

declare(strict_types=1);

return static function (\PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM profiles')->fetchAll(PDO::FETCH_COLUMN);
    $add = static function (string $sql) use ($pdo): void {
        $pdo->exec($sql);
    };

    if (!in_array('work_mode', $cols, true)) {
        $add('ALTER TABLE profiles ADD COLUMN work_mode VARCHAR(20) NULL AFTER city');
    }
    if (!in_array('response_time', $cols, true)) {
        $add('ALTER TABLE profiles ADD COLUMN response_time VARCHAR(20) NULL AFTER availability_status');
    }
    if (!in_array('name_mode', $cols, true)) {
        $add('ALTER TABLE profiles ADD COLUMN name_mode VARCHAR(20) NOT NULL DEFAULT "full" AFTER title');
    }
    if (!in_array('public_name', $cols, true)) {
        $add('ALTER TABLE profiles ADD COLUMN public_name VARCHAR(160) NULL AFTER name_mode');
    }
};
