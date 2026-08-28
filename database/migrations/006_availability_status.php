<?php

declare(strict_types=1);

return static function (\PDO $pdo): void {
    $pdo->exec(
        "ALTER TABLE profiles
            ADD COLUMN availability_status ENUM('available', 'busy') NOT NULL DEFAULT 'available' AFTER availability"
    );
};
