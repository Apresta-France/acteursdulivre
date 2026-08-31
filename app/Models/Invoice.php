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

    public static function findForSeller(int $id, int $sellerId): ?array
    {
        $row = Database::fetch(
            'SELECT i.*, o.number AS order_number, o.amount AS order_amount
             FROM invoices i
             JOIN orders o ON o.id = i.order_id
             WHERE i.id = ? AND i.seller_id = ?',
            [$id, $sellerId]
        );
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

    public static function countOpenForSeller(int $sellerId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM invoices
             WHERE seller_id = ?
               AND status IN ("issued", "overdue")',
            [$sellerId]
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

        $params = [
            (int) $order['id'],
            (int) $order['seller_id'],
            $amount,
            $percent,
            $status,
            $dueDays,
            $free ? date('Y-m-d H:i:s') : null,
        ];
        $lastError = null;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                Database::query(
                    'INSERT INTO invoices (number, order_id, seller_id, amount, commission_percent, status, issued_at, due_at, paid_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), ?)',
                    [self::nextNumber(), ...$params]
                );
                $lastError = null;
                break;
            } catch (\PDOException $e) {
                $lastError = $e;
                if (!str_contains($e->getMessage(), 'Duplicate')) {
                    throw $e;
                }
            }
        }
        if ($lastError !== null) {
            throw $lastError;
        }

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

    public static function assertCanOffer(int $sellerId, bool $forBuyer = false): void
    {
        $invoice = self::blockingInvoice($sellerId);
        if (!$invoice) {
            return;
        }
        if ($forBuyer) {
            throw new RuntimeException('Ce prestataire ne peut plus prendre de mission pour le moment.');
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

    public static function cancelOpenForOrder(int $orderId): void
    {
        Database::query(
            'UPDATE invoices SET status = "cancelled"
             WHERE order_id = ? AND status IN ("issued", "overdue")',
            [$orderId]
        );
    }

    public static function markPaid(int $id): ?array
    {
        Database::query(
            'UPDATE invoices SET status = "paid", paid_at = NOW() WHERE id = ? AND status IN ("issued", "overdue")',
            [$id]
        );
        $invoice = self::find($id);
        if ($invoice) {
            OrderMilestone::closeAfterCommissionPaid($invoice);
        }
        return $invoice;
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
        $row['pdf_href'] = '/espace/facturation/' . (int) ($row['id'] ?? 0) . '/pdf';
        $row['seller'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        return $row;
    }
}
