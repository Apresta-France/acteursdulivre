<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $indexes = $pdo->query('SHOW INDEX FROM invoices')->fetchAll(PDO::FETCH_ASSOC);
    $hasUniq = false;
    foreach ($indexes as $index) {
        if (($index['Key_name'] ?? '') === 'uniq_invoice_order') {
            $hasUniq = true;
            break;
        }
    }
    if ($hasUniq) {
        return;
    }

    $dupes = $pdo->query(
        'SELECT order_id FROM invoices GROUP BY order_id HAVING COUNT(*) > 1'
    )->fetchAll(PDO::FETCH_COLUMN);
    foreach ($dupes as $orderId) {
        $rows = $pdo->prepare('SELECT id FROM invoices WHERE order_id = ? ORDER BY id ASC');
        $rows->execute([(int) $orderId]);
        $ids = $rows->fetchAll(PDO::FETCH_COLUMN);
        array_shift($ids);
        if ($ids !== []) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare('DELETE FROM invoices WHERE id IN (' . $placeholders . ')')->execute($ids);
        }
    }

    $pdo->exec('ALTER TABLE invoices ADD UNIQUE KEY uniq_invoice_order (order_id)');
};
