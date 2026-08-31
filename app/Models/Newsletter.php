<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Core\Mailer;
use RuntimeException;

final class Newsletter
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    public static function subscribe(string $email, string $source = 'footer', ?int $userId = null, bool $immediate = false): string
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Indiquez une adresse e-mail valide.');
        }
        if ($userId === null) {
            $user = User::findByEmail($email);
            $userId = $user ? (int) $user['id'] : null;
        }

        $row = self::findByEmail($email);
        if ($row && ($row['status'] ?? '') === self::STATUS_CONFIRMED) {
            return 'already';
        }

        $confirm = self::freshToken();
        $unsub = (string) ($row['unsub_token'] ?? '') ?: self::freshToken();
        $status = $immediate ? self::STATUS_CONFIRMED : self::STATUS_PENDING;

        if ($row) {
            Database::query(
                'UPDATE newsletter_subscribers
                 SET status = ?, confirm_token = ?, unsub_token = ?, user_id = COALESCE(?, user_id),
                     source = ?, confirmed_at = IF(? = "confirmed", COALESCE(confirmed_at, NOW()), NULL),
                     unsubscribed_at = NULL, confirm_sent_at = IF(? = "pending", NOW(), confirm_sent_at)
                 WHERE id = ?',
                [$status, $status === self::STATUS_PENDING ? $confirm : null, $unsub, $userId, $source, $status, $status, (int) $row['id']]
            );
        } else {
            Database::query(
                'INSERT INTO newsletter_subscribers
                    (email, created_at, status, confirm_token, unsub_token, user_id, source, confirmed_at, confirm_sent_at)
                 VALUES (?, NOW(), ?, ?, ?, ?, ?, IF(? = "confirmed", NOW(), NULL), IF(? = "pending", NOW(), NULL))',
                [$email, $status, $status === self::STATUS_PENDING ? $confirm : null, $unsub, $userId, $source, $status, $status]
            );
        }

        if ($status === self::STATUS_CONFIRMED) {
            self::syncUserFlag($email, true);
            return 'confirmed';
        }

        $token = $confirm;
        try {
            Mailer::sendTemplate('newsletter-confirm', $email, [
                'lien_confirmation' => url('/newsletter/confirmer/' . $token),
            ]);
        } catch (\Throwable) {
            // l'inscription reste enregistrée ; l'admin peut renvoyer
        }

        return 'pending';
    }

    public static function confirm(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $row = Database::fetch('SELECT * FROM newsletter_subscribers WHERE confirm_token = ?', [$token]);
        if (!$row) {
            return null;
        }
        Database::query(
            'UPDATE newsletter_subscribers
             SET status = ?, confirm_token = NULL, confirmed_at = NOW(), unsubscribed_at = NULL
             WHERE id = ?',
            [self::STATUS_CONFIRMED, (int) $row['id']]
        );
        self::syncUserFlag((string) $row['email'], true);
        $row['status'] = self::STATUS_CONFIRMED;

        return $row;
    }

    public static function unsubscribeByToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $row = Database::fetch('SELECT * FROM newsletter_subscribers WHERE unsub_token = ?', [$token]);
        if (!$row) {
            return null;
        }
        self::markUnsubscribed((int) $row['id'], (string) $row['email']);

        return $row;
    }

    public static function unsubscribeEmail(string $email): void
    {
        $row = self::findByEmail($email);
        if (!$row) {
            return;
        }
        self::markUnsubscribed((int) $row['id'], (string) $row['email']);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetch(
            'SELECT * FROM newsletter_subscribers WHERE email = ?',
            [strtolower(trim($email))]
        );
    }

    public static function findByUnsubToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        return Database::fetch('SELECT * FROM newsletter_subscribers WHERE unsub_token = ?', [$token]);
    }

    /** @return list<array<string, mixed>> */
    public static function confirmed(): array
    {
        return Database::fetchAll(
            'SELECT * FROM newsletter_subscribers WHERE status = ? ORDER BY id ASC',
            [self::STATUS_CONFIRMED]
        );
    }

    public static function countByStatus(?string $status = null): int
    {
        if ($status === null) {
            $row = Database::fetch('SELECT COUNT(*) AS n FROM newsletter_subscribers');
        } else {
            $row = Database::fetch('SELECT COUNT(*) AS n FROM newsletter_subscribers WHERE status = ?', [$status]);
        }
        return (int) ($row['n'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public static function adminList(int $limit = 200): array
    {
        return Database::fetchAll(
            'SELECT * FROM newsletter_subscribers ORDER BY created_at DESC, id DESC LIMIT ' . max(1, $limit)
        );
    }

    public static function setting(string $key, string $default = ''): string
    {
        try {
            $value = Setting::get($key);
            if ($value !== null) {
                return $value;
            }
        } catch (\Throwable) {
        }
        return $default;
    }

    public static function enabled(): bool
    {
        return self::setting('newsletter_enabled', '0') === '1';
    }

    public static function batchSize(): int
    {
        return max(1, min(200, (int) self::setting('newsletter_batch_size', '25')));
    }

    public static function weekday(): int
    {
        return max(1, min(7, (int) self::setting('newsletter_weekday', '3')));
    }

    public static function hour(): int
    {
        return max(0, min(23, (int) self::setting('newsletter_hour', '8')));
    }

    public static function includeMissions(): bool
    {
        return self::setting('newsletter_include_missions', '1') === '1';
    }

    public static function includePeople(): bool
    {
        return self::setting('newsletter_include_people', '1') === '1';
    }

    public static function includeUrl(): bool
    {
        return self::setting('newsletter_include_url', '1') === '1';
    }

    public static function sourceUrl(): string
    {
        return trim(self::setting('newsletter_source_url', ''));
    }

    private static function markUnsubscribed(int $id, string $email): void
    {
        Database::query(
            'UPDATE newsletter_subscribers
             SET status = ?, confirm_token = NULL, unsubscribed_at = NOW()
             WHERE id = ?',
            [self::STATUS_UNSUBSCRIBED, $id]
        );
        self::syncUserFlag($email, false);
    }

    private static function syncUserFlag(string $email, bool $on): void
    {
        $user = User::findByEmail($email);
        if (!$user) {
            return;
        }
        try {
            User::update((int) $user['id'], ['notify_newsletter' => $on ? 1 : 0]);
        } catch (\Throwable) {
        }
    }

    private static function freshToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}
