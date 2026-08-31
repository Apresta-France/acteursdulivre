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

    public static function findForUser(int $id, int $userId): ?array
    {
        $row = Database::fetch(
            'SELECT m.*, u.first_name, u.last_name
             FROM missions m
             JOIN users u ON u.id = m.user_id
             WHERE m.id = ? AND m.user_id = ?',
            [$id, $userId]
        );
        return $row ? self::present($row) : null;
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
               AND u.status = "active"
             ORDER BY m.created_at DESC'
        );
        return array_map([self::class, 'present'], $rows);
    }

    public static function countOpen(): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM missions m
             JOIN users u ON u.id = m.user_id
             WHERE m.status = "open" AND u.status = "active"'
        );
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

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, int $userId, array $data): array
    {
        $mission = self::findForUser($id, $userId);
        if (!$mission) {
            throw new \RuntimeException('Cette recherche est introuvable.');
        }
        $current = (string) ($mission['status'] ?? '');
        if (!in_array($current, ['draft', 'open'], true)) {
            throw new \RuntimeException('Cette recherche ne peut plus être modifiée.');
        }

        $title = trim((string) ($data['title'] ?? ''));
        $newStatus = (string) ($data['status'] ?? $current);
        if (!isset(self::STATUSES[$newStatus])) {
            throw new \InvalidArgumentException('Statut de mission invalide.');
        }
        if ($current === 'open') {
            $newStatus = 'open';
        } elseif (!in_array($newStatus, ['draft', 'open'], true)) {
            throw new \RuntimeException('Statut de mission invalide.');
        }

        $slug = (string) ($mission['slug'] ?? '');
        if ($current === 'draft' && $title !== '' && $title !== (string) ($mission['title'] ?? '')) {
            $slug = unique_slug(
                $title,
                static fn (string $candidate): bool => Database::fetch(
                    'SELECT id FROM missions WHERE slug = ? AND id != ?',
                    [$candidate, $id]
                ) !== null
            );
        }

        $pick = static function (array $data, string $key, mixed $fallback): mixed {
            return array_key_exists($key, $data) ? $data[$key] : $fallback;
        };

        Database::query(
            'UPDATE missions
             SET category_name = ?, title = ?, slug = ?, brief = ?, volume = ?,
                 budget_min = ?, budget_max = ?, deadline = ?,
                 attachment_name = ?, attachment_path = ?, status = ?
             WHERE id = ? AND user_id = ?',
            [
                $pick($data, 'category_name', $mission['category_name'] ?? null),
                $title !== '' ? $title : ($mission['title'] ?? ''),
                $slug,
                $pick($data, 'brief', $mission['brief'] ?? null),
                $pick($data, 'volume', $mission['volume'] ?? null),
                $pick($data, 'budget_min', $mission['budget_min'] ?? null),
                $pick($data, 'budget_max', $mission['budget_max'] ?? null),
                $pick($data, 'deadline', $mission['deadline'] ?? null) ?: null,
                $pick($data, 'attachment_name', $mission['attachment_name'] ?? null),
                $pick($data, 'attachment_path', $mission['attachment_path'] ?? null),
                $newStatus,
                $id,
                $userId,
            ]
        );

        return self::find($id) ?? $mission;
    }

    public static function publishForUser(int $id, int $userId): array
    {
        $mission = self::findForUser($id, $userId);
        if (!$mission) {
            throw new \RuntimeException('Cette recherche est introuvable.');
        }
        if (($mission['status'] ?? '') !== 'draft') {
            throw new \RuntimeException('Cette recherche est déjà publiée.');
        }
        $title = trim((string) ($mission['title'] ?? ''));
        $brief = trim((string) ($mission['brief'] ?? ''));
        $category = trim((string) ($mission['category_name'] ?? ''));
        if ($title === '' || $brief === '' || $category === '') {
            throw new \RuntimeException('Complétez le métier, le titre et le brief avant de publier.');
        }
        Database::query(
            'UPDATE missions SET status = ? WHERE id = ? AND user_id = ?',
            ['open', $id, $userId]
        );
        return self::find($id) ?? $mission;
    }

    /**
     * @param array<string, mixed> $mission
     * @param array<string, mixed> $user
     */
    public static function canAccessAttachment(array $mission, array $user): bool
    {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId < 1) {
            return false;
        }
        if (($user['role'] ?? '') === 'admin') {
            return true;
        }
        if ((int) ($mission['user_id'] ?? 0) === $userId) {
            return true;
        }
        $status = (string) ($mission['status'] ?? '');
        if ($status === 'draft') {
            return false;
        }
        if (Application::findForUserOnMission((int) ($mission['id'] ?? 0), $userId)) {
            return true;
        }
        if ($status !== 'open' || !User::offersServices($user)) {
            return false;
        }
        $owner = User::find((int) ($mission['user_id'] ?? 0));
        return $owner !== null && ($owner['status'] ?? '') === 'active';
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
            $row['attachment_href'] = url('/missions/' . $row['slug'] . '/fichier');
        }
        return $row;
    }
}