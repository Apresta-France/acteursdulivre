<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        "INSERT INTO settings (setting_key, setting_value) VALUES
            ('loyalty_order_threshold', '12')
         ON DUPLICATE KEY UPDATE setting_key = setting_key"
    );
};
