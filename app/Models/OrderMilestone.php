<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Core\Mailer;
use RuntimeException;

final class OrderMilestone
{
    public const ACTOR_SELLER = 'seller';
    public const ACTOR_BUYER = 'buyer';
    public const ACTOR_PLATFORM = 'platform';

    public const STATUS_PENDING = 'pending';
    public const STATUS_CURRENT = 'current';
    public const STATUS_DONE = 'done';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_DECLARED = 'declared';

    public const SEQUENCE = [
        'quote',
        'quote_accept',
        'deposit_invoice',
        'deposit_paid',
        'deposit_ack',
        'deliver',
        'final_invoice',
        'final_paid',
        'validate',
        'commission',
        'commission_paid',
    ];

    public const DEPOSIT_CODES = ['deposit_invoice', 'deposit_paid', 'deposit_ack'];
    public const FINAL_PAY_CODES = ['final_invoice', 'final_paid'];

    /** @var array<string, array<string, string>> */
    public const DEFINITIONS = [
        'quote' => [
            'actor' => self::ACTOR_SELLER,
            'title' => 'Envoyer le devis',
            'done' => 'Devis envoyé',
            'seller_cta' => 'Envoyer le devis',
            'buyer_wait' => 'En attente du devis',
            'lead_seller' => 'Cadrez le prix, le délai et l’acompte. Le client accepte ensuite ce devis : le règlement se fait entre vous, hors de la plateforme.',
            'lead_buyer' => 'Le prestataire prépare le devis. Vous pourrez l’accepter ici, sans quitter le suivi.',
            'form' => 'quote',
        ],
        'quote_accept' => [
            'actor' => self::ACTOR_BUYER,
            'title' => 'Accepter le devis',
            'done' => 'Devis accepté',
            'buyer_cta' => 'Accepter le devis',
            'seller_wait' => 'En attente de l’acceptation du devis',
            'lead_buyer' => 'Vérifiez le montant et l’acompte. En acceptant, vous vous engagez à régler le prestataire directement. Sinon, refusez ce devis pour en recevoir un autre, ou annulez la commande.',
            'lead_seller' => 'Le porteur de projet examine votre devis.',
            'form' => 'quote_accept',
        ],
        'deposit_invoice' => [
            'actor' => self::ACTOR_SELLER,
            'title' => 'Envoyer la facture d’acompte',
            'done' => 'Facture d’acompte envoyée',
            'seller_cta' => 'Envoyer la facture d’acompte',
            'buyer_wait' => 'En attente de la facture d’acompte',
            'lead_seller' => 'Joignez votre facture d’acompte. Le client la règle hors plateforme, puis le confirme ici.',
            'lead_buyer' => 'Le prestataire prépare la facture d’acompte.',
            'form' => 'invoice',
        ],
        'deposit_paid' => [
            'actor' => self::ACTOR_BUYER,
            'title' => 'Régler l’acompte',
            'done' => 'Acompte déclaré réglé',
            'buyer_cta' => 'J’ai réglé l’acompte',
            'seller_wait' => 'En attente du règlement de l’acompte',
            'lead_buyer' => 'Réglez le prestataire (virement, chèque…), puis confirmez ici. Aucun paiement n’est encaissé par la plateforme.',
            'lead_seller' => 'Le client règle l’acompte hors plateforme, puis le déclare ici.',
            'form' => 'confirm_pay',
        ],
        'deposit_ack' => [
            'actor' => self::ACTOR_SELLER,
            'title' => 'Confirmer la réception de l’acompte',
            'done' => 'Acompte reçu',
            'seller_cta' => 'Acompte bien reçu, je démarre',
            'buyer_wait' => 'En attente de la confirmation de réception',
            'lead_seller' => 'Confirmez que l’acompte est arrivé. La mission peut alors démarrer.',
            'lead_buyer' => 'Le prestataire confirme la réception de l’acompte avant de commencer.',
            'form' => 'confirm',
        ],
        'deliver' => [
            'actor' => self::ACTOR_SELLER,
            'title' => 'Livrer la prestation',
            'done' => 'Prestation livrée',
            'seller_cta' => 'Marquer comme livrée',
            'buyer_wait' => 'Travail en cours',
            'lead_seller' => 'Signalez la livraison lorsque le travail est prêt. Vous pourrez ensuite envoyer la facture de solde.',
            'lead_buyer' => 'Le prestataire réalise la mission. La livraison apparaîtra ici.',
            'form' => 'deliver',
        ],
        'final_invoice' => [
            'actor' => self::ACTOR_SELLER,
            'title' => 'Envoyer la facture de solde',
            'done' => 'Facture de solde envoyée',
            'seller_cta' => 'Envoyer la facture de solde',
            'buyer_wait' => 'En attente de la facture de solde',
            'lead_seller' => 'Envoyez la facture du reste à payer. Le client la règle hors plateforme.',
            'lead_buyer' => 'Le prestataire prépare la facture de solde.',
            'form' => 'invoice',
        ],
        'final_paid' => [
            'actor' => self::ACTOR_BUYER,
            'title' => 'Régler le solde',
            'done' => 'Solde déclaré réglé',
            'buyer_cta' => 'J’ai réglé le solde',
            'seller_wait' => 'En attente du règlement du solde',
            'lead_buyer' => 'Réglez le solde au prestataire, puis confirmez-le ici avant de valider la mission.',
            'lead_seller' => 'Le client règle le solde hors plateforme, puis le déclare ici.',
            'form' => 'confirm_pay',
        ],
        'validate' => [
            'actor' => self::ACTOR_BUYER,
            'title' => 'Valider la fin de mission',
            'done' => 'Mission validée',
            'buyer_cta' => 'Valider et noter',
            'seller_wait' => 'En attente de la validation client',
            'lead_buyer' => 'Confirmez que la prestation est terminée et notez le prestataire. C’est ce jalon qui déclenche notre facture de commission.',
            'lead_seller' => 'Le client valide la mission et laisse un avis. La commission plateforme est alors calculée.',
            'form' => 'validate',
        ],
        'commission' => [
            'actor' => self::ACTOR_PLATFORM,
            'title' => 'Facture de commission',
            'done' => 'Commission calculée',
            'lead_seller' => 'La plateforme calcule sa commission sur le montant de la mission validée.',
            'lead_buyer' => 'La plateforme facture sa commission au prestataire, pas à vous.',
            'form' => 'info',
        ],
        'commission_paid' => [
            'actor' => self::ACTOR_SELLER,
            'title' => 'Régler la commission plateforme',
            'done' => 'Commission réglée',
            'seller_cta' => 'J’ai réglé la commission',
            'buyer_wait' => 'Dernier jalon prestataire',
            'lead_seller' => 'Dernier jalon : réglez la facture de commission, puis déclarez le règlement. La plateforme confirme la réception.',
            'lead_buyer' => 'Le prestataire règle sa commission à la plateforme. Ce jalon ne vous concerne pas.',
            'form' => 'commission',
        ],
    ];

    public static function seed(int $orderId): void
    {
        $existing = Database::fetch(
            'SELECT id FROM order_milestones WHERE order_id = ? LIMIT 1',
            [$orderId]
        );
        if ($existing) {
            return;
        }

        $order = Order::findBare($orderId);
        if (!$order) {
            throw new RuntimeException('Cette commande est introuvable.');
        }

        Database::transaction(static function () use ($orderId, $order): void {
            $again = Database::fetch(
                'SELECT id FROM order_milestones WHERE order_id = ? LIMIT 1',
                [$orderId]
            );
            if ($again) {
                return;
            }

            foreach (self::SEQUENCE as $position => $code) {
                $def = self::DEFINITIONS[$code];
                Database::query(
                    'INSERT INTO order_milestones (order_id, code, position, actor, status)
                     VALUES (?, ?, ?, ?, ?)',
                    [$orderId, $code, $position + 1, $def['actor'], self::STATUS_PENDING]
                );
            }

            self::bootstrapFromOrder($order);
            self::refreshCurrent($orderId);
        });
    }

    /**
     * @param array<string, mixed> $order
     * @return array<string, mixed>
     */
    public static function hydrateOrder(array $order): array
    {
        $id = (int) ($order['id'] ?? 0);
        if ($id < 1) {
            return $order;
        }

        try {
            self::seed($id);
            $milestones = self::forOrder($id);
        } catch (\Throwable) {
            $order['milestones'] = [];
            $order['current_milestone'] = null;
            return $order;
        }

        $current = null;
        foreach ($milestones as $row) {
            if (in_array((string) $row['status'], [self::STATUS_CURRENT, self::STATUS_DECLARED], true)) {
                $current = $row;
                break;
            }
        }

        $order['milestones'] = $milestones;
        $order['current_milestone'] = $current;
        $order['can_cancel_order'] = ($order['status'] ?? '') === 'pending' && !self::isDone($milestones, 'quote_accept');
        $order['can_accept'] = false;
        $order['can_deliver'] = $current && ($current['code'] ?? '') === 'deliver';
        $order['can_validate'] = self::isCurrent($milestones, 'validate');
        $order['can_confirm'] = !empty($order['can_validate']);
        $order['next_jalon_label'] = $current['title'] ?? '';
        $order['deposit_label'] = format_euros((int) ($order['deposit_amount'] ?? 0));
        $order['balance_amount'] = max(0, (int) ($order['amount'] ?? 0) - (int) ($order['deposit_amount'] ?? 0));
        $order['balance_label'] = format_euros((int) $order['balance_amount']);

        return $order;
    }

    /**
     * @param list<array<string, mixed>> $orders
     * @return list<array<string, mixed>>
     */
    public static function hydrateMany(array $orders): array
    {
        foreach ($orders as $i => $order) {
            $orders[$i] = self::hydrateOrder($order);
        }
        return $orders;
    }

    /** @return list<array<string, mixed>> */
    public static function forOrder(int $orderId): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM order_milestones WHERE order_id = ? ORDER BY position ASC, id ASC',
            [$orderId]
        );
        return array_map([self::class, 'present'], $rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function dueActions(int $userId, int $limit = 5): array
    {
        $rows = Database::fetchAll(
            'SELECT om.*, o.number, o.status AS order_status,
                    s.title AS service_title, m.title AS mission_title
             FROM order_milestones om
             JOIN orders o ON o.id = om.order_id
             LEFT JOIN services s ON s.id = o.service_id
             LEFT JOIN missions m ON m.id = o.mission_id
             WHERE om.status = ?
               AND o.status NOT IN ("cancelled", "dispute")
               AND (
                    (om.actor = "seller" AND o.seller_id = ?)
                 OR (om.actor = "buyer" AND o.buyer_id = ?)
               )
             ORDER BY om.id ASC
             LIMIT ' . max(1, $limit),
            [self::STATUS_CURRENT, $userId, $userId]
        );

        $out = [];
        foreach ($rows as $row) {
            $def = self::DEFINITIONS[$row['code'] ?? ''] ?? null;
            if (!$def) {
                continue;
            }
            $actor = (string) ($row['actor'] ?? '');
            $revision = self::isQuoteRevision($row);
            $cta = $revision
                ? 'Renvoyer le devis'
                : ($actor === self::ACTOR_BUYER
                    ? (string) ($def['buyer_cta'] ?? $def['title'])
                    : (string) ($def['seller_cta'] ?? $def['title']));
            $out[] = [
                'code' => $row['code'],
                'title' => $revision ? 'Renvoyer le devis' : $def['title'],
                'cta' => $cta,
                'body' => (string) ($row['number'] ?? '') . ' · ' . (string) ($row['service_title'] ?: $row['mission_title'] ?: 'Commande'),
                'href' => '/espace/suivi/' . (int) $row['order_id'],
                'icon' => $actor === self::ACTOR_BUYER ? 'bag' : 'invoice',
            ];
        }
        return $out;
    }

    public static function countDueActions(int $userId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n
             FROM order_milestones om
             JOIN orders o ON o.id = om.order_id
             WHERE om.status = ?
               AND o.status NOT IN ("cancelled", "dispute")
               AND (
                    (om.actor = "seller" AND o.seller_id = ?)
                 OR (om.actor = "buyer" AND o.buyer_id = ?)
               )',
            [self::STATUS_CURRENT, $userId, $userId]
        );
        return (int) ($row['n'] ?? 0);
    }

    public static function canValidate(array $order): bool
    {
        $id = (int) ($order['id'] ?? 0);
        if ($id < 1) {
            return false;
        }
        try {
            self::seed($id);
        } catch (\Throwable) {
            return in_array((string) ($order['status'] ?? ''), Order::COMPLETABLE, true);
        }
        $row = Database::fetch(
            'SELECT status FROM order_milestones WHERE order_id = ? AND code = "validate"',
            [$id]
        );
        if (!$row) {
            return in_array((string) ($order['status'] ?? ''), Order::COMPLETABLE, true);
        }
        return ($row['status'] ?? '') === self::STATUS_CURRENT;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function complete(int $orderId, int $userId, string $code, array $payload = []): array
    {
        if (!isset(self::DEFINITIONS[$code])) {
            throw new RuntimeException('Ce jalon est inconnu.');
        }
        if (in_array($code, ['validate', 'commission'], true)) {
            throw new RuntimeException('Ce jalon se clôture automatiquement à la validation de la mission.');
        }

        return Database::transaction(static function () use ($orderId, $userId, $code, $payload): array {
            self::seed($orderId);
            $order = Order::find($orderId);
            if (!$order) {
                throw new RuntimeException('Cette commande est introuvable.');
            }
            if (in_array((string) ($order['status'] ?? ''), ['cancelled', 'dispute'], true)) {
                throw new RuntimeException('Cette commande est suspendue. Les jalons reprendront après médiation.');
            }

            $milestone = self::requireRow($orderId, $code);
            if (($milestone['status'] ?? '') !== self::STATUS_CURRENT) {
                throw new RuntimeException('Ce n’est pas le jalon en cours.');
            }

            $def = self::DEFINITIONS[$code];
            $isSeller = (int) $order['seller_id'] === $userId;
            $isBuyer = (int) $order['buyer_id'] === $userId;
            if ($def['actor'] === self::ACTOR_SELLER && !$isSeller) {
                throw new RuntimeException('Seul le prestataire peut effectuer cette action.');
            }
            if ($def['actor'] === self::ACTOR_BUYER && !$isBuyer) {
                throw new RuntimeException('Seul le porteur de projet peut effectuer cette action.');
            }

            $fields = self::payloadFor($code, $order, $payload);
            self::markDone($orderId, $code, $userId, $fields);
            self::afterComplete($order, $code, $fields);
            self::refreshCurrent($orderId);

            $fresh = Order::find($orderId) ?? $order;
            self::notifyOtherParty($fresh, $code, $userId);

            return $fresh;
        });
    }

    public static function refuseQuote(int $orderId, int $buyerId, string $note = ''): array
    {
        $note = trim($note);
        if (mb_strlen($note) > 2000) {
            throw new RuntimeException('Le message est trop long.');
        }

        $fresh = Database::transaction(static function () use ($orderId, $buyerId, $note): array {
            self::seed($orderId);
            $order = Order::requireActor($orderId, $buyerId, 'buyer');
            $milestone = self::requireRow($orderId, 'quote_accept');
            if (($milestone['status'] ?? '') !== self::STATUS_CURRENT) {
                throw new RuntimeException('Le devis ne peut plus être refusé.');
            }
            if (in_array((string) ($order['status'] ?? ''), ['cancelled', 'confirmed', 'paid', 'dispute'], true)) {
                throw new RuntimeException('Cette commande ne peut plus être clôturée ainsi.');
            }

            self::reopenQuote($orderId);

            $body = 'Le porteur de projet a refusé le devis de « ' . $order['title'] . ' ». Vous pouvez en proposer un autre.';
            if ($note !== '') {
                $body .= ' Message : ' . $note;
            }
            Notification::create(
                (int) $order['seller_id'],
                'Devis refusé ' . $order['num'],
                $body,
                '/espace/suivi/' . $orderId,
                'quote_refused',
                'order',
                $orderId
            );

            return Order::find($orderId) ?? $order;
        });

        self::emailSeller($fresh, 'devis-refuse', [
            'message_html' => $note !== ''
                ? '<p>Message du porteur de projet :</p><p>' . nl2br(e($note)) . '</p>'
                : '',
        ]);

        return $fresh;
    }

    public static function cancelByBuyer(int $orderId, int $buyerId): array
    {
        $fresh = Database::transaction(static function () use ($orderId, $buyerId): array {
            self::seed($orderId);
            $order = Order::requireActor($orderId, $buyerId, 'buyer');
            $quoteAccept = self::requireRow($orderId, 'quote_accept');
            if (($quoteAccept['status'] ?? '') === self::STATUS_DONE) {
                throw new RuntimeException('Le devis a déjà été accepté. Convenez d’une annulation avec le prestataire ou ouvrez un litige.');
            }
            if (($order['status'] ?? '') !== 'pending') {
                throw new RuntimeException('Cette commande ne peut plus être annulée ainsi.');
            }

            Order::setStatus($orderId, 'cancelled');
            Database::query(
                'UPDATE order_milestones
                 SET status = CASE WHEN status IN ("done", "skipped") THEN status ELSE "skipped" END
                 WHERE order_id = ? AND status IN ("pending", "current", "declared")',
                [$orderId]
            );

            Notification::create(
                (int) $order['seller_id'],
                'Commande annulée ' . $order['num'],
                'Le porteur de projet a annulé « ' . $order['title'] . ' ». La commande est clôturée.',
                '/espace/suivi/' . $orderId,
                'order_cancelled',
                'order',
                $orderId
            );

            return Order::find($orderId) ?? $order;
        });

        self::emailSeller($fresh, 'commande-annulee');

        return $fresh;
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $invoice
     */
    public static function closeAfterValidation(array $order, array $invoice, int $buyerId): void
    {
        $orderId = (int) ($order['id'] ?? 0);
        if ($orderId < 1) {
            return;
        }
        try {
            self::seed($orderId);
        } catch (\Throwable) {
            return;
        }

        $validate = Database::fetch(
            'SELECT status FROM order_milestones WHERE order_id = ? AND code = "validate"',
            [$orderId]
        );
        if ($validate && !in_array((string) $validate['status'], [self::STATUS_DONE, self::STATUS_SKIPPED], true)) {
            self::markDone($orderId, 'validate', $buyerId, []);
        }

        $commissionAmount = (int) ($invoice['amount'] ?? $order['commission_amount'] ?? 0);
        $waived = !empty($order['first_mission_free'])
            || $commissionAmount <= 0
            || ($invoice['status'] ?? '') === 'waived';

        self::markDone($orderId, 'commission', null, [
            'amount' => $commissionAmount,
            'note' => $waived ? 'Première mission offerte : aucune commission due.' : null,
        ]);

        if ($waived) {
            self::skip($orderId, ['commission_paid']);
            Order::touchPaid($orderId);
        }

        self::refreshCurrent($orderId);
    }

    /**
     * @param array<string, mixed> $invoice
     */
    public static function closeAfterCommissionPaid(array $invoice): void
    {
        $orderId = (int) ($invoice['order_id'] ?? 0);
        if ($orderId < 1) {
            return;
        }
        try {
            self::seed($orderId);
        } catch (\Throwable) {
            return;
        }

        $row = Database::fetch(
            'SELECT status FROM order_milestones WHERE order_id = ? AND code = "commission_paid"',
            [$orderId]
        );
        if ($row && !in_array((string) $row['status'], [self::STATUS_DONE, self::STATUS_SKIPPED], true)) {
            self::markDone($orderId, 'commission_paid', null, [
                'amount' => (int) ($invoice['amount'] ?? 0),
                'note' => 'Règlement confirmé par la plateforme.',
            ]);
        }
        Order::touchPaid($orderId);
        self::refreshCurrent($orderId);
    }

    /**
     * Devis pas encore accepté : à envoyer ou en attente de validation client.
     *
     * @param array<string, mixed> $order
     */
    public static function quoteNeedsManagement(array $order): bool
    {
        if (in_array((string) ($order['status'] ?? ''), ['cancelled', 'dispute'], true)) {
            return false;
        }
        $current = $order['current_milestone'] ?? null;
        if (!is_array($current)) {
            return false;
        }

        return in_array((string) ($current['code'] ?? ''), ['quote', 'quote_accept'], true);
    }

    public static function flashFor(string $code): string
    {
        return match ($code) {
            'quote' => 'Devis envoyé. Le porteur de projet peut maintenant l’accepter.',
            'quote_accept' => 'Devis accepté. Les jalons de règlement commencent.',
            'deposit_invoice' => 'Facture d’acompte envoyée. Le client peut maintenant la régler hors plateforme.',
            'deposit_paid' => 'Règlement de l’acompte déclaré. Le prestataire doit confirmer la réception.',
            'deposit_ack' => 'Acompte reçu. Vous pouvez réaliser et livrer la mission.',
            'deliver' => 'Livraison signalée. Envoyez ensuite la facture de solde.',
            'final_invoice' => 'Facture de solde envoyée. Le client peut maintenant la régler.',
            'final_paid' => 'Règlement du solde déclaré. Vous pouvez valider et noter la mission.',
            'commission_paid' => 'Règlement déclaré. La plateforme confirmera la réception de la commission.',
            default => 'Jalon enregistré.',
        };
    }

    /**
     * @param array<string, mixed> $order
     * @return array<string, mixed>|null
     */
    public static function actionFor(array $order, int $userId): ?array
    {
        $current = $order['current_milestone'] ?? null;
        if (!is_array($current)) {
            return null;
        }
        if (in_array((string) ($order['status'] ?? ''), ['cancelled', 'dispute'], true)) {
            return null;
        }

        $code = (string) ($current['code'] ?? '');
        $def = self::DEFINITIONS[$code] ?? null;
        if (!$def) {
            return null;
        }

        $isSeller = (int) ($order['seller_id'] ?? 0) === $userId;
        $isBuyer = (int) ($order['buyer_id'] ?? 0) === $userId;
        $mine = ($def['actor'] === self::ACTOR_SELLER && $isSeller)
            || ($def['actor'] === self::ACTOR_BUYER && $isBuyer);

        $revision = self::isQuoteRevision($current);
        $lead = $isSeller ? ($def['lead_seller'] ?? '') : ($def['lead_buyer'] ?? '');
        if ($revision) {
            $lead = $isSeller
                ? 'Le porteur de projet a refusé le devis. Ajustez le prix, le délai ou le périmètre, puis renvoyez une proposition.'
                : 'Le prestataire prépare un nouveau devis. Vous pouvez aussi annuler la commande si vous ne souhaitez plus continuer.';
        }
        $declared = ($current['status'] ?? '') === self::STATUS_DECLARED;

        if ($code === 'commission_paid' && $declared && $isSeller) {
            return [
                'code' => $code,
                'title' => 'Commission déclarée réglée',
                'lead' => 'Nous confirmons la réception. Ce jalon se clôturera ensuite automatiquement.',
                'form' => 'waiting',
                'mine' => true,
                'cta' => '',
            ];
        }

        if (!$mine) {
            $wait = $revision
                ? 'En attente d’un nouveau devis'
                : ($isSeller
                    ? (string) ($def['seller_wait'] ?? $def['buyer_wait'] ?? 'En attente de l’autre partie')
                    : (string) ($def['buyer_wait'] ?? $def['seller_wait'] ?? 'En attente de l’autre partie'));
            return [
                'code' => $code,
                'title' => $wait,
                'lead' => $lead,
                'form' => 'waiting',
                'mine' => false,
                'cta' => '',
                'revision' => $revision,
            ];
        }

        $cta = $revision
            ? 'Renvoyer le devis'
            : ($def['actor'] === self::ACTOR_BUYER
                ? (string) ($def['buyer_cta'] ?? $def['title'])
                : (string) ($def['seller_cta'] ?? $def['title']));

        return [
            'code' => $code,
            'title' => $revision ? 'Renvoyer le devis' : $def['title'],
            'lead' => $lead,
            'form' => $def['form'],
            'mine' => true,
            'cta' => $cta,
            'revision' => $revision,
            'amount' => $code === 'deposit_invoice' || $code === 'deposit_paid'
                ? (int) ($order['deposit_amount'] ?? 0)
                : ($code === 'final_invoice' || $code === 'final_paid'
                    ? (int) ($order['balance_amount'] ?? 0)
                    : (int) ($order['amount'] ?? 0)),
            'amount_label' => $code === 'deposit_invoice' || $code === 'deposit_paid'
                ? (string) ($order['deposit_label'] ?? '')
                : ($code === 'final_invoice' || $code === 'final_paid'
                    ? (string) ($order['balance_label'] ?? '')
                    : (string) ($order['amount_label'] ?? '')),
        ];
    }

    /** @param array<string, mixed> $row */
    private static function present(array $row): array
    {
        $code = (string) ($row['code'] ?? '');
        $def = self::DEFINITIONS[$code] ?? [];
        $status = (string) ($row['status'] ?? self::STATUS_PENDING);
        $row['title'] = $status === self::STATUS_DONE
            ? (string) ($def['done'] ?? $def['title'] ?? $code)
            : (string) ($def['title'] ?? $code);
        $row['base_title'] = (string) ($def['title'] ?? $code);
        $row['actor'] = (string) ($row['actor'] ?? $def['actor'] ?? self::ACTOR_SELLER);
        $row['actor_label'] = match ($row['actor']) {
            self::ACTOR_BUYER => 'Client',
            self::ACTOR_PLATFORM => 'Plateforme',
            default => 'Prestataire',
        };
        $row['status_label'] = match ($status) {
            self::STATUS_DONE => 'Fait',
            self::STATUS_CURRENT => 'En cours',
            self::STATUS_SKIPPED => 'Non concerné',
            self::STATUS_DECLARED => 'Déclaré',
            default => 'À venir',
        };
        $row['is_done'] = $status === self::STATUS_DONE;
        $row['is_current'] = in_array($status, [self::STATUS_CURRENT, self::STATUS_DECLARED], true);
        $row['is_skipped'] = $status === self::STATUS_SKIPPED;
        $row['amount_label'] = isset($row['amount']) && $row['amount'] !== null
            ? format_euros((int) $row['amount'])
            : '';
        $row['when'] = !empty($row['completed_at']) ? time_ago((string) $row['completed_at']) : '';
        $row['file_href'] = !empty($row['file_path'])
            ? url('/espace/suivi/' . (int) ($row['order_id'] ?? 0) . '/fichier/' . rawurlencode($code))
            : '';
        return $row;
    }

    /** @param list<array<string, mixed>> $milestones */
    private static function isCurrent(array $milestones, string $code): bool
    {
        foreach ($milestones as $row) {
            if (($row['code'] ?? '') === $code && ($row['status'] ?? '') === self::STATUS_CURRENT) {
                return true;
            }
        }
        return false;
    }

    /** @param list<array<string, mixed>> $milestones */
    private static function isDone(array $milestones, string $code): bool
    {
        foreach ($milestones as $row) {
            if (($row['code'] ?? '') === $code && !empty($row['is_done'])) {
                return true;
            }
        }
        return false;
    }

    /** @param array<string, mixed> $milestone */
    private static function isQuoteRevision(array $milestone): bool
    {
        return ($milestone['code'] ?? '') === 'quote' && !empty($milestone['completed_at']);
    }

    private static function reopenQuote(int $orderId): void
    {
        Database::query(
            'UPDATE order_milestones
             SET status = ?, completed_by = NULL
             WHERE order_id = ? AND code = ?',
            [self::STATUS_PENDING, $orderId, 'quote']
        );
        Database::query(
            'UPDATE order_milestones
             SET status = ?, completed_at = NULL, completed_by = NULL
             WHERE order_id = ? AND code = ?',
            [self::STATUS_PENDING, $orderId, 'quote_accept']
        );
        foreach (array_merge(self::DEPOSIT_CODES, self::FINAL_PAY_CODES) as $code) {
            Database::query(
                'UPDATE order_milestones
                 SET status = ?
                 WHERE order_id = ? AND code = ? AND status = ?',
                [self::STATUS_PENDING, $orderId, $code, self::STATUS_SKIPPED]
            );
        }
        self::refreshCurrent($orderId);
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, string> $vars
     */
    private static function emailSeller(array $order, string $slug, array $vars = []): void
    {
        Mailer::notify(User::find((int) ($order['seller_id'] ?? 0)), 'transactional', $slug, array_merge([
            'numero' => (string) ($order['num'] ?? ''),
            'titre' => (string) ($order['title'] ?? ''),
            'lien' => url('/espace/suivi/' . (int) ($order['id'] ?? 0)),
        ], $vars));
    }

    /**
     * @return array<string, mixed>
     */
    private static function requireRow(int $orderId, string $code): array
    {
        $row = Database::fetch(
            'SELECT * FROM order_milestones WHERE order_id = ? AND code = ?',
            [$orderId, $code]
        );
        if (!$row) {
            throw new RuntimeException('Ce jalon est introuvable.');
        }
        return $row;
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $payload
     * @return array{amount: ?int, delay: ?string, note: ?string, file_name: ?string, file_path: ?string}
     */
    private static function payloadFor(string $code, array $order, array $payload): array
    {
        $amount = isset($payload['amount']) ? (int) $payload['amount'] : null;
        $deposit = isset($payload['deposit_amount']) ? (int) $payload['deposit_amount'] : null;
        $delay = trim((string) ($payload['delay'] ?? '')) ?: null;
        $note = trim((string) ($payload['note'] ?? '')) ?: null;
        $fileName = trim((string) ($payload['file_name'] ?? '')) ?: null;
        $filePath = trim((string) ($payload['file_path'] ?? '')) ?: null;

        if ($code === 'quote') {
            if ($amount === null || $amount < 1) {
                throw new RuntimeException('Indiquez le montant du devis.');
            }
            $deposit = max(0, $deposit ?? 0);
            if ($deposit > $amount) {
                throw new RuntimeException('L’acompte ne peut pas dépasser le montant du devis.');
            }
            if ($fileName === null && $filePath === null) {
                $existing = self::requireRow((int) ($order['id'] ?? 0), 'quote');
                $fileName = trim((string) ($existing['file_name'] ?? '')) ?: null;
                $filePath = trim((string) ($existing['file_path'] ?? '')) ?: null;
            }
            return [
                'amount' => $amount,
                'delay' => $delay,
                'note' => $note,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'deposit_amount' => $deposit,
            ];
        }

        if ($code === 'deposit_invoice') {
            return [
                'amount' => (int) ($order['deposit_amount'] ?? 0),
                'delay' => null,
                'note' => $note,
                'file_name' => $fileName,
                'file_path' => $filePath,
            ];
        }

        if ($code === 'final_invoice') {
            return [
                'amount' => max(0, (int) ($order['amount'] ?? 0) - (int) ($order['deposit_amount'] ?? 0)),
                'delay' => null,
                'note' => $note,
                'file_name' => $fileName,
                'file_path' => $filePath,
            ];
        }

        if ($code === 'commission_paid') {
            return [
                'amount' => (int) ($order['commission_amount'] ?? 0),
                'delay' => null,
                'note' => $note,
                'file_name' => $fileName,
                'file_path' => $filePath,
            ];
        }

        return [
            'amount' => $amount,
            'delay' => $delay,
            'note' => $note,
            'file_name' => $fileName,
            'file_path' => $filePath,
        ];
    }

    /**
     * @param array<string, mixed> $fields
     */
    private static function markDone(int $orderId, string $code, ?int $userId, array $fields): void
    {
        Database::query(
            'UPDATE order_milestones
             SET status = ?, amount = ?, delay = ?, note = ?, file_name = ?, file_path = ?,
                 completed_at = NOW(), completed_by = ?
             WHERE order_id = ? AND code = ?',
            [
                self::STATUS_DONE,
                $fields['amount'] ?? null,
                $fields['delay'] ?? null,
                $fields['note'] ?? null,
                $fields['file_name'] ?? null,
                $fields['file_path'] ?? null,
                $userId,
                $orderId,
                $code,
            ]
        );
    }

    /** @param list<string> $codes */
    private static function skip(int $orderId, array $codes): void
    {
        foreach ($codes as $code) {
            Database::query(
                'UPDATE order_milestones
                 SET status = ?
                 WHERE order_id = ? AND code = ? AND status IN ("pending", "current")',
                [self::STATUS_SKIPPED, $orderId, $code]
            );
        }
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $fields
     */
    private static function afterComplete(array $order, string $code, array $fields): void
    {
        $id = (int) $order['id'];

        if ($code === 'quote') {
            $deposit = (int) ($fields['deposit_amount'] ?? 0);
            $amount = (int) ($fields['amount'] ?? 0);
            Database::query(
                'UPDATE orders SET amount = ?, deposit_amount = ?, quote_delay = ?, quote_note = ? WHERE id = ?',
                [$amount, $deposit, $fields['delay'] ?? null, $fields['note'] ?? null, $id]
            );
            if ($deposit < 1) {
                self::skip($id, self::DEPOSIT_CODES);
            }
            if ($deposit >= $amount && $amount > 0) {
                self::skip($id, self::FINAL_PAY_CODES);
            }
            return;
        }

        if ($code === 'quote_accept') {
            Order::touchAccepted($id);
            if ((int) ($order['deposit_amount'] ?? 0) < 1) {
                Order::touchInProgress($id);
            }
            return;
        }

        if ($code === 'deposit_ack') {
            Order::touchInProgress($id);
            return;
        }

        if ($code === 'deliver') {
            Order::touchDelivered($id);
            return;
        }

        if ($code === 'commission_paid') {
            Database::query(
                'UPDATE order_milestones SET status = ? WHERE order_id = ? AND code = ?',
                [self::STATUS_DECLARED, $id, $code]
            );
            $invoice = Invoice::forOrder($id);
            Notification::create(
                (int) $order['seller_id'],
                'Commission déclarée réglée',
                'Votre déclaration a été enregistrée. La plateforme confirmera la réception de la facture '
                . ($invoice['number'] ?? '') . '.',
                '/espace/facturation',
                'commission_declared',
                'order',
                $id
            );
        }
    }

    private static function refreshCurrent(int $orderId): void
    {
        Database::query(
            'UPDATE order_milestones SET status = ? WHERE order_id = ? AND status = ?',
            [self::STATUS_PENDING, $orderId, self::STATUS_CURRENT]
        );

        $next = Database::fetch(
            'SELECT id FROM order_milestones
             WHERE order_id = ? AND status = ?
             ORDER BY position ASC, id ASC
             LIMIT 1',
            [$orderId, self::STATUS_PENDING]
        );
        if ($next) {
            Database::query(
                'UPDATE order_milestones SET status = ? WHERE id = ?',
                [self::STATUS_CURRENT, (int) $next['id']]
            );
        }
    }

    /** @param array<string, mixed> $order */
    private static function bootstrapFromOrder(array $order): void
    {
        $id = (int) $order['id'];
        $status = (string) ($order['status'] ?? 'pending');
        $done = [];
        $skip = [];

        if (in_array($status, ['in_progress', 'delivered', 'confirmed', 'paid', 'dispute'], true)) {
            $done = ['quote', 'quote_accept'];
            $skip = self::DEPOSIT_CODES;
        }
        if (in_array($status, ['delivered', 'confirmed', 'paid'], true)) {
            $done[] = 'deliver';
            $skip = array_merge($skip, self::FINAL_PAY_CODES);
        }
        if (in_array($status, ['confirmed', 'paid'], true)) {
            $done = array_merge($done, ['validate', 'commission']);
        }
        if ($status === 'paid' || (!empty($order['first_mission_free']) && $status === 'confirmed')) {
            $invoice = Invoice::forOrder($id);
            if ($invoice && in_array((string) ($invoice['status'] ?? ''), ['paid', 'waived'], true)) {
                $done[] = 'commission_paid';
            } elseif ($invoice && ($invoice['status'] ?? '') === 'waived') {
                $skip[] = 'commission_paid';
            }
        }
        if ($status === 'cancelled') {
            $skip = self::SEQUENCE;
        }

        foreach (array_unique($done) as $code) {
            Database::query(
                'UPDATE order_milestones
                 SET status = ?, completed_at = COALESCE(completed_at, NOW())
                 WHERE order_id = ? AND code = ?',
                [self::STATUS_DONE, $id, $code]
            );
        }
        if ($skip !== []) {
            self::skip($id, array_values(array_unique($skip)));
        }
    }

    /**
     * @param array<string, mixed> $order
     */
    private static function notifyOtherParty(array $order, string $code, int $actorId): void
    {
        if ($code === 'commission_paid') {
            return;
        }

        $otherId = (int) $order['buyer_id'] === $actorId
            ? (int) $order['seller_id']
            : (int) $order['buyer_id'];
        $current = Database::fetch(
            'SELECT code FROM order_milestones WHERE order_id = ? AND status = ? LIMIT 1',
            [(int) $order['id'], self::STATUS_CURRENT]
        );
        $nextCode = (string) ($current['code'] ?? '');
        $nextTitle = self::DEFINITIONS[$nextCode]['title'] ?? 'la suite du suivi';

        $titles = [
            'quote' => 'Devis reçu ' . $order['num'],
            'quote_accept' => 'Devis accepté ' . $order['num'],
            'deposit_invoice' => 'Facture d’acompte ' . $order['num'],
            'deposit_paid' => 'Acompte déclaré ' . $order['num'],
            'deposit_ack' => 'Mission démarrée ' . $order['num'],
            'deliver' => 'Livraison à régler ' . $order['num'],
            'final_invoice' => 'Facture de solde ' . $order['num'],
            'final_paid' => 'Solde déclaré ' . $order['num'],
        ];

        Notification::create(
            $otherId,
            $titles[$code] ?? ('Jalon ' . $order['num']),
            '« ' . $order['title'] . ' » : prochaine étape — ' . $nextTitle . '.',
            '/espace/suivi/' . (int) $order['id'],
            'order_milestone',
            'order',
            (int) $order['id']
        );
    }
}
