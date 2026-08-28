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

    /**
     * @param array{buyer_id: int, seller_id: int, amount?: int, service_id?: ?int, mission_id?: ?int, brief?: ?string, package_name?: ?string} $data
     * @return array<string, mixed>
     */
    public static function create(array $data): array
    {
        $buyerId = (int) ($data['buyer_id'] ?? 0);
        $sellerId = (int) ($data['seller_id'] ?? 0);
        if ($buyerId < 1 || $sellerId < 1 || $buyerId === $sellerId) {
            throw new \RuntimeException('Cette commande ne peut pas être créée.');
        }
        Invoice::assertCanOffer($sellerId);

        $number = self::nextNumber();
        Database::query(
            'INSERT INTO orders (number, buyer_id, seller_id, service_id, mission_id, amount, status, brief, package_name, created_at)
             VALUES (?, ?, ?, ?, ?, ?, "pending", ?, ?, NOW())',
            [
                $number,
                $buyerId,
                $sellerId,
                $data['service_id'] ?? null,
                $data['mission_id'] ?? null,
                (int) ($data['amount'] ?? 0),
                trim((string) ($data['brief'] ?? '')) ?: null,
                trim((string) ($data['package_name'] ?? '')) ?: null,
            ]
        );

        $order = self::find((int) Database::lastId());
        if (!$order) {
            throw new \RuntimeException('La commande n\'a pas pu être créée.');
        }

        Notification::create(
            $sellerId,
            'Nouvelle commande ' . $order['num'],
            'Un porteur de projet a ouvert « ' . $order['title'] . ' ». Acceptez-la pour démarrer.',
            '/espace/suivi/' . (int) $order['id'],
            'order_created',
            'order',
            (int) $order['id']
        );

        return $order;
    }

    public static function acceptBySeller(int $id, int $sellerId): array
    {
        $order = self::requireParty($id, $sellerId, 'seller');
        if (($order['status'] ?? '') !== 'pending') {
            throw new \RuntimeException('Cette commande n\'est plus en attente.');
        }
        Database::query(
            'UPDATE orders SET status = "in_progress", accepted_at = NOW() WHERE id = ? AND status = "pending"',
            [$id]
        );
        Notification::create(
            (int) $order['buyer_id'],
            'Commande acceptée ' . $order['num'],
            'Le prestataire a accepté « ' . $order['title'] . ' » et démarre le travail.',
            '/espace/suivi/' . $id,
            'order_accepted',
            'order',
            $id
        );
        return self::find($id) ?? $order;
    }

    public static function deliverBySeller(int $id, int $sellerId): array
    {
        $order = self::requireParty($id, $sellerId, 'seller');
        if (($order['status'] ?? '') !== 'in_progress') {
            throw new \RuntimeException('Acceptez d\'abord la commande avant de livrer.');
        }
        Database::query(
            'UPDATE orders SET status = "delivered", delivered_at = NOW() WHERE id = ? AND status = "in_progress"',
            [$id]
        );
        Notification::create(
            (int) $order['buyer_id'],
            'Livraison à valider ' . $order['num'],
            '« ' . $order['title'] . ' » est livrée. Validez et notez la mission pour clôturer.',
            '/espace/suivi/' . $id,
            'order_delivered',
            'order',
            $id
        );
        return self::find($id) ?? $order;
    }

    public static function openDispute(int $id, int $userId, string $reason): array
    {
        $order = self::requireParty($id, $userId);
        $reason = trim($reason);
        if ($reason === '') {
            throw new \RuntimeException('Indiquez le motif du litige.');
        }
        $current = (string) ($order['status'] ?? 'pending');
        if (!in_array($current, ['pending', 'in_progress', 'delivered'], true)) {
            throw new \RuntimeException('Un litige ne peut être ouvert que sur une commande en cours ou livrée.');
        }
        Database::query(
            'UPDATE orders SET status = "dispute", dispute_reason = ?, dispute_opened_by = ?, dispute_at = NOW() WHERE id = ?',
            [$reason, $userId, $id]
        );
        $otherId = (int) $order['buyer_id'] === $userId ? (int) $order['seller_id'] : (int) $order['buyer_id'];
        Notification::create(
            $otherId,
            'Litige ouvert sur ' . $order['num'],
            'Un litige a été signalé : ' . $reason,
            '/espace/suivi/' . $id,
            'order_dispute',
            'order',
            $id
        );
        return self::find($id) ?? $order;
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        $order = self::find($id);
        if (!$order) {
            return null;
        }
        if ((int) $order['buyer_id'] !== $userId && (int) $order['seller_id'] !== $userId) {
            return null;
        }
        return $order;
    }

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

        $quality = Review::score($ratings['quality'] ?? 0);
        $efficiency = Review::score($ratings['efficiency'] ?? 0);
        $satisfaction = Review::score($ratings['satisfaction'] ?? 0);
        $ratings = [
            'quality' => $quality,
            'efficiency' => $efficiency,
            'satisfaction' => $satisfaction,
            'body' => $ratings['body'] ?? '',
        ];

        $result = Database::transaction(static function () use ($orderId, $buyerId, $ratings, $ip, $order): array {
            Database::query('SELECT id FROM users WHERE id = ? FOR UPDATE', [(int) $order['seller_id']]);
            $locked = self::find($orderId);
            if (!$locked || (int) $locked['buyer_id'] !== $buyerId) {
                throw new \RuntimeException('Cette commande est introuvable.');
            }
            if (!empty($locked['confirmed_at']) || in_array((string) $locked['status'], ['confirmed', 'paid', 'cancelled'], true)) {
                throw new \RuntimeException('Cette mission a déjà été validée.');
            }
            if (!in_array((string) $locked['status'], self::COMPLETABLE, true)) {
                throw new \RuntimeException('La mission doit être livrée avant d\'être validée.');
            }

            $quote = Commission::quoteForSeller((int) $locked['seller_id']);
            $amount = (int) ($locked['amount'] ?? 0);
            $commissionAmount = Commission::amount($amount, $quote['percent']);

            $updated = Database::query(
                'UPDATE orders
                 SET status = "confirmed",
                     confirmed_at = NOW(),
                     commission_percent = ?,
                     commission_amount = ?,
                     first_mission_free = ?,
                     buyer_cgv_at = NOW()
                 WHERE id = ? AND confirmed_at IS NULL AND status IN ("delivered", "in_progress")',
                [$quote['percent'], $commissionAmount, $quote['first_free'] ? 1 : 0, $orderId]
            );
            if ($updated->rowCount() < 1) {
                throw new \RuntimeException('Cette mission a déjà été validée.');
            }

            LegalAcceptance::record($buyerId, 'cgv', 'order_confirm', $ip);

            $review = Review::createForOrder($orderId, $buyerId, (int) $locked['seller_id'], $ratings);
            $fresh = self::find($orderId) ?? $locked;
            $invoice = Invoice::issueForOrder($fresh);

            return ['order' => $fresh, 'review' => $review, 'invoice' => $invoice];
        });

        $invoice = $result['invoice'];
        $order = $result['order'];

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

        return $result;
    }

    public static function setStatus(int $id, string $status): void
    {
        if (!isset(self::STATUSES[$status])) {
            throw new \InvalidArgumentException('Statut de commande invalide.');
        }
        $order = self::find($id);
        if (!$order) {
            throw new \RuntimeException('Commande introuvable.');
        }
        $current = (string) ($order['status'] ?? 'pending');

        if ($status === 'dispute') {
            if (!in_array($current, ['pending', 'in_progress', 'delivered'], true)) {
                throw new \RuntimeException('Un litige ne peut être ouvert que sur une commande en cours ou livrée.');
            }
            Database::query('UPDATE orders SET status = ? WHERE id = ?', [$status, $id]);
            return;
        }

        if ($status === 'cancelled') {
            Database::transaction(static function () use ($id): void {
                Database::query('UPDATE orders SET status = ? WHERE id = ?', ['cancelled', $id]);
                Invoice::cancelOpenForOrder($id);
            });
            return;
        }

        if ($status === 'in_progress' && $current === 'dispute') {
            $restore = !empty($order['confirmed_at']) ? 'confirmed' : 'in_progress';
            Database::query('UPDATE orders SET status = ? WHERE id = ?', [$restore, $id]);
            return;
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
        $row = Database::fetch(
            'SELECT COALESCE(SUM(amount), 0) AS n FROM orders WHERE status IN ("confirmed", "paid")'
        );
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
        $row['can_accept'] = $status === 'pending';
        $row['can_deliver'] = $status === 'in_progress';
        $row['can_dispute'] = in_array($status, ['pending', 'in_progress', 'delivered'], true);
        $row['confirm_href'] = '/espace/avis';
        $row['href'] = '/espace/suivi/' . (int) ($row['id'] ?? 0);
        $row['commission_label'] = !empty($row['confirmed_at']) || ($row['commission_percent'] ?? null) !== null
            ? format_euros((int) ($row['commission_amount'] ?? 0))
            : '';
        return $row;
    }

    private static function nextNumber(): string
    {
        $year = date('Y');
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM orders WHERE number LIKE ?',
            ['ADL-' . $year . '-%']
        );
        return sprintf('ADL-%s-%04d', $year, ((int) ($row['n'] ?? 0)) + 1);
    }

    /** @return array<string, mixed> */
    private static function requireParty(int $id, int $userId, ?string $role = null): array
    {
        $order = self::find($id);
        if (!$order) {
            throw new \RuntimeException('Cette commande est introuvable.');
        }
        $buyer = (int) $order['buyer_id'] === $userId;
        $seller = (int) $order['seller_id'] === $userId;
        if ($role === 'seller' && !$seller) {
            throw new \RuntimeException('Seul le prestataire peut effectuer cette action.');
        }
        if ($role === 'buyer' && !$buyer) {
            throw new \RuntimeException('Seul le porteur de projet peut effectuer cette action.');
        }
        if (!$buyer && !$seller) {
            throw new \RuntimeException('Cette commande est introuvable.');
        }
        return $order;
    }
}
