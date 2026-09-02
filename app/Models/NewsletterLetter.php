<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Core\NewsletterBuilder;
use RuntimeException;

final class NewsletterLetter
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';

    public static function find(int $id): ?array
    {
        $row = Database::fetch('SELECT * FROM newsletter_letters WHERE id = ?', [$id]);
        return $row ? self::present($row) : null;
    }

    /** @return list<array<string, mixed>> */
    public static function all(int $limit = 80): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM newsletter_letters ORDER BY updated_at DESC, id DESC LIMIT ' . max(1, $limit)
        );
        return array_map([self::class, 'present'], $rows);
    }

    public static function count(): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS n FROM newsletter_letters');
        return (int) ($row['n'] ?? 0);
    }

    /**
     * @param array{subject?: string, preheader?: string, blocks?: list<array<string, mixed>>} $data
     */
    public static function save(?int $id, array $data): int
    {
        $subject = trim((string) ($data['subject'] ?? ''));
        if ($subject === '') {
            throw new RuntimeException('Indiquez le sujet de la lettre.');
        }
        if (mb_strlen($subject) > 180) {
            throw new RuntimeException('Le sujet ne peut pas dépasser 180 caractères.');
        }
        $preheader = mb_substr(trim((string) ($data['preheader'] ?? '')), 0, 180);
        $blocks = NewsletterBuilder::normalize(is_array($data['blocks'] ?? null) ? $data['blocks'] : []);
        if ($blocks === []) {
            throw new RuntimeException('Ajoutez au moins un bloc à la lettre.');
        }
        $html = NewsletterBuilder::render($blocks, $preheader);
        $json = json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new RuntimeException('Impossible d’enregistrer les blocs de la lettre.');
        }

        $current = $id ? self::find($id) : null;
        if ($id && !$current) {
            throw new RuntimeException('Lettre introuvable.');
        }

        if ($current) {
            Database::query(
                'UPDATE newsletter_letters
                 SET subject = ?, preheader = ?, blocks_json = ?, body_html = ?
                 WHERE id = ?',
                [$subject, $preheader, $json, $html, $id]
            );
            return (int) $id;
        }

        Database::query(
            'INSERT INTO newsletter_letters (subject, preheader, blocks_json, body_html, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
            [$subject, $preheader, $json, $html, self::STATUS_DRAFT]
        );
        return (int) Database::lastId();
    }

    public static function markSent(int $id, int $campaignId): void
    {
        Database::query(
            'UPDATE newsletter_letters SET status = ?, campaign_id = ?, sent_at = NOW() WHERE id = ?',
            [self::STATUS_SENT, $campaignId, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM newsletter_letters WHERE id = ?', [$id]);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_SENT => 'Envoyée',
            default => 'Brouillon',
        };
    }

    public static function statusTone(string $status): string
    {
        return $status === self::STATUS_SENT ? 'green' : 'navy';
    }

    /** @param array<string, mixed> $row */
    private static function present(array $row): array
    {
        $blocks = NewsletterBuilder::decode((string) ($row['blocks_json'] ?? ''));
        foreach ($blocks as $i => $block) {
            if (($block['type'] ?? '') === 'image') {
                $blocks[$i]['_url'] = NewsletterBuilder::publicImageUrl((string) ($block['src'] ?? ''));
            }
        }
        $row['blocks'] = $blocks;
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['status_label'] = self::statusLabel((string) ($row['status'] ?? self::STATUS_DRAFT));
        $row['status_tone'] = self::statusTone((string) ($row['status'] ?? self::STATUS_DRAFT));
        return $row;
    }
}
