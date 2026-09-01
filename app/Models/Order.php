<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Core\Mailer;

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
     * @param array{buyer_id: int, seller_id: int, amount?: int, service_id?: ?int, mission_id?: ?int, brief?: ?string, package_name?: ?string, options?: list<array<string, mixed>>} $data
     * @return array<string, mixed>
     */
    public static function create(array $data): array
    {
        $buyerId = (int) ($data['buyer_id'] ?? 0);
        $sellerId = (int) ($data['seller_id'] ?? 0);
        if ($buyerId < 1 || $sellerId < 1 || $buyerId === $sellerId) {
            throw new \RuntimeException('Cette commande ne peut pas être créée.');
        }
        Invoice::assertCanOffer($sellerId, true);

        $optionsJson = self::encodeOptions($data['options'] ?? []);
        $startupEnabled = !empty($data['startup_enabled']) ? 1 : 0;
        $startupKind = $startupEnabled
            ? ((string) ($data['startup_kind'] ?? '') === 'percent' ? 'percent' : 'amount')
            : null;
        $startupValue = $startupEnabled ? (int) ($data['startup_value'] ?? 0) : null;
        $deposit = (int) ($data['deposit_amount'] ?? 0);
        if ($startupEnabled && $deposit < 1 && $startupKind !== null) {
            $deposit = Service::computeStartupAmount((int) ($data['amount'] ?? 0), $startupKind, (int) $startupValue);
        }
        $params = [
            $buyerId,
            $sellerId,
            $data['service_id'] ?? null,
            $data['mission_id'] ?? null,
            (int) ($data['amount'] ?? 0),
            $deposit,
            trim((string) ($data['brief'] ?? '')) ?: null,
            trim((string) ($data['package_name'] ?? '')) ?: null,
            $optionsJson,
            $startupEnabled,
            $startupKind,
            $startupValue,
        ];
        $lastError = null;
        $orderId = 0;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                Database::query(
                    'INSERT INTO orders (number, buyer_id, seller_id, service_id, mission_id, amount, deposit_amount, status, brief, package_name, options_json, startup_enabled, startup_kind, startup_value, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, "pending", ?, ?, ?, ?, ?, ?, NOW())',
                    [self::nextNumber(), ...$params]
                );
                $orderId = (int) Database::lastId();
                $lastError = null;
                break;
            } catch (\PDOException $e) {
                $lastError = $e;
                if (!str_contains($e->getMessage(), 'Duplicate')) {
                    throw $e;
                }
            }
        }
        if ($lastError !== null || $orderId < 1) {
            throw new \RuntimeException('La commande n\'a pas pu être créée.');
        }

        $order = self::find($orderId);
        if (!$order) {
            throw new \RuntimeException('La commande n\'a pas pu être créée.');
        }

        Notification::create(
            $sellerId,
            'Nouvelle commande ' . $order['num'],
            'Un porteur de projet a ouvert « ' . $order['title'] . ' ». Envoyez le devis pour lancer les jalons.',
            '/espace/suivi/' . (int) $order['id'],
            'order_created',
            'order',
            (int) $order['id']
        );
        Mailer::notify(User::find($sellerId), 'transactional', 'nouvelle-commande', [
            'numero' => (string) $order['num'],
            'titre' => (string) $order['title'],
            'lien' => url('/espace/suivi/' . (int) $order['id']),
        ]);

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
        Mailer::notify(User::find((int) $order['buyer_id']), 'jalons', 'commande-acceptee', [
            'numero' => (string) $order['num'],
            'titre' => (string) $order['title'],
            'lien' => url('/espace/suivi/' . $id),
        ]);
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
        Mailer::notify(User::find((int) $order['buyer_id']), 'jalons', 'commande-livree', [
            'numero' => (string) $order['num'],
            'titre' => (string) $order['title'],
            'lien' => url('/espace/suivi/' . $id),
        ]);
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
        Mailer::notify(User::find($otherId), 'transactional', 'commande-litige', [
            'numero' => (string) $order['num'],
            'titre' => (string) $order['title'],
            'motif' => $reason,
            'lien' => url('/espace/suivi/' . $id),
        ]);
        return self::find($id) ?? $order;
    }

    public static function findPendingForService(int $buyerId, int $serviceId): ?array
    {
        if ($buyerId < 1 || $serviceId < 1) {
            return null;
        }
        $row = Database::fetch(
            'SELECT id FROM orders
             WHERE buyer_id = ? AND service_id = ? AND status = "pending"
             ORDER BY id DESC LIMIT 1',
            [$buyerId, $serviceId]
        );
        return $row ? self::find((int) $row['id']) : null;
    }

    /**
     * @param array{amount?: int, brief?: ?string, package_name?: ?string, options?: list<array<string, mixed>>} $data
     * @return array<string, mixed>
     */
    public static function updatePendingDetails(int $id, array $data): array
    {
        $order = self::find($id);
        if (!$order || ($order['status'] ?? '') !== 'pending') {
            throw new \RuntimeException('Cette commande n\'est plus en attente.');
        }
        $amount = (int) ($data['amount'] ?? 0);
        $deposit = (int) ($order['deposit_amount'] ?? 0);
        if (!empty($order['startup_enabled']) && (string) ($order['startup_kind'] ?? '') === 'percent') {
            $deposit = Service::computeStartupAmount($amount, 'percent', (int) ($order['startup_value'] ?? 0));
        }
        $deposit = min($deposit, max(0, $amount));
        Database::query(
            'UPDATE orders SET amount = ?, deposit_amount = ?, brief = ?, package_name = ?, options_json = ? WHERE id = ? AND status = "pending"',
            [
                $amount,
                $deposit,
                trim((string) ($data['brief'] ?? '')) ?: null,
                trim((string) ($data['package_name'] ?? '')) ?: null,
                self::encodeOptions($data['options'] ?? []),
                $id,
            ]
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
        $row = self::fetchRow($id);
        return $row ? OrderMilestone::hydrateOrder(self::present($row)) : null;
    }

    public static function findBare(int $id): ?array
    {
        $row = self::fetchRow($id);
        return $row ? self::present($row) : null;
    }

    public static function touchAccepted(int $id): void
    {
        $before = self::fetchRow($id);
        Database::query(
            'UPDATE orders SET accepted_at = COALESCE(accepted_at, NOW()) WHERE id = ?',
            [$id]
        );
        if ($before && empty($before['accepted_at'])) {
            $order = self::find($id);
            if ($order) {
                Mailer::notify(User::find((int) $order['buyer_id']), 'jalons', 'commande-acceptee', [
                    'numero' => (string) $order['num'],
                    'titre' => (string) $order['title'],
                    'lien' => url('/espace/suivi/' . $id),
                ]);
            }
        }
    }

    public static function touchInProgress(int $id): void
    {
        Database::query(
            'UPDATE orders SET status = "in_progress", accepted_at = COALESCE(accepted_at, NOW())
             WHERE id = ? AND status IN ("pending", "in_progress")',
            [$id]
        );
    }

    public static function touchDelivered(int $id): void
    {
        $before = self::fetchRow($id);
        Database::query(
            'UPDATE orders SET status = "delivered", delivered_at = COALESCE(delivered_at, NOW())
             WHERE id = ? AND status IN ("pending", "in_progress", "delivered")',
            [$id]
        );
        if ($before && empty($before['delivered_at'])) {
            $order = self::find($id);
            if ($order) {
                Mailer::notify(User::find((int) $order['buyer_id']), 'jalons', 'commande-livree', [
                    'numero' => (string) ($order['num'] ?? ''),
                    'titre' => (string) ($order['title'] ?? ''),
                    'lien' => url('/espace/suivi/' . $id),
                ]);
            }
        }
    }

    public static function touchPaid(int $id): void
    {
        Database::query(
            'UPDATE orders SET status = "paid" WHERE id = ? AND status IN ("confirmed", "paid")',
            [$id]
        );
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
               AND (
                    NOT EXISTS (SELECT 1 FROM order_milestones om WHERE om.order_id = o.id)
                    OR EXISTS (
                        SELECT 1 FROM order_milestones om
                        WHERE om.order_id = o.id AND om.code = "validate" AND om.status = "current"
                    )
               )
             ORDER BY o.created_at DESC',
            [$buyerId]
        );
        return OrderMilestone::hydrateMany(array_map([self::class, 'present'], $rows));
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
        if (!OrderMilestone::canValidate($order)) {
            throw new \RuntimeException('Confirmez d\'abord le règlement du solde dans le suivi de commande.');
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
            $locked = self::findBare($orderId);
            if (!$locked || (int) $locked['buyer_id'] !== $buyerId) {
                throw new \RuntimeException('Cette commande est introuvable.');
            }
            if (!empty($locked['confirmed_at']) || in_array((string) $locked['status'], ['confirmed', 'paid', 'cancelled'], true)) {
                throw new \RuntimeException('Cette mission a déjà été validée.');
            }
            if (!in_array((string) $locked['status'], self::COMPLETABLE, true)) {
                throw new \RuntimeException('La mission doit être livrée avant d\'être validée.');
            }
            if (!OrderMilestone::canValidate($locked)) {
                throw new \RuntimeException('Confirmez d\'abord le règlement du solde dans le suivi de commande.');
            }

            $quote = Commission::quoteForSeller((int) $locked['seller_id']);
            $amount = (int) ($locked['amount'] ?? 0);
            $seller = User::find((int) $locked['seller_id']);
            $commissionAmount = Commission::amount($amount, $quote['percent'], !empty($seller['vat_exempt']));

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
            $fresh = self::findBare($orderId) ?? $locked;
            $invoice = Invoice::issueForOrder($fresh);
            OrderMilestone::closeAfterValidation($fresh, $invoice, $buyerId);
            $fresh = self::findBare($orderId) ?? $fresh;

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
            Mailer::notify(User::find((int) $order['seller_id']), 'jalons', 'facture-commission', [
                'numero' => (string) ($invoice['number'] ?? ''),
                'montant' => (string) ($invoice['amount_label'] ?? ''),
                'echeance' => (string) ($invoice['due_label'] ?? ''),
                'lien' => url('/espace/facturation'),
            ]);
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
            if ($current === 'dispute') {
                self::notifyParties($id, 'Commande annulée après médiation', 'La commande ' . $order['num'] . ' a été annulée par l\'équipe.');
            }
            return;
        }

        if ($status === 'in_progress' && $current === 'dispute') {
            $restore = 'pending';
            if (!empty($order['accepted_at']) || !empty($order['delivered_at'])) {
                $restore = !empty($order['delivered_at']) ? 'delivered' : 'in_progress';
            }
            if (!empty($order['confirmed_at'])) {
                $restore = 'confirmed';
            }
            Database::query('UPDATE orders SET status = ? WHERE id = ?', [$restore, $id]);
            self::notifyParties($id, 'Litige clôturé — suivi repris', 'L\'équipe a repris le suivi de ' . $order['num'] . '. Les jalons peuvent reprendre.');
            return;
        }

        throw new \RuntimeException('Ce statut doit passer par le suivi à jalons, pas par un changement manuel.');
    }

    public static function setDisputeNote(int $id, string $note): void
    {
        Database::query('UPDATE orders SET dispute_admin_note = ? WHERE id = ?', [trim($note), $id]);
    }

    private static function notifyParties(int $orderId, string $title, string $body): void
    {
        $order = self::findBare($orderId) ?? self::find($orderId);
        if (!$order) {
            return;
        }
        foreach ([(int) $order['buyer_id'], (int) $order['seller_id']] as $uid) {
            if ($uid < 1) {
                continue;
            }
            Notification::create($uid, $title, $body, '/espace/suivi/' . $orderId, 'order_mediation', 'order', $orderId);
        }
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
        return OrderMilestone::hydrateMany(array_map([self::class, 'present'], $rows));
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

        return OrderMilestone::hydrateMany(array_map([self::class, 'present'], $rows));
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

        return OrderMilestone::hydrateMany(array_map([self::class, 'present'], $rows));
    }

    public static function requireActor(int $id, int $userId, ?string $role = null): array
    {
        return self::requireParty($id, $userId, $role);
    }

    /** @return array<string, mixed>|null */
    private static function fetchRow(int $id): ?array
    {
        return Database::fetch(
            'SELECT o.*,
                    s.title AS service_title,
                    s.excerpt AS service_excerpt,
                    s.delay AS service_delay,
                    (SELECT sp.delay FROM service_packages sp
                     WHERE o.service_id IS NOT NULL
                       AND sp.service_id = o.service_id
                       AND o.package_name IS NOT NULL
                       AND sp.name = o.package_name
                     LIMIT 1) AS package_delay,
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
    }

    /** @param array<string, mixed> $row */
    private static function present(array $row): array
    {
        $status = (string) ($row['status'] ?? 'pending');
        $title = (string) ($row['service_title'] ?: $row['mission_title'] ?: $row['package_name'] ?: 'Commande');
        $buyer = trim(($row['buyer_first'] ?? '') . ' ' . ($row['buyer_last'] ?? ''));
        $seller = trim(($row['seller_first'] ?? '') . ' ' . ($row['seller_last'] ?? ''));
        $row['title'] = $title;
        $row['by'] = $buyer !== '' && $seller !== '' ? $buyer . ' → ' . $seller : ($seller ?: $buyer);
        $row['parties'] = $row['by'];
        $row['num'] = (string) ($row['number'] ?? '');
        $row['amount_label'] = format_euros((int) ($row['amount'] ?? 0));
        $row['deposit_amount'] = (int) ($row['deposit_amount'] ?? 0);
        $row['deposit_label'] = format_euros($row['deposit_amount']);
        $row['startup_enabled'] = !empty($row['startup_enabled']);
        $row['startup_kind'] = $row['startup_enabled']
            ? ((string) ($row['startup_kind'] ?? '') === 'percent' ? 'percent' : 'amount')
            : '';
        $row['startup_value'] = $row['startup_enabled'] ? (int) ($row['startup_value'] ?? 0) : 0;
        $row['startup_label'] = Service::startupLabel($row);
        $row['deposit_title'] = $row['startup_enabled'] ? 'Accompagnement de démarrage' : 'Acompte';
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
        $row['options'] = self::decodeOptions($row['options_json'] ?? null);
        $row['dispute_reason'] = trim((string) ($row['dispute_reason'] ?? ''));
        $row['dispute_admin_note'] = trim((string) ($row['dispute_admin_note'] ?? ''));
        $row['dispute_when'] = !empty($row['dispute_at']) ? time_ago($row['dispute_at']) : '';
        $row['commission_label'] = !empty($row['confirmed_at']) || ($row['commission_percent'] ?? null) !== null
            ? format_euros((int) ($row['commission_amount'] ?? 0))
            : '';
        $row['service_excerpt'] = self::htmlToQuoteNote((string) ($row['service_excerpt'] ?? ''));
        $row['service_delay'] = trim((string) ($row['service_delay'] ?? ''));
        $row['package_delay'] = trim((string) ($row['package_delay'] ?? ''));
        $row['quote_form_delay'] = trim((string) ($row['quote_delay'] ?? ''))
            ?: ($row['package_delay'] !== '' ? $row['package_delay'] : $row['service_delay']);
        $row['quote_form_note'] = trim((string) ($row['quote_note'] ?? ''))
            ?: $row['service_excerpt'];
        return $row;
    }

    private static function htmlToQuoteNote(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<\/(p|div|h3|h4|li)>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<li[^>]*>/i', '• ', $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace("/[ \t]+/u", ' ', $text) ?? $text;
        $text = preg_replace("/ ?\n ?/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * @param list<mixed> $options
     */
    private static function encodeOptions(array $options): ?string
    {
        $clean = [];
        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }
            $name = trim((string) ($option['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $clean[] = [
                'name' => $name,
                'price' => (int) ($option['price'] ?? 0),
            ];
        }
        if ($clean === []) {
            return null;
        }
        return json_encode($clean, JSON_UNESCAPED_UNICODE) ?: null;
    }

    /** @return list<array{name: string, price: int, price_label: string}> */
    private static function decodeOptions(mixed $json): array
    {
        if (!is_string($json) || trim($json) === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $option) {
            if (!is_array($option)) {
                continue;
            }
            $name = trim((string) ($option['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $price = (int) ($option['price'] ?? 0);
            $out[] = [
                'name' => $name,
                'price' => $price,
                'price_label' => format_euros($price),
            ];
        }
        return $out;
    }

    private static function nextNumber(): string
    {
        $year = date('Y');
        $row = Database::fetch(
            'SELECT number FROM orders WHERE number LIKE ? ORDER BY id DESC LIMIT 1',
            ['ADL-' . $year . '-%']
        );
        $next = 1;
        if ($row && preg_match('/ADL-\d{4}-(\d+)/', (string) $row['number'], $m)) {
            $next = (int) $m[1] + 1;
        }
        return sprintf('ADL-%s-%04d', $year, $next);
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
