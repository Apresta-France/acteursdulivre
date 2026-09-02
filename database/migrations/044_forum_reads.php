<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $cols = $pdo->query('SHOW COLUMNS FROM forum_topic_follows')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    if (!in_array('last_read_at', $cols, true)) {
        $pdo->exec(
            'ALTER TABLE forum_topic_follows
             ADD COLUMN last_read_at DATETIME NULL AFTER created_at'
        );
        $pdo->exec('UPDATE forum_topic_follows SET last_read_at = COALESCE(created_at, NOW())');
    }
    if (!in_array('last_read_post_id', $cols, true)) {
        $pdo->exec(
            'ALTER TABLE forum_topic_follows
             ADD COLUMN last_read_post_id INT UNSIGNED NULL AFTER last_read_at'
        );
        // Tout le déjà suivi est considéré comme lu jusqu'au dernier message actuel.
        $pdo->exec(
            'UPDATE forum_topic_follows f
             LEFT JOIN (
               SELECT topic_id, MAX(id) AS max_id
               FROM forum_posts
               WHERE status = "visible"
               GROUP BY topic_id
             ) p ON p.topic_id = f.topic_id
             SET f.last_read_post_id = COALESCE(p.max_id, 0)'
        );
    }
};
