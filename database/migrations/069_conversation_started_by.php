<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM conversations')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('started_by', $cols, true)) {
        $pdo->exec('ALTER TABLE conversations ADD COLUMN started_by INT UNSIGNED NULL AFTER service_id');
    }

    $pdo->exec(
        'UPDATE conversations c
         INNER JOIN (
             SELECT m.conversation_id, m.user_id
             FROM messages m
             INNER JOIN (
                 SELECT conversation_id, MIN(id) AS id
                 FROM messages
                 GROUP BY conversation_id
             ) first_msg ON first_msg.id = m.id
         ) starter ON starter.conversation_id = c.id
         SET c.started_by = starter.user_id
         WHERE c.started_by IS NULL'
    );
};
