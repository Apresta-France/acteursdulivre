<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use RuntimeException;

final class NewsletterCampaign
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_EMPTY = 'empty';

    public const AUDIENCE_CONFIRMED = 'confirmed';
    public const AUDIENCE_ACCOUNTS = 'accounts';

    /**
     * @param array{subject: string, html: string} $composed
     */
    public static function queue(array $composed, string $source = 'manual', string $audience = self::AUDIENCE_CONFIRMED): int
    {
        [$subject, $html] = self::content($composed);
        $audience = self::audience($audience);
        $subscribers = self::recipients($audience);
        Database::query(
            'INSERT INTO newsletter_campaigns (subject, body_html, source, audience, status, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())',
            [$subject, $html, $source, $audience, $subscribers === [] ? self::STATUS_EMPTY : self::STATUS_QUEUED]
        );
        $id = (int) Database::lastId();
        if ($subscribers === []) {
            Database::query(
                'UPDATE newsletter_campaigns SET finished_at = NOW() WHERE id = ?',
                [$id]
            );
            throw new RuntimeException(self::emptyAudienceMessage($audience));
        }

        self::insertDeliveries($id, $subscribers);

        return $id;
    }

    /**
     * Met à jour le contenu et n’ajoute que les destinataires qui n’ont pas déjà reçu la campagne.
     * Les échecs sont remis en file.
     *
     * @param array{subject: string, html: string} $composed
     * @return array{added: int, already: int, retried: int}
     */
    public static function relaunch(int $id, array $composed, string $audience = self::AUDIENCE_CONFIRMED): array
    {
        $campaign = self::find($id);
        if (!$campaign) {
            throw new RuntimeException('Campagne introuvable.');
        }
        [$subject, $html] = self::content($composed);
        $audience = self::audience($audience);

        $failed = Database::fetch(
            'SELECT COUNT(*) AS n FROM newsletter_deliveries WHERE campaign_id = ? AND status = "failed"',
            [$id]
        );
        $retried = (int) ($failed['n'] ?? 0);
        if ($retried > 0) {
            Database::query(
                'UPDATE newsletter_deliveries SET status = "pending", error = NULL WHERE campaign_id = ? AND status = "failed"',
                [$id]
            );
            Database::query(
                'UPDATE newsletter_campaigns SET fail_count = GREATEST(0, fail_count - ?) WHERE id = ?',
                [$retried, $id]
            );
        }

        Database::query(
            'UPDATE newsletter_campaigns
             SET subject = ?, body_html = ?, audience = ?, status = ?, finished_at = NULL
             WHERE id = ?',
            [$subject, $html, $audience, self::STATUS_QUEUED, $id]
        );

        $existing = Database::fetchAll(
            'SELECT LOWER(email) AS email, status FROM newsletter_deliveries WHERE campaign_id = ?',
            [$id]
        );
        $known = [];
        $already = 0;
        foreach ($existing as $row) {
            $known[(string) $row['email']] = true;
            if (($row['status'] ?? '') === 'sent') {
                $already++;
            }
        }

        $added = 0;
        $fresh = [];
        foreach (self::recipients($audience) as $row) {
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            if ($email === '' || isset($known[$email])) {
                continue;
            }
            $known[$email] = true;
            $fresh[] = $row;
            $added++;
        }
        self::insertDeliveries($id, $fresh);

        $left = Database::fetch(
            'SELECT COUNT(*) AS n FROM newsletter_deliveries WHERE campaign_id = ? AND status IN ("pending", "sending")',
            [$id]
        );
        if ((int) ($left['n'] ?? 0) < 1) {
            Database::query(
                'UPDATE newsletter_campaigns SET status = ?, finished_at = NOW() WHERE id = ?',
                [self::STATUS_SENT, $id]
            );
            throw new RuntimeException('Tout le monde a déjà reçu cette lettre.');
        }

        return ['added' => $added, 'already' => $already, 'retried' => $retried];
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM newsletter_campaigns WHERE id = ?', [$id]);
    }

    /** @return list<array<string, mixed>> */
    public static function recent(int $limit = 12): array
    {
        return Database::fetchAll(
            'SELECT * FROM newsletter_campaigns ORDER BY id DESC LIMIT ' . max(1, $limit)
        );
    }

    /** @return list<array<string, mixed>> */
    public static function pendingBatch(int $limit): array
    {
        $limit = max(1, min(200, $limit));
        return Database::fetchAll(
            'SELECT d.*, c.subject, c.body_html, s.unsub_token
             FROM newsletter_deliveries d
             JOIN newsletter_campaigns c ON c.id = d.campaign_id
             JOIN newsletter_subscribers s ON s.id = d.subscriber_id
             WHERE d.status = "pending"
               AND (
                 (c.audience = "accounts" AND s.status <> "unsubscribed")
                 OR (c.audience <> "accounts" AND s.status = "confirmed")
               )
             ORDER BY d.id ASC
             LIMIT ' . $limit
        );
    }

    public static function claimDelivery(int $id): bool
    {
        $updated = Database::query(
            'UPDATE newsletter_deliveries SET status = "sending" WHERE id = ? AND status = "pending"',
            [$id]
        );
        return $updated->rowCount() === 1;
    }

    public static function markSending(int $campaignId): void
    {
        Database::query(
            'UPDATE newsletter_campaigns SET status = ?, started_at = COALESCE(started_at, NOW()) WHERE id = ?',
            [self::STATUS_SENDING, $campaignId]
        );
    }

    public static function markDelivery(int $id, int $campaignId, string $status, string $error = ''): void
    {
        Database::query(
            'UPDATE newsletter_deliveries
             SET status = ?, error = ?, sent_at = IF(? = "sent", NOW(), sent_at)
             WHERE id = ?',
            [$status, $error !== '' ? mb_substr($error, 0, 255) : null, $status, $id]
        );
        $col = $status === 'sent' ? 'sent_count' : ($status === 'failed' ? 'fail_count' : 'skip_count');
        Database::query(
            'UPDATE newsletter_campaigns SET ' . $col . ' = ' . $col . ' + 1 WHERE id = ?',
            [$campaignId]
        );
        self::maybeFinish($campaignId);
    }

    public static function skipUnsubscribedPending(): int
    {
        $rows = Database::fetchAll(
            'SELECT d.id, d.campaign_id
             FROM newsletter_deliveries d
             JOIN newsletter_subscribers s ON s.id = d.subscriber_id
             JOIN newsletter_campaigns c ON c.id = d.campaign_id
             WHERE d.status = "pending"
               AND (
                 (c.audience = "accounts" AND s.status = "unsubscribed")
                 OR (c.audience <> "accounts" AND s.status <> "confirmed")
               )'
        );
        foreach ($rows as $row) {
            self::markDelivery((int) $row['id'], (int) $row['campaign_id'], 'skipped', 'désinscrit');
        }
        return count($rows);
    }

    public static function pendingCount(): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS n FROM newsletter_deliveries WHERE status = "pending"');
        return (int) ($row['n'] ?? 0);
    }

    public static function releaseStaleSending(): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS n FROM newsletter_deliveries WHERE status = "sending"');
        $n = (int) ($row['n'] ?? 0);
        if ($n < 1) {
            return 0;
        }
        Database::query('UPDATE newsletter_deliveries SET status = "pending" WHERE status = "sending"');
        return $n;
    }

    /** @param array{subject?: string, html?: string} $composed
     *  @return array{0: string, 1: string}
     */
    private static function content(array $composed): array
    {
        $subject = trim((string) ($composed['subject'] ?? ''));
        $html = (string) ($composed['html'] ?? '');
        if ($subject === '' || $html === '') {
            throw new RuntimeException('La lettre n\'a pas de contenu à envoyer.');
        }

        return [$subject, $html];
    }

    public static function audience(string $audience): string
    {
        return $audience === self::AUDIENCE_ACCOUNTS ? self::AUDIENCE_ACCOUNTS : self::AUDIENCE_CONFIRMED;
    }

    /** @return list<array<string, mixed>> */
    private static function recipients(string $audience): array
    {
        return $audience === self::AUDIENCE_ACCOUNTS
            ? Newsletter::accountRecipients()
            : Newsletter::confirmed();
    }

    private static function emptyAudienceMessage(string $audience): string
    {
        return $audience === self::AUDIENCE_ACCOUNTS
            ? 'Aucun compte à qui écrire : rien à envoyer.'
            : 'Aucun abonné confirmé : rien à envoyer.';
    }

    /** @param list<array<string, mixed>> $subscribers */
    private static function insertDeliveries(int $campaignId, array $subscribers): void
    {
        if ($subscribers === []) {
            return;
        }
        $stmt = Database::pdo()->prepare(
            'INSERT INTO newsletter_deliveries (campaign_id, subscriber_id, email, status)
             VALUES (?, ?, ?, "pending")'
        );
        foreach ($subscribers as $row) {
            $stmt->execute([$campaignId, (int) $row['id'], (string) $row['email']]);
        }
    }

    private static function maybeFinish(int $campaignId): void
    {
        $left = Database::fetch(
            'SELECT COUNT(*) AS n FROM newsletter_deliveries WHERE campaign_id = ? AND status IN ("pending", "sending")',
            [$campaignId]
        );
        if ((int) ($left['n'] ?? 0) > 0) {
            return;
        }
        Database::query(
            'UPDATE newsletter_campaigns SET status = ?, finished_at = NOW() WHERE id = ?',
            [self::STATUS_SENT, $campaignId]
        );
    }
}
