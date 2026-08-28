<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use RuntimeException;

final class Invoice
{
    public const STATUSES = [
        'issued' => 'À régler',
        'paid' => 'Réglée',
        'overdue' => 'En retard',
        'waived' => 'Offerte',
        'cancelled' => 'Annulée',
    ];

    public static function find(int $id): ?array
    {
        $row = Database::fetch('SELECT * FROM invoices WHERE id = ?', [$id]);
        return $row ? self::present($row) : null;
    }

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $rows = Database::fetchAll(
            'SELECT i.*, o.number AS order_number, o.amount AS order_amount,
                    u.first_name, u.last_name, u.email
             FROM invoices i
             JOIN orders o ON o.id = i.order_id
             JOIN users u ON u.id = i.seller_id
             ORDER BY i.issued_at DESC, i.id DESC'
        );
        return array_map([self::class, 'present'], $rows);
    }

    public static function countOverdue(): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM invoices
             WHERE status IN ("issued", "overdue") AND due_at < NOW()'
        );
        return (int) ($row['n'] ?? 0);
    }

    public static function forOrder(int $orderId): ?array
    {
        $row = Database::fetch('SELECT * FROM invoices WHERE order_id = ? ORDER BY id DESC LIMIT 1', [$orderId]);
        return $row ? self::present($row) : null;
    }

    /** @return list<array<string, mixed>> */
    public static function forSeller(int $sellerId): array
    {
        $rows = Database::fetchAll(
            'SELECT i.*, o.number AS order_number, o.amount AS order_amount
             FROM invoices i
             JOIN orders o ON o.id = i.order_id
             WHERE i.seller_id = ?
             ORDER BY i.issued_at DESC, i.id DESC',
            [$sellerId]
        );
        return array_map([self::class, 'present'], $rows);
    }

    /** @param array<string, mixed> $order */
    public static function issueForOrder(array $order): array
    {
        $existing = self::forOrder((int) $order['id']);
        if ($existing) {
            return $existing;
        }

        $amount = (int) ($order['commission_amount'] ?? 0);
        $percent = (float) ($order['commission_percent'] ?? 0);
        $free = !empty($order['first_mission_free']) || $amount <= 0;
        $dueDays = Commission::dueDays();
        $status = $free ? 'waived' : 'issued';

        Database::query(
            'INSERT INTO invoices (number, order_id, seller_id, amount, commission_percent, status, issued_at, due_at, paid_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), ?)',
            [
                self::nextNumber(),
                (int) $order['id'],
                (int) $order['seller_id'],
                $amount,
                $percent,
                $status,
                $dueDays,
                $free ? date('Y-m-d H:i:s') : null,
            ]
        );

        return self::find((int) Database::lastId()) ?? [];
    }

    public static function sellerIsBlocked(int $sellerId): bool
    {
        return self::blockingInvoice($sellerId) !== null;
    }

    public static function blockingInvoice(int $sellerId): ?array
    {
        $row = Database::fetch(
            'SELECT * FROM invoices
             WHERE seller_id = ?
               AND status IN ("issued", "overdue")
               AND due_at < NOW()
             ORDER BY due_at ASC
             LIMIT 1',
            [$sellerId]
        );
        return $row ? self::present($row) : null;
    }

    public static function pendingInvoice(int $sellerId): ?array
    {
        $row = Database::fetch(
            'SELECT * FROM invoices
             WHERE seller_id = ?
               AND status = "issued"
               AND due_at >= NOW()
             ORDER BY due_at ASC
             LIMIT 1',
            [$sellerId]
        );
        return $row ? self::present($row) : null;
    }

    public static function assertCanOffer(int $sellerId): void
    {
        $invoice = self::blockingInvoice($sellerId);
        if (!$invoice) {
            return;
        }
        throw new RuntimeException(
            'Une facture de commission (' . $invoice['number'] . ') est échue. '
            . 'Réglez-la pour proposer à nouveau des prestations.'
        );
    }

    public static function markOverdue(): int
    {
        Database::query(
            'UPDATE invoices
             SET status = "overdue"
             WHERE status = "issued" AND due_at < NOW()'
        );
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM invoices WHERE status = "overdue"'
        );
        return (int) ($row['n'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public static function overdueUnnotified(): array
    {
        return Database::fetchAll(
            'SELECT * FROM invoices
             WHERE status = "overdue"
             ORDER BY due_at ASC'
        );
    }

    public static function markPaid(int $id): ?array
    {
        Database::query(
            'UPDATE invoices SET status = "paid", paid_at = NOW() WHERE id = ? AND status IN ("issued", "overdue")',
            [$id]
        );
        return self::find($id);
    }

    public static function nextNumber(): string
    {
        $year = date('Y');
        $row = Database::fetch(
            'SELECT number FROM invoices WHERE number LIKE ? ORDER BY id DESC LIMIT 1',
            ['COM-' . $year . '-%']
        );
        $next = 1;
        if ($row && preg_match('/COM-\d{4}-(\d+)/', (string) $row['number'], $m)) {
            $next = (int) $m[1] + 1;
        }
        return sprintf('COM-%s-%05d', $year, $next);
    }

    /** @param array<string, mixed> $row */
    public static function present(array $row): array
    {
        $status = (string) ($row['status'] ?? 'issued');
        $dueAt = $row['due_at'] ?? null;
        $overdue = in_array($status, ['issued', 'overdue'], true)
            && $dueAt
            && strtotime((string) $dueAt) < time();

        $row['status_label'] = $overdue && $status === 'issued'
            ? self::STATUSES['overdue']
            : (self::STATUSES[$status] ?? $status);
        $row['amount_label'] = format_euros((int) ($row['amount'] ?? 0));
        $row['due_label'] = $dueAt ? date('d/m/Y', strtotime((string) $dueAt)) : '';
        $row['issued_label'] = !empty($row['issued_at']) ? date('d/m/Y', strtotime((string) $row['issued_at'])) : '';
        $row['is_overdue'] = $overdue;
        $row['is_open'] = in_array($status, ['issued', 'overdue'], true);
        $row['href'] = '/espace/facturation';
        $row['seller'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        return $row;
    }
}
