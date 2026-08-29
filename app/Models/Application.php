<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class Application
{
    public const STATUSES = [
        'sent' => 'Envoyée',
        'viewed' => 'Vue',
        'accepted' => 'Acceptée',
        'rejected' => 'Non retenue',
    ];

    public static function find(int $id): ?array
    {
        $row = Database::fetch(
            'SELECT a.*, m.title, m.slug, m.category_name, m.status AS mission_status, m.user_id AS owner_id,
                    u.first_name, u.last_name, u.avatar_url
             FROM applications a
             JOIN missions m ON m.id = a.mission_id
             JOIN users u ON u.id = a.user_id
             WHERE a.id = ?',
            [$id]
        );
        return $row ? self::present($row) : null;
    }

    public static function findForUserOnMission(int $missionId, int $userId): ?array
    {
        $row = Database::fetch(
            'SELECT a.*, m.title, m.slug, m.category_name, m.status AS mission_status, m.user_id AS owner_id,
                    u.first_name, u.last_name, u.avatar_url
             FROM applications a
             JOIN missions m ON m.id = a.mission_id
             JOIN users u ON u.id = a.user_id
             WHERE a.mission_id = ? AND a.user_id = ?',
            [$missionId, $userId]
        );
        return $row ? self::present($row) : null;
    }

    public static function countForMission(int $missionId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM applications WHERE mission_id = ?',
            [$missionId]
        );
        return (int) ($row['n'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public static function forMission(int $missionId): array
    {
        $rows = Database::fetchAll(
            'SELECT a.*, m.title, m.slug, m.category_name, m.status AS mission_status, m.user_id AS owner_id,
                    u.first_name, u.last_name, u.avatar_url, p.slug AS profile_slug
             FROM applications a
             JOIN missions m ON m.id = a.mission_id
             JOIN users u ON u.id = a.user_id
             LEFT JOIN profiles p ON p.user_id = u.id
             WHERE a.mission_id = ?
             ORDER BY a.created_at ASC, a.id ASC',
            [$missionId]
        );
        return array_map([self::class, 'present'], $rows);
    }

    public static function create(int $missionId, int $userId, ?int $price, ?string $delay, string $message): array
    {
        $mission = Mission::find($missionId);
        if (!$mission || ($mission['status'] ?? '') !== 'open') {
            throw new \RuntimeException('Cette recherche n\'accepte plus de candidatures.');
        }
        if ((int) ($mission['user_id'] ?? 0) === $userId) {
            throw new \RuntimeException('Vous ne pouvez pas candidater à votre propre recherche.');
        }
        if (self::findForUserOnMission($missionId, $userId)) {
            throw new \RuntimeException('Vous avez déjà candidaté à cette recherche.');
        }

        $message = trim($message);
        if ($message === '') {
            throw new \RuntimeException('Présentez votre approche en quelques lignes.');
        }

        Database::query(
            'INSERT INTO applications (mission_id, user_id, price, delay, message, status, created_at)
             VALUES (?, ?, ?, ?, ?, "sent", NOW())',
            [$missionId, $userId, $price, $delay !== '' ? $delay : null, $message]
        );

        $application = self::find((int) Database::lastId()) ?? [];
        $who = User::displayName(User::find($userId) ?? []);
        Notification::create(
            (int) $mission['user_id'],
            'Nouvelle candidature sur « ' . $mission['title'] . ' »',
            $who . ' propose ses services' . ($price ? ' pour ' . format_euros($price) : '') . '.',
            '/missions/' . $mission['slug'] . '#candidatures',
            'application',
            'application',
            (int) ($application['id'] ?? 0)
        );

        return $application;
    }

    public static function markViewed(int $id, int $ownerId): void
    {
        $application = self::find($id);
        if (!$application || (int) ($application['owner_id'] ?? 0) !== $ownerId) {
            return;
        }
        if (($application['status'] ?? '') !== 'sent') {
            return;
        }
        Database::query('UPDATE applications SET status = "viewed" WHERE id = ? AND status = "sent"', [$id]);
    }

    public static function reject(int $id, int $ownerId): void
    {
        $application = self::find($id);
        if (!$application || (int) ($application['owner_id'] ?? 0) !== $ownerId) {
            throw new \RuntimeException('Cette candidature est introuvable.');
        }
        if (!in_array((string) ($application['status'] ?? ''), ['sent', 'viewed'], true)) {
            throw new \RuntimeException('Cette candidature a déjà été traitée.');
        }
        Database::query('UPDATE applications SET status = "rejected" WHERE id = ?', [$id]);
        Notification::create(
            (int) $application['user_id'],
            'Candidature non retenue',
            'Votre proposition sur « ' . $application['title'] . ' » n\'a pas été retenue.',
            (string) ($application['href'] ?? '/espace/candidatures'),
            'application_rejected',
            'application',
            $id
        );
    }

    public static function accept(int $id, int $ownerId): array
    {
        return Database::transaction(static function () use ($id, $ownerId): array {
            $application = self::find($id);
            if (!$application || (int) ($application['owner_id'] ?? 0) !== $ownerId) {
                throw new \RuntimeException('Cette candidature est introuvable.');
            }
            $mission = Mission::find((int) $application['mission_id']);
            if (!$mission || ($mission['status'] ?? '') !== 'open') {
                throw new \RuntimeException('Cette recherche n\'est plus ouverte.');
            }
            if (!in_array((string) ($application['status'] ?? ''), ['sent', 'viewed'], true)) {
                throw new \RuntimeException('Cette candidature a déjà été traitée.');
            }

            $pending = array_values(array_filter(
                self::forMission((int) $application['mission_id']),
                static fn (array $row): bool => (int) $row['id'] !== $id
                    && in_array((string) ($row['status'] ?? ''), ['sent', 'viewed'], true)
            ));

            Database::query('UPDATE applications SET status = "accepted" WHERE id = ?', [$id]);
            Database::query(
                'UPDATE applications SET status = "rejected" WHERE mission_id = ? AND id != ? AND status IN ("sent", "viewed")',
                [(int) $application['mission_id'], $id]
            );
            Mission::setStatus((int) $application['mission_id'], 'assigned');

            $order = Order::create([
                'buyer_id' => $ownerId,
                'seller_id' => (int) $application['user_id'],
                'mission_id' => (int) $application['mission_id'],
                'amount' => (int) ($application['raw_price'] ?? $application['price'] ?? 0),
                'brief' => (string) ($application['message'] ?? ''),
                'package_name' => null,
            ]);

            Conversation::open($ownerId, (int) $application['user_id'], [
                'subject' => (string) $mission['title'],
                'order_id' => (int) $order['id'],
                'mission_id' => (int) $mission['id'],
            ]);

            foreach ($pending as $other) {
                Notification::create(
                    (int) $other['user_id'],
                    'Candidature non retenue',
                    'La recherche « ' . $mission['title'] . ' » a été attribuée à un autre prestataire.',
                    (string) ($other['href'] ?? '/espace/candidatures'),
                    'application_rejected',
                    'application',
                    (int) $other['id']
                );
            }

            Notification::create(
                (int) $application['user_id'],
                'Candidature acceptée',
                'Votre proposition sur « ' . $mission['title'] . ' » a été retenue. Envoyez le devis pour lancer les jalons.',
                '/espace/suivi/' . (int) $order['id'],
                'application_accepted',
                'order',
                (int) $order['id']
            );

            return $order;
        });
    }

    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT a.*, m.title, m.slug, m.category_name, m.status AS mission_status,
                    u.first_name, u.last_name
             FROM applications a
             JOIN missions m ON m.id = a.mission_id
             JOIN users u ON u.id = m.user_id
             WHERE a.user_id = ?
             ORDER BY a.created_at DESC',
            [$userId]
        );

        return array_map([self::class, 'present'], $rows);
    }

    /** @param array<string, mixed> $row */
    private static function present(array $row): array
    {
        $status = (string) ($row['status'] ?? 'sent');
        $row['raw_price'] = isset($row['price']) ? (int) $row['price'] : null;
        $row['by'] = User::displayName($row);
        $row['initials'] = User::initials($row);
        $row['price'] = isset($row['raw_price']) ? format_euros($row['raw_price']) : '—';
        $row['when'] = time_ago($row['created_at'] ?? null);
        $row['status_label'] = self::STATUSES[$status] ?? 'Envoyée';
        $row['status_tone'] = match ($status) {
            'accepted' => 'green',
            'rejected' => 'grey',
            'viewed' => 'navy',
            default => 'orange',
        };
        $row['href'] = '/missions/' . ($row['slug'] ?? '');
        $row['profile_href'] = !empty($row['profile_slug']) ? '/prestataires/' . $row['profile_slug'] : '';
        return $row;
    }
}
