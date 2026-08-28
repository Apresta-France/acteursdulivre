<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class Mission
{
    public const STATUSES = [
        'draft' => 'Brouillon',
        'open' => 'Ouverte',
        'assigned' => 'Attribuée',
        'closed' => 'Clôturée',
    ];

    public static function find(int $id): ?array
    {
        $row = Database::fetch(
            'SELECT m.*, u.first_name, u.last_name
             FROM missions m
             JOIN users u ON u.id = m.user_id
             WHERE m.id = ?',
            [$id]
        );
        return $row ? self::present($row) : null;
    }

    public static function setStatus(int $id, string $status): void
    {
        if (!isset(self::STATUSES[$status])) {
            throw new \InvalidArgumentException('Statut de mission invalide.');
        }
        if (!self::find($id)) {
            throw new \RuntimeException('Mission introuvable.');
        }
        Database::query('UPDATE missions SET status = ? WHERE id = ?', [$status, $id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        $row = Database::fetch(
            'SELECT m.*, u.first_name, u.last_name
             FROM missions m
             JOIN users u ON u.id = m.user_id
             WHERE m.slug = ?',
            [$slug]
        );
        return $row ? self::present($row) : null;
    }

    /** @return list<array<string, mixed>> */
    public static function open(): array
    {
        $rows = Database::fetchAll(
            'SELECT m.*, u.first_name, u.last_name
             FROM missions m
             JOIN users u ON u.id = m.user_id
             WHERE m.status = "open"
             ORDER BY m.created_at DESC'
        );
        return array_map([self::class, 'present'], $rows);
    }

    public static function countOpen(): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS n FROM missions WHERE status = "open"');
        return (int) ($row['n'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $rows = Database::fetchAll(
            'SELECT m.*, u.first_name, u.last_name
             FROM missions m
             JOIN users u ON u.id = m.user_id
             ORDER BY m.created_at DESC'
        );
        return array_map([self::class, 'present'], $rows);
    }

    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT m.*, u.first_name, u.last_name
             FROM missions m
             JOIN users u ON u.id = m.user_id
             WHERE m.user_id = ?
             ORDER BY m.created_at DESC',
            [$userId]
        );
        return array_map([self::class, 'present'], $rows);
    }

    /** @param array<string, mixed> $data */
    public static function create(int $userId, array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $slug = unique_slug(
            $title,
            static fn (string $candidate): bool => Database::fetch('SELECT id FROM missions WHERE slug = ?', [$candidate]) !== null
        );

        Database::query(
            'INSERT INTO missions
                (user_id, category_name, title, slug, brief, volume, budget_min, budget_max, deadline, attachment_name, attachment_path, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $data['category_name'] ?? null,
                $title,
                $slug,
                $data['brief'] ?? null,
                $data['volume'] ?? null,
                $data['budget_min'] ?? null,
                $data['budget_max'] ?? null,
                $data['deadline'] ?: null,
                $data['attachment_name'] ?? null,
                $data['attachment_path'] ?? null,
                $data['status'] ?? 'open',
            ]
        );

        return self::findBySlug($slug) ?? ['slug' => $slug];
    }

    public static function budgetLabel(?int $min, ?int $max): string
    {
        if ($min && $max && $min !== $max) {
            return $min . ' – ' . $max . ' €';
        }
        if ($max) {
            return $max . ' €';
        }
        if ($min) {
            return 'à partir de ' . $min . ' €';
        }
        return 'Budget à convenir';
    }

    /** @param array<string, mixed> $row */
    private static function present(array $row): array
    {
        $row['by'] = User::displayName($row);
        $row['initials'] = User::initials($row);
        $row['budget'] = self::budgetLabel(
            isset($row['budget_min']) ? (int) $row['budget_min'] : null,
            isset($row['budget_max']) ? (int) $row['budget_max'] : null
        );
        $row['deadline_label'] = format_deadline($row['deadline'] ?? null);
        $row['when'] = time_ago($row['created_at'] ?? null);
        $row['status_label'] = self::STATUSES[$row['status'] ?? 'open'] ?? 'Ouverte';
        $row['href'] = '/missions/' . $row['slug'];
        $row['applicants'] = Application::countForMission((int) $row['id']);
        $row['live'] = true;
        if (!empty($row['attachment_path'])) {
            $row['attachment_href'] = uploaded((string) $row['attachment_path']);
        }
        return $row;
    }
}