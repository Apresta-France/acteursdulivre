<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Core\Mailer;
use Adl\Data\Share;
use RuntimeException;

final class PublisherClaim
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REFUSED = 'refused';

    /** Demandes par compte et par 24 h. */
    public const DAILY_LIMIT = 3;

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_APPROVED => 'Validée',
            self::STATUS_REFUSED => 'Refusée',
            default => 'En attente',
        };
    }

    public static function find(int $id): ?array
    {
        $row = Database::fetch(
            'SELECT c.*, p.name AS publisher_name, p.slug AS publisher_slug, p.website AS publisher_website, p.owner_user_id,
                    u.email AS user_email, u.first_name, u.last_name
             FROM publisher_claims c
             JOIN publishers p ON p.id = c.publisher_id
             JOIN users u ON u.id = c.user_id
             WHERE c.id = ?',
            [$id]
        );
        return $row ? self::hydrate($row) : null;
    }

    public static function pendingFor(int $publisherId, int $userId): ?array
    {
        $row = Database::fetch(
            'SELECT * FROM publisher_claims WHERE publisher_id = ? AND user_id = ? AND status = "pending" LIMIT 1',
            [$publisherId, $userId]
        );
        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT c.*, p.name AS publisher_name, p.slug AS publisher_slug, p.owner_user_id, p.website AS publisher_website
             FROM publisher_claims c JOIN publishers p ON p.id = c.publisher_id
             WHERE c.user_id = ? ORDER BY c.created_at DESC',
            [$userId]
        );
        return array_map([self::class, 'hydrate'], $rows);
    }

    public static function countPending(): int
    {
        return (int) (Database::fetch('SELECT COUNT(*) AS n FROM publisher_claims WHERE status = "pending"')['n'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public static function forAdmin(string $filter = 'pending'): array
    {
        $where = match ($filter) {
            'approved' => 'c.status = "approved"',
            'refused' => 'c.status = "refused"',
            'tous' => '1=1',
            default => 'c.status = "pending"',
        };
        $rows = Database::fetchAll(
            'SELECT c.*, p.name AS publisher_name, p.slug AS publisher_slug, p.website AS publisher_website, p.owner_user_id,
                    p.country AS publisher_country, p.city AS publisher_city,
                    u.email AS user_email, u.first_name, u.last_name, u.avatar_url, u.created_at AS user_since
             FROM publisher_claims c
             JOIN publishers p ON p.id = c.publisher_id
             JOIN users u ON u.id = c.user_id
             WHERE ' . $where . '
             ORDER BY (c.status = "pending") DESC, c.created_at DESC
             LIMIT 200'
        );
        return array_map([self::class, 'hydrate'], $rows);
    }

    /**
     * @param array{role_title: string, company_email: string, message: string, phone?: string} $data
     */
    public static function create(array $publisher, array $user, array $data): int
    {
        $userId = (int) $user['id'];
        $publisherId = (int) $publisher['id'];

        if (!empty($publisher['owner_user_id'])) {
            if ((int) $publisher['owner_user_id'] === $userId) {
                throw new RuntimeException('Vous gérez déjà cette fiche.');
            }
            throw new RuntimeException('Cette fiche a déjà été revendiquée. Si vous pensez qu\'il s\'agit d\'une erreur, écrivez-nous via la page contact.');
        }
        if (self::pendingFor($publisherId, $userId)) {
            throw new RuntimeException('Votre demande pour cette maison est déjà en cours d\'examen.');
        }
        $today = (int) (Database::fetch(
            'SELECT COUNT(*) AS n FROM publisher_claims WHERE user_id = ? AND created_at > (NOW() - INTERVAL 1 DAY)',
            [$userId]
        )['n'] ?? 0);
        if ($today >= self::DAILY_LIMIT) {
            throw new RuntimeException('Vous avez déjà envoyé plusieurs demandes aujourd\'hui. Réessayez demain.');
        }

        $role = trim($data['role_title']);
        $email = strtolower(trim($data['company_email']));
        $message = trim($data['message']);
        $phone = trim((string) ($data['phone'] ?? ''));
        if (mb_strlen($role) < 2) {
            throw new RuntimeException('Indiquez votre fonction au sein de la maison.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Indiquez une adresse e-mail professionnelle valide.');
        }
        if (mb_strlen($message) < 20) {
            throw new RuntimeException('Décrivez en quelques lignes votre lien avec la maison (20 caractères minimum).');
        }
        if (mb_strlen($message) > 2000) {
            throw new RuntimeException('Le message est limité à 2 000 caractères.');
        }

        $domainMatch = self::domainMatches($email, (string) ($publisher['website'] ?? ''), (string) ($publisher['contact_email'] ?? ''));

        Database::query(
            'INSERT INTO publisher_claims (publisher_id, user_id, role_title, company_email, phone, message, domain_match, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, "pending", NOW())',
            [$publisherId, $userId, mb_substr($role, 0, 120), mb_substr($email, 0, 190), mb_substr($phone, 0, 40), $message, $domainMatch ? 1 : 0]
        );
        $id = (int) Database::lastId();

        self::notifyAdmins($id, $publisher, $user, $role, $email, $message, $domainMatch);
        Mailer::notify($user, 'transactional', 'maison-revendication-recue', [
            'maison' => (string) $publisher['name'],
            'lien_fiche' => Share::absolute((string) $publisher['href']),
        ]);

        return $id;
    }

    public static function domainMatches(string $email, string $website, string $contactEmail): bool
    {
        $domain = strtolower((string) substr($email, (int) strrpos($email, '@') + 1));
        if ($domain === '') {
            return false;
        }
        $candidates = [];
        $host = Publisher::websiteHost($website);
        if ($host !== '') {
            $candidates[] = strtolower($host);
        }
        if ($contactEmail !== '' && str_contains($contactEmail, '@')) {
            $candidates[] = strtolower(substr($contactEmail, strrpos($contactEmail, '@') + 1));
        }
        foreach ($candidates as $candidate) {
            if ($candidate === $domain || str_ends_with($domain, '.' . $candidate) || str_ends_with($candidate, '.' . $domain)) {
                return true;
            }
        }
        return false;
    }

    private static function notifyAdmins(int $claimId, array $publisher, array $user, string $role, string $email, string $message, bool $domainMatch): void
    {
        $who = User::displayName($user);
        $link = '/admin/maisons-edition';
        try {
            $admins = User::activeAdmins();
        } catch (\Throwable) {
            return;
        }
        foreach ($admins as $admin) {
            $adminId = (int) ($admin['id'] ?? 0);
            if ($adminId < 1) {
                continue;
            }
            try {
                Notification::upsertUnread(
                    $adminId,
                    'Une maison d\'édition souhaite gérer sa fiche',
                    $who . ' demande à prendre la main sur « ' . $publisher['name'] . ' ».',
                    $link,
                    'publisher_claim',
                    'publisher_claim',
                    $claimId
                );
            } catch (\Throwable) {
            }
            Mailer::notify($admin, 'transactional', 'maison-revendication-admin', [
                'maison' => (string) $publisher['name'],
                'demandeur' => $who,
                'email_compte' => (string) ($user['email'] ?? ''),
                'email_pro' => $email,
                'fonction' => $role,
                'domaine' => $domainMatch ? 'Le domaine de l\'e-mail correspond au site de la maison.' : 'Le domaine de l\'e-mail ne correspond pas au site connu : vérification recommandée.',
                'message' => $message,
                'lien_admin' => Share::absolute($link),
                'lien_fiche' => Share::absolute((string) $publisher['href']),
            ]);
        }
    }

    /**
     * Décision administrateur : attribue la fiche ou refuse la demande, et prévient le demandeur.
     */
    public static function decide(int $id, string $status, string $note, int $adminId): array
    {
        $claim = self::find($id);
        if (!$claim) {
            throw new RuntimeException('Demande introuvable.');
        }
        if ($claim['status'] !== self::STATUS_PENDING) {
            throw new RuntimeException('Cette demande a déjà été traitée.');
        }
        if (!in_array($status, [self::STATUS_APPROVED, self::STATUS_REFUSED], true)) {
            throw new RuntimeException('Décision inconnue.');
        }
        $note = trim($note);
        if ($status === self::STATUS_REFUSED && $note === '') {
            throw new RuntimeException('Indiquez un motif de refus : il sera transmis au demandeur.');
        }

        Database::transaction(static function () use ($claim, $status, $note, $adminId): void {
            Database::query(
                'UPDATE publisher_claims SET status = ?, admin_note = ?, decided_by = ?, decided_at = NOW() WHERE id = ?',
                [$status, $note !== '' ? $note : null, $adminId, (int) $claim['id']]
            );
            if ($status === self::STATUS_APPROVED) {
                Publisher::assignOwner((int) $claim['publisher_id'], (int) $claim['user_id']);
                Database::query(
                    'UPDATE publisher_claims SET status = "refused", admin_note = ?, decided_by = ?, decided_at = NOW()
                     WHERE publisher_id = ? AND status = "pending" AND id != ?',
                    ['Une autre demande a été validée pour cette maison.', $adminId, (int) $claim['publisher_id'], (int) $claim['id']]
                );
            }
        });

        try {
            foreach (User::activeAdmins() as $admin) {
                Notification::markSubjectRead((int) $admin['id'], 'publisher_claim', 'publisher_claim', $id);
            }
        } catch (\Throwable) {
        }

        $user = User::find((int) $claim['user_id']);
        $approved = $status === self::STATUS_APPROVED;
        $link = $approved ? '/espace/maison-edition' : '/maisons-edition/' . $claim['publisher_slug'];
        try {
            Notification::create(
                (int) $claim['user_id'],
                $approved ? 'Votre fiche maison d\'édition est à vous' : 'Votre demande n\'a pas été retenue',
                $approved
                    ? 'Vous gérez désormais la fiche « ' . $claim['publisher_name'] . ' ». Complétez-la depuis votre espace.'
                    : 'La demande pour « ' . $claim['publisher_name'] . ' » a été refusée. Motif : ' . $note,
                $link,
                $approved ? 'publisher_claim_approved' : 'publisher_claim_refused',
                'publisher',
                (int) $claim['publisher_id']
            );
        } catch (\Throwable) {
        }
        Mailer::notify($user, 'transactional', $approved ? 'maison-revendication-validee' : 'maison-revendication-refusee', [
            'maison' => (string) $claim['publisher_name'],
            'motif' => $note,
            'lien_espace' => Share::absolute('/espace/maison-edition'),
            'lien_fiche' => Share::absolute('/maisons-edition/' . $claim['publisher_slug']),
        ]);

        return $claim;
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): array
    {
        $row['status_label'] = self::statusLabel((string) ($row['status'] ?? 'pending'));
        $row['who'] = trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) ?: (string) ($row['user_email'] ?? 'Membre');
        $row['publisher_href'] = '/maisons-edition/' . ($row['publisher_slug'] ?? '');
        $row['when'] = admin_date((string) ($row['created_at'] ?? ''));
        $row['decided_label'] = !empty($row['decided_at']) ? admin_date((string) $row['decided_at']) : '';
        $row['domain_match'] = (int) ($row['domain_match'] ?? 0) === 1;
        $row['already_owned'] = !empty($row['owner_user_id']) && (int) $row['owner_user_id'] !== (int) ($row['user_id'] ?? 0);
        return $row;
    }
}
