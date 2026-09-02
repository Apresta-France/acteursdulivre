<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Core\Mailer;
use RuntimeException;

final class ReviewRequest
{
    public const KIND_PLATFORM = 'platform';
    public const KIND_EXTERNAL = 'external';
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const RESEND_HOURS = 48;
    public const EXTERNAL_DAYS = 30;
    public const MAX_PENDING_EXTERNAL = 15;
    public const MAX_EXTERNAL_PER_DAY = 8;

    /** @return list<array<string, mixed>> */
    public static function forSeller(int $sellerId): array
    {
        try {
            $rows = Database::fetchAll(
                'SELECT * FROM review_requests WHERE seller_id = ? ORDER BY created_at DESC',
                [$sellerId]
            );
        } catch (\Throwable) {
            return [];
        }

        return array_map([self::class, 'present'], $rows);
    }

    /** @return list<array<string, mixed>> */
    public static function pendingExternalForSeller(int $sellerId): array
    {
        try {
            $rows = Database::fetchAll(
                'SELECT * FROM review_requests
                 WHERE seller_id = ? AND kind = ? AND status = ?
                 ORDER BY last_sent_at DESC',
                [$sellerId, self::KIND_EXTERNAL, self::STATUS_PENDING]
            );
        } catch (\Throwable) {
            return [];
        }

        return array_map([self::class, 'present'], $rows);
    }

    public static function findByToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        try {
            $row = Database::fetch('SELECT * FROM review_requests WHERE token = ? LIMIT 1', [$token]);
        } catch (\Throwable) {
            return null;
        }

        return $row ? self::present($row) : null;
    }

    public static function findOwned(int $id, int $sellerId): ?array
    {
        $row = Database::fetch(
            'SELECT * FROM review_requests WHERE id = ? AND seller_id = ? LIMIT 1',
            [$id, $sellerId]
        );
        return $row ? self::present($row) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function requestPlatform(int $sellerId, int $orderId): array
    {
        $order = Order::findForUser($orderId, $sellerId);
        if (!$order || (int) ($order['seller_id'] ?? 0) !== $sellerId) {
            throw new RuntimeException('Cette mission est introuvable.');
        }
        if (!empty($order['confirmed_at']) || in_array((string) ($order['status'] ?? ''), ['confirmed', 'paid', 'cancelled'], true)) {
            throw new RuntimeException('Cette mission est déjà close.');
        }
        if (Review::forOrderAuthor($orderId, (int) $order['buyer_id'])) {
            throw new RuntimeException('Un avis a déjà été déposé pour cette mission.');
        }
        if (!in_array((string) ($order['status'] ?? ''), ['delivered', 'in_progress'], true)) {
            throw new RuntimeException('Attendez la livraison avant de demander un avis.');
        }

        $buyer = User::find((int) $order['buyer_id']);
        if (!$buyer) {
            throw new RuntimeException('Le client est introuvable.');
        }
        $email = strtolower(trim((string) ($buyer['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Ce client n’a pas d’adresse e-mail utilisable.');
        }

        $existing = Database::fetch(
            'SELECT * FROM review_requests
             WHERE seller_id = ? AND order_id = ? AND kind = ?
             ORDER BY id DESC LIMIT 1',
            [$sellerId, $orderId, self::KIND_PLATFORM]
        );
        if ($existing) {
            return self::resend((int) $existing['id'], $sellerId);
        }

        $sellerName = self::sellerLabel($sellerId);
        $title = (string) ($order['title'] ?? 'la mission');
        $number = (string) ($order['num'] ?? $order['number'] ?? '');

        Database::query(
            'INSERT INTO review_requests
                (seller_id, kind, order_id, recipient_email, recipient_name, context, status, last_sent_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                $sellerId,
                self::KIND_PLATFORM,
                $orderId,
                $email,
                User::displayName($buyer),
                $title,
                self::STATUS_PENDING,
            ]
        );
        $id = (int) Database::lastId();

        self::notifyPlatform($buyer, $sellerName, $title, $number, $orderId);
        return self::findOwned($id, $sellerId) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function requestExternal(int $sellerId, string $name, string $email, string $context = ''): array
    {
        $name = trim($name);
        $email = strtolower(trim($email));
        $context = trim($context);
        if ($name === '') {
            throw new RuntimeException('Indiquez le nom de la personne à inviter.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Indiquez une adresse e-mail valide.');
        }
        if (mb_strlen($name) > 120 || mb_strlen($context) > 160) {
            throw new RuntimeException('Le nom ou le contexte est trop long.');
        }

        $seller = User::find($sellerId);
        $ownEmail = strtolower(trim((string) ($seller['email'] ?? '')));
        if ($ownEmail !== '' && hash_equals($ownEmail, $email)) {
            throw new RuntimeException('Vous ne pouvez pas vous inviter vous-même.');
        }

        $pending = self::pendingExternalForSeller($sellerId);
        if (count($pending) >= self::MAX_PENDING_EXTERNAL) {
            throw new RuntimeException('Trop d’invitations en attente. Annulez-en une ou attendez une réponse.');
        }

        $today = Database::fetch(
            'SELECT COUNT(*) AS n FROM review_requests
             WHERE seller_id = ? AND kind = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)',
            [$sellerId, self::KIND_EXTERNAL]
        );
        if ((int) ($today['n'] ?? 0) >= self::MAX_EXTERNAL_PER_DAY) {
            throw new RuntimeException('Vous avez déjà envoyé plusieurs invitations aujourd’hui. Réessayez demain.');
        }

        $open = Database::fetch(
            'SELECT * FROM review_requests
             WHERE seller_id = ? AND kind = ? AND recipient_email = ? AND status = ?
             ORDER BY id DESC LIMIT 1',
            [$sellerId, self::KIND_EXTERNAL, $email, self::STATUS_PENDING]
        );
        if ($open) {
            return self::resend((int) $open['id'], $sellerId);
        }

        $token = bin2hex(random_bytes(24));
        Database::query(
            'INSERT INTO review_requests
                (seller_id, kind, recipient_email, recipient_name, context, token, status, last_sent_at, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY))',
            [
                $sellerId,
                self::KIND_EXTERNAL,
                $email,
                $name,
                $context,
                $token,
                self::STATUS_PENDING,
                self::EXTERNAL_DAYS,
            ]
        );
        $id = (int) Database::lastId();
        $row = self::findOwned($id, $sellerId);
        if (!$row) {
            throw new RuntimeException('L’invitation n’a pas pu être créée.');
        }
        self::mailExternal($row);
        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    public static function resend(int $id, int $sellerId): array
    {
        $row = self::findOwned($id, $sellerId);
        if (!$row || ($row['status'] ?? '') !== self::STATUS_PENDING) {
            throw new RuntimeException('Cette invitation n’est plus active.');
        }
        if (empty($row['can_resend'])) {
            throw new RuntimeException('Attendez encore un peu avant de relancer.');
        }

        if (($row['kind'] ?? '') === self::KIND_EXTERNAL) {
            $token = (string) ($row['token'] ?? '');
            if ($token === '') {
                $token = bin2hex(random_bytes(24));
            }
            Database::query(
                'UPDATE review_requests
                 SET last_sent_at = NOW(),
                     token = ?,
                     expires_at = DATE_ADD(NOW(), INTERVAL ? DAY)
                 WHERE id = ?',
                [$token, self::EXTERNAL_DAYS, $id]
            );
            $fresh = self::findOwned($id, $sellerId);
            if ($fresh) {
                self::mailExternal($fresh);
            }
            return $fresh ?? $row;
        }

        $orderId = (int) ($row['order_id'] ?? 0);
        $order = $orderId > 0 ? Order::findForUser($orderId, $sellerId) : null;
        if (!$order) {
            throw new RuntimeException('Cette mission est introuvable.');
        }
        $buyer = User::find((int) $order['buyer_id']);
        if (!$buyer) {
            throw new RuntimeException('Le client est introuvable.');
        }
        Database::query('UPDATE review_requests SET last_sent_at = NOW() WHERE id = ?', [$id]);
        self::notifyPlatform(
            $buyer,
            self::sellerLabel($sellerId),
            (string) ($order['title'] ?? $row['context'] ?? 'la mission'),
            (string) ($order['num'] ?? $order['number'] ?? ''),
            $orderId
        );
        return self::findOwned($id, $sellerId) ?? $row;
    }

    public static function cancel(int $id, int $sellerId): void
    {
        $row = self::findOwned($id, $sellerId);
        if (!$row) {
            throw new RuntimeException('Cette invitation est introuvable.');
        }
        if (($row['status'] ?? '') !== self::STATUS_PENDING) {
            throw new RuntimeException('Cette invitation n’est plus en attente.');
        }
        Database::query(
            'UPDATE review_requests SET status = ? WHERE id = ?',
            [self::STATUS_CANCELLED, $id]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function completeExternal(string $token, string $name, string $role, string $body): array
    {
        $request = self::findByToken($token);
        if (!$request || ($request['kind'] ?? '') !== self::KIND_EXTERNAL) {
            throw new RuntimeException('Ce lien n’est plus valable.');
        }
        if (($request['status'] ?? '') !== self::STATUS_PENDING) {
            throw new RuntimeException('Cette recommandation a déjà été déposée.');
        }
        if (!empty($request['expired'])) {
            throw new RuntimeException('Ce lien a expiré. Demandez une nouvelle invitation.');
        }

        $name = trim($name);
        $role = trim($role);
        $body = trim($body);
        if ($name === '') {
            throw new RuntimeException('Indiquez votre nom.');
        }
        if (mb_strlen($body) < 40) {
            throw new RuntimeException('Écrivez au moins quelques phrases : 40 caractères minimum.');
        }
        if (mb_strlen($body) > 2000) {
            throw new RuntimeException('La recommandation est trop longue (2 000 caractères maximum).');
        }
        if (mb_strlen($name) > 120 || mb_strlen($role) > 120) {
            throw new RuntimeException('Le nom ou la fonction est trop long.');
        }

        $requestId = (int) $request['id'];
        $existing = Database::fetch('SELECT id FROM recommendations WHERE request_id = ?', [$requestId]);
        if ($existing) {
            throw new RuntimeException('Cette recommandation a déjà été déposée.');
        }

        Database::query(
            'INSERT INTO recommendations (request_id, target_id, author_name, author_email, author_role, context, body)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $requestId,
                (int) $request['seller_id'],
                $name,
                (string) $request['recipient_email'],
                $role,
                (string) ($request['context'] ?? ''),
                $body,
            ]
        );
        $recoId = (int) Database::lastId();
        Database::query(
            'UPDATE review_requests SET status = ?, completed_at = NOW() WHERE id = ?',
            [self::STATUS_COMPLETED, $requestId]
        );

        $row = Database::fetch('SELECT * FROM recommendations WHERE id = ?', [$recoId]) ?? [];
        return Recommendation::present($row);
    }

    /** @param array<string, mixed> $row */
    public static function present(array $row): array
    {
        $row['kind'] = (string) ($row['kind'] ?? '');
        $row['status'] = (string) ($row['status'] ?? '');
        $row['recipient_name'] = trim((string) ($row['recipient_name'] ?? ''));
        $row['recipient_email'] = trim((string) ($row['recipient_email'] ?? ''));
        $row['context'] = trim((string) ($row['context'] ?? ''));
        $row['expired'] = !empty($row['expires_at']) && strtotime((string) $row['expires_at']) < time();
        $last = strtotime((string) ($row['last_sent_at'] ?? '')) ?: 0;
        $row['can_resend'] = $row['status'] === self::STATUS_PENDING
            && !$row['expired']
            && $last <= time() - self::RESEND_HOURS * 3600;
        $row['sent_when'] = time_ago($row['last_sent_at'] ?? null);
        $row['expires_label'] = !empty($row['expires_at'])
            ? date('d/m/Y', strtotime((string) $row['expires_at']) ?: time())
            : '';
        $row['status_label'] = match ($row['status']) {
            self::STATUS_COMPLETED => 'Reçue',
            self::STATUS_CANCELLED => 'Annulée',
            default => $row['expired'] ? 'Expirée' : 'En attente',
        };
        return $row;
    }

    /** @param array<string, mixed> $buyer */
    private static function notifyPlatform(array $buyer, string $sellerName, string $title, string $number, int $orderId): void
    {
        $buyerId = (int) ($buyer['id'] ?? 0);
        if ($buyerId > 0) {
            Notification::create(
                $buyerId,
                'Avis demandé',
                $sellerName . ' vous demande de valider et noter « ' . $title . ' ».',
                '/espace/avis',
                'review_request',
                'order',
                $orderId > 0 ? $orderId : null
            );
        }
        Mailer::notify($buyer, 'jalons', 'demande-avis', [
            'prestataire' => $sellerName,
            'titre' => $title,
            'numero' => $number,
            'lien' => url('/espace/avis'),
        ]);
    }

    /** @param array<string, mixed> $request */
    private static function mailExternal(array $request): void
    {
        $email = (string) ($request['recipient_email'] ?? '');
        if ($email === '') {
            return;
        }
        $context = trim((string) ($request['context'] ?? ''));
        $vars = [
            'prenom' => (string) ($request['recipient_name'] ?? ''),
            'prestataire' => self::sellerLabel((int) $request['seller_id']),
            'contexte' => $context !== '' ? ' — à propos de « ' . $context . ' »' : '',
            'lien' => url('/recommandation/' . rawurlencode((string) $request['token'])),
            'expiration' => (string) ($request['expires_label'] ?? ''),
        ];
        try {
            Mailer::sendTemplate('demande-recommandation', $email, $vars);
        } catch (\Throwable) {
            $id = (int) ($request['id'] ?? 0);
            if ($id > 0) {
                Database::query(
                    'UPDATE review_requests SET last_sent_at = DATE_SUB(NOW(), INTERVAL ? HOUR) WHERE id = ?',
                    [self::RESEND_HOURS, $id]
                );
            }
            throw new RuntimeException('L’invitation est enregistrée, mais l’e-mail n’a pas pu partir. Relancez depuis l’onglet Avis.');
        }
    }

    private static function sellerLabel(int $sellerId): string
    {
        try {
            $profile = Profile::findByUser($sellerId);
            if ($profile) {
                $name = Profile::displayName($profile);
                if ($name !== '') {
                    return $name;
                }
            }
        } catch (\Throwable) {
        }
        $user = User::find($sellerId);
        return $user ? User::displayName($user) : 'Un prestataire';
    }
}
