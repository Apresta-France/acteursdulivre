<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $indexes = $pdo->query('SHOW INDEX FROM conversations')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($indexes as $index) {
        if (($index['Key_name'] ?? '') === 'uniq_conv_order') {
            return;
        }
    }

    $dupes = $pdo->query(
        'SELECT order_id FROM conversations WHERE order_id IS NOT NULL GROUP BY order_id HAVING COUNT(*) > 1'
    )->fetchAll(PDO::FETCH_COLUMN);
    foreach ($dupes as $orderId) {
        $rows = $pdo->prepare('SELECT id FROM conversations WHERE order_id = ? ORDER BY id ASC');
        $rows->execute([(int) $orderId]);
        $ids = $rows->fetchAll(PDO::FETCH_COLUMN);
        array_shift($ids);
        foreach ($ids as $conversationId) {
            $pdo->prepare('DELETE FROM conversation_participants WHERE conversation_id = ?')->execute([(int) $conversationId]);
            $pdo->prepare('DELETE FROM messages WHERE conversation_id = ?')->execute([(int) $conversationId]);
            $pdo->prepare('DELETE FROM conversations WHERE id = ?')->execute([(int) $conversationId]);
        }
    }

    $pdo->exec('ALTER TABLE conversations ADD UNIQUE KEY uniq_conv_order (order_id)');
};
