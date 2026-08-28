<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class Order
{
    public const STATUSES = [
        'pending' => 'En attente',
        'in_progress' => 'En cours',
        'delivered' => 'Livrée',
        'confirmed' => 'Validée',
        'paid' => 'Réglée',
        'cancelled' => 'Annulée',
        'dispute' => 'En litige',
    ];

    public const COMPLETABLE = ['delivered', 'in_progress'];

    public static function find(int $id): ?array
    {
        $row = Database::fetch(
            'SELECT o.*,
                    s.title AS service_title,
                    m.title AS mission_title,
                    b.first_name AS buyer_first, b.last_name AS buyer_last,
                    sl.first_name AS seller_first, sl.last_name AS seller_last
             FROM orders o
             LEFT JOIN services s ON s.id = o.service_id
             LEFT JOIN missions m ON m.id = o.mission_id
             JOIN users b ON b.id = o.buyer_id
             JOIN users sl ON sl.id = o.seller_id
             WHERE o.id = ?',
            [$id]
        );
        return $row ? self::present($row) : null;
    }

    public static function completedCountForSeller(int $sellerId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM orders
             WHERE seller_id = ? AND (confirmed_at IS NOT NULL OR status IN ("confirmed", "paid"))',
            [$sellerId]
        );
        return (int) ($row['n'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public static function awaitingReviewForBuyer(int $buyerId): array
    {
        $rows = Database::fetchAll(
            'SELECT o.*,
                    s.title AS service_title,
                    m.title AS mission_title,
                    b.first_name AS buyer_first, b.last_name AS buyer_last,
                    sl.first_name AS seller_first, sl.last_name AS seller_last
             FROM orders o
             LEFT JOIN services s ON s.id = o.service_id
             LEFT JOIN missions m ON m.id = o.mission_id
             JOIN users b ON b.id = o.buyer_id
             JOIN users sl ON sl.id = o.seller_id
             LEFT JOIN reviews r ON r.order_id = o.id AND r.author_id = o.buyer_id
             WHERE o.buyer_id = ?
               AND o.status IN ("delivered", "in_progress")
               AND o.confirmed_at IS NULL
               AND r.id IS NULL
             ORDER BY o.created_at DESC',
            [$buyerId]
        );
        return array_map([self::class, 'present'], $rows);
    }

    /**
     * @param array{quality: int, efficiency: int, satisfaction: int, body?: string} $ratings
     * @return array{order: array<string, mixed>, review: array<string, mixed>, invoice: array<string, mixed>}
     */
    public static function confirmByBuyer(int $orderId, int $buyerId, array $ratings, ?string $ip = null): array
    {
        $order = self::find($orderId);
        if (!$order || (int) $order['buyer_id'] !== $buyerId) {
            throw new \RuntimeException('Cette commande est introuvable.');
        }
        if (!empty($order['confirmed_at']) || in_array((string) $order['status'], ['confirmed', 'paid', 'cancelled'], true)) {
            throw new \RuntimeException('Cette mission a déjà été validée.');
        }
        if (!in_array((string) $order['status'], self::COMPLETABLE, true)) {
            throw new \RuntimeException('La mission doit être livrée avant d\'être validée.');
        }

        $quote = Commission::quoteForSeller((int) $order['seller_id']);
        $amount = (int) ($order['amount'] ?? 0);
        $commissionAmount = Commission::amount($amount, $quote['percent']);

        Database::query(
            'UPDATE orders
             SET status = "confirmed",
                 confirmed_at = NOW(),
                 commission_percent = ?,
                 commission_amount = ?,
                 first_mission_free = ?,
                 buyer_cgv_at = NOW()
             WHERE id = ?',
            [$quote['percent'], $commissionAmount, $quote['first_free'] ? 1 : 0, $orderId]
        );

        LegalAcceptance::record($buyerId, 'cgv', 'order_confirm', $ip);

        $review = Review::createForOrder($orderId, $buyerId, (int) $order['seller_id'], $ratings);
        $fresh = self::find($orderId) ?? $order;
        $invoice = Invoice::issueForOrder($fresh);

        if ((int) $invoice['amount'] > 0 && ($invoice['status'] ?? '') !== 'waived') {
            Notification::create(
                (int) $order['seller_id'],
                'Facture de commission ' . $invoice['number'],
                'Le client a validé la mission. Votre facture de commission (' . $invoice['amount_label'] . ') est à régler avant le ' . $invoice['due_label'] . '.',
                '/espace/facturation',
                'invoice_issued',
                'invoice',
                (int) ($invoice['id'] ?? 0)
            );
        } else {
            Notification::create(
                (int) $order['seller_id'],
                'Mission validée — première mission offerte',
                'Le client a validé et noté la mission. Aucune commission n\'est due sur cette première mission.',
                '/espace/facturation',
                'order_confirmed',
                'order',
                $orderId
            );
        }

        return ['order' => $fresh, 'review' => $review, 'invoice' => $invoice];
    }

    public static function setStatus(int $id, string $status): void
    {
        if (!isset(self::STATUSES[$status])) {
            throw new \InvalidArgumentException('Statut de commande invalide.');
        }
        if (!self::find($id)) {
            throw new \RuntimeException('Commande introuvable.');
        }
        Database::query('UPDATE orders SET status = ? WHERE id = ?', [$status, $id]);
    }

    /** @return list<array<string, mixed>> */
    public static function byStatus(string $status): array
    {
        if (!isset(self::STATUSES[$status])) {
            return [];
        }
        $rows = Database::fetchAll(
            'SELECT o.*,
                    s.title AS service_title,
                    m.title AS mission_title,
                    b.first_name AS buyer_first, b.last_name AS buyer_last,
                    sl.first_name AS seller_first, sl.last_name AS seller_last
             FROM orders o
             LEFT JOIN services s ON s.id = o.service_id
             LEFT JOIN missions m ON m.id = o.mission_id
             JOIN users b ON b.id = o.buyer_id
             JOIN users sl ON sl.id = o.seller_id
             WHERE o.status = ?
             ORDER BY o.created_at DESC',
            [$status]
        );
        return array_map([self::class, 'present'], $rows);
    }

    public static function countByStatus(string $status): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS n FROM orders WHERE status = ?', [$status]);
        return (int) ($row['n'] ?? 0);
    }

    public static function countAll(): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS n FROM orders');
        return (int) ($row['n'] ?? 0);
    }

    public static function sumAmount(): int
    {
        $row = Database::fetch('SELECT COALESCE(SUM(amount), 0) AS n FROM orders');
        return (int) ($row['n'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public static function forBuyer(int $userId): array
    {
        return self::forParty('buyer_id', $userId);
    }

    /** @return list<array<string, mixed>> */
    public static function forSeller(int $userId): array
    {
        return self::forParty('seller_id', $userId);
    }

    /** @return list<array<string, mixed>> */
    public static function recent(int $limit = 40): array
    {
        $rows = Database::fetchAll(
            'SELECT o.*,
                    s.title AS service_title,
                    m.title AS mission_title,
                    b.first_name AS buyer_first, b.last_name AS buyer_last,
                    sl.first_name AS seller_first, sl.last_name AS seller_last
             FROM orders o
             LEFT JOIN services s ON s.id = o.service_id
             LEFT JOIN missions m ON m.id = o.mission_id
             JOIN users b ON b.id = o.buyer_id
             JOIN users sl ON sl.id = o.seller_id
             ORDER BY o.created_at DESC
             LIMIT ' . max(1, $limit)
        );

        return array_map([self::class, 'present'], $rows);
    }

    /** @return list<array<string, mixed>> */
    private static function forParty(string $column, int $userId): array
    {
        $allowed = ['buyer_id', 'seller_id'];
        if (!in_array($column, $allowed, true)) {
            return [];
        }

        $rows = Database::fetchAll(
            "SELECT o.*,
                    s.title AS service_title,
                    m.title AS mission_title,
                    b.first_name AS buyer_first, b.last_name AS buyer_last,
                    sl.first_name AS seller_first, sl.last_name AS seller_last
             FROM orders o
             LEFT JOIN services s ON s.id = o.service_id
             LEFT JOIN missions m ON m.id = o.mission_id
             JOIN users b ON b.id = o.buyer_id
             JOIN users sl ON sl.id = o.seller_id
             WHERE o.{$column} = ?
             ORDER BY o.created_at DESC",
            [$userId]
        );

        return array_map([self::class, 'present'], $rows);
    }

    /** @param array<string, mixed> $row */
    private static function present(array $row): array
    {
        $status = (string) ($row['status'] ?? 'pending');
        $title = (string) ($row['service_title'] ?: $row['mission_title'] ?: 'Commande');
        $buyer = trim(($row['buyer_first'] ?? '') . ' ' . ($row['buyer_last'] ?? ''));
        $seller = trim(($row['seller_first'] ?? '') . ' ' . ($row['seller_last'] ?? ''));
        $row['title'] = $title;
        $row['by'] = $buyer !== '' && $seller !== '' ? $buyer . ' → ' . $seller : ($seller ?: $buyer);
        $row['parties'] = $row['by'];
        $row['num'] = (string) ($row['number'] ?? '');
        $row['amount_label'] = format_euros((int) ($row['amount'] ?? 0));
        $row['status_label'] = self::STATUSES[$status] ?? $status;
        $row['status_tone'] = match ($status) {
            'paid', 'delivered', 'confirmed' => 'green',
            'dispute', 'cancelled' => 'orange',
            default => 'navy',
        };
        $row['when'] = time_ago($row['created_at'] ?? null);
        $row['can_confirm'] = empty($row['confirmed_at']) && in_array($status, self::COMPLETABLE, true);
        $row['confirm_href'] = '/espace/avis';
        $row['href'] = '/espace/suivi';
        $row['commission_label'] = isset($row['commission_amount'])
            ? format_euros((int) $row['commission_amount'])
            : '';
        return $row;
    }
}
