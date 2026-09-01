<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    foreach ([
        'siren' => 'VARCHAR(9) NULL',
        'einvoice_routing' => 'VARCHAR(64) NULL',
        'legal_form' => 'VARCHAR(32) NULL',
        'vat_exempt' => 'TINYINT(1) NOT NULL DEFAULT 0',
    ] as $col => $def) {
        if (!in_array($col, $cols, true)) {
            $pdo->exec('ALTER TABLE users ADD COLUMN ' . $col . ' ' . $def);
        }
    }

    $pdo->exec(
        "UPDATE users
            SET siren = LEFT(REPLACE(REPLACE(siret, ' ', ''), '.', ''), 9)
          WHERE (siren IS NULL OR siren = '')
            AND siret IS NOT NULL
            AND CHAR_LENGTH(REPLACE(REPLACE(siret, ' ', ''), '.', '')) = 14"
    );
};
