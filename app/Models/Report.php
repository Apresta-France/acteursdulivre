<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use RuntimeException;

final class Report
{
    public const TYPES = ['user', 'service', 'mission', 'order', 'review', 'conversation'];

    public const REASONS = [
        'ia' => 'Contenu généré par IA',
        'hors_plateforme' => 'Contournement de la plateforme',
        'abus' => 'Propos abusifs ou harcèlement',
        'usurpation' => 'Usurpation ou fausse identité',
        'autre' => 'Autre signalement',
    ];

    /** @var list<string> */
    public const CONVERSATION_REASON_KEYS = ['hors_plateforme', 'abus', 'usurpation', 'autre'];

    public const STATUSES = [
        'open' => 'Ouvert',
        'closed' => 'Traité',
    ];

    /** @return list<array<string, mixed>> */
    public static function open(): array
    {
        return self::list('open');
    }

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        return self::list(null);
    }

    public static function create(?int $reporterId, string $type, ?int $targetId, string $reason, string $body): int
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new RuntimeException('Type de signalement invalide.');
        }
        if (!isset(self::REASONS[$reason])) {
            throw new RuntimeException('Indiquez le motif du signalement.');
        }
        $body = trim($body);
        if ($body === '' && $reason === 'autre') {
            throw new RuntimeException('Précisez le motif de votre signalement.');
        }

        Database::query(
            'INSERT INTO reports (reporter_id, target_type, target_id, reason, body, status, created_at)
             VALUES (?, ?, ?, ?, ?, "open", NOW())',
            [$reporterId, $type, $targetId, $reason, $body !== '' ? $body : null]
        );
        return (int) Database::lastId();
    }

    public static function setStatus(int $id, string $status): void
    {
        if (!isset(self::STATUSES[$status])) {
            throw new RuntimeException('Statut de signalement invalide.');
        }
        Database::query('UPDATE reports SET status = ? WHERE id = ?', [$status, $id]);
    }

    public static function countOpen(): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS n FROM reports WHERE status = "open"');
        return (int) ($row['n'] ?? 0);
    }

    public static function countOpenForType(string $type): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM reports WHERE status = "open" AND target_type = ?',
            [$type]
        );
        return (int) ($row['n'] ?? 0);
    }

    public static function hasOpen(int $reporterId, string $type, int $targetId): bool
    {
        $row = Database::fetch(
            'SELECT id FROM reports
             WHERE reporter_id = ? AND target_type = ? AND target_id = ? AND status = "open"
             LIMIT 1',
            [$reporterId, $type, $targetId]
        );
        return $row !== null;
    }

    /** @return array<string, string> */
    public static function reasonsFor(string $type): array
    {
        if ($type === 'conversation') {
            return array_intersect_key(self::REASONS, array_flip(self::CONVERSATION_REASON_KEYS));
        }

        return self::REASONS;
    }

    /** @return list<array<string, mixed>> */
    public static function forTarget(string $type, int $targetId): array
    {
        return self::list(null, $type, $targetId);
    }

    /** @return list<array<string, mixed>> */
    private static function list(?string $status, ?string $type = null, ?int $targetId = null): array
    {
        $sql = 'SELECT r.*, u.first_name, u.last_name, u.email
                FROM reports r
                LEFT JOIN users u ON u.id = r.reporter_id';
        $where = [];
        $params = [];
        if ($status !== null) {
            $where[] = 'r.status = ?';
            $params[] = $status;
        }
        if ($type !== null) {
            $where[] = 'r.target_type = ?';
            $params[] = $type;
        }
        if ($targetId !== null) {
            $where[] = 'r.target_id = ?';
            $params[] = $targetId;
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY r.created_at DESC, r.id DESC';
        $rows = Database::fetchAll($sql, $params);

        return array_map(static function (array $row): array {
            $row['who'] = $row['first_name'] ? User::displayName($row) : 'Visiteur';
            $row['reason_label'] = self::REASONS[$row['reason'] ?? ''] ?? (string) $row['reason'];
            $row['status_label'] = self::STATUSES[$row['status'] ?? 'open'] ?? 'Ouvert';
            $row['type_label'] = match ($row['target_type'] ?? '') {
                'user' => 'Profil',
                'service' => 'Prestation',
                'mission' => 'Mission',
                'order' => 'Commande',
                'review' => 'Avis',
                'conversation' => 'Conversation',
                default => (string) ($row['target_type'] ?? ''),
            };
            $row['when'] = time_ago($row['created_at'] ?? null);
            $targetId = (int) ($row['target_id'] ?? 0);
            $row['href'] = match ($row['target_type'] ?? '') {
                'service' => self::serviceHref($targetId),
                'mission' => self::missionHref($targetId),
                'user' => $targetId > 0 ? '/admin/utilisateurs/' . $targetId : '/admin/moderation',
                'order' => '/admin/finances',
                'conversation' => $targetId > 0 ? '/admin/conversations/' . $targetId : '/admin/moderation',
                default => '/admin/moderation',
            };
            return $row;
        }, $rows);
    }

    private static function serviceHref(int $id): string
    {
        if ($id < 1) {
            return '/admin/moderation';
        }
        $row = Database::fetch('SELECT slug FROM services WHERE id = ?', [$id]);
        return $row ? '/prestations/' . $row['slug'] : '/admin/moderation';
    }

    private static function missionHref(int $id): string
    {
        if ($id < 1) {
            return '/admin/moderation';
        }
        $row = Database::fetch('SELECT slug FROM missions WHERE id = ?', [$id]);
        return $row ? '/missions/' . $row['slug'] : '/admin/moderation';
    }
}
