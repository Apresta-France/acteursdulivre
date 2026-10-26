<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Core\Mailer;
use Adl\Data\Share;
use RuntimeException;

final class SalonClaim
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
            'SELECT c.*, s.name AS salon_name, s.slug AS salon_slug, s.website AS salon_website, s.owner_user_id,
                    u.email AS user_email, u.first_name, u.last_name
             FROM salon_claims c
             JOIN salons s ON s.id = c.salon_id
             JOIN users u ON u.id = c.user_id
             WHERE c.id = ?',
            [$id]
        );
        return $row ? self::hydrate($row) : null;
    }

    public static function pendingFor(int $salonId, int $userId): ?array
    {
        $row = Database::fetch(
            'SELECT * FROM salon_claims WHERE salon_id = ? AND user_id = ? AND status = "pending" LIMIT 1',
            [$salonId, $userId]
        );
        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT c.*, s.name AS salon_name, s.slug AS salon_slug, s.owner_user_id
             FROM salon_claims c JOIN salons s ON s.id = c.salon_id
             WHERE c.user_id = ? ORDER BY c.created_at DESC',
            [$userId]
        );
        return array_map([self::class, 'hydrate'], $rows);
    }

    public static function countPending(): int
    {
        return (int) (Database::fetch('SELECT COUNT(*) AS n FROM salon_claims WHERE status = "pending"')['n'] ?? 0);
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
            'SELECT c.*, s.name AS salon_name, s.slug AS salon_slug, s.website AS salon_website, s.owner_user_id,
                    s.city AS salon_city, s.starts_on AS salon_starts_on, s.ends_on AS salon_ends_on,
                    u.email AS user_email, u.first_name, u.last_name, u.avatar_url, u.created_at AS user_since
             FROM salon_claims c
             JOIN salons s ON s.id = c.salon_id
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
    public static function create(array $salon, array $user, array $data): int
    {
        $userId = (int) $user['id'];
        $salonId = (int) $salon['id'];

        if (!empty($salon['owner_user_id'])) {
            if ((int) $salon['owner_user_id'] === $userId) {
                throw new RuntimeException('Vous gérez déjà cette fiche.');
            }
            throw new RuntimeException('Cette fiche a déjà été revendiquée. Si vous pensez qu\'il s\'agit d\'une erreur, écrivez-nous via la page contact.');
        }
        if (self::pendingFor($salonId, $userId)) {
            throw new RuntimeException('Votre demande pour cette fiche est déjà en cours d\'examen.');
        }
        self::assertDailyLimit($userId);
        [$role, $email, $phone, $message] = self::validateApplicant($data);

        $domainMatch = self::domainMatches($email, (string) ($salon['website'] ?? ''));
        Database::query(
            'INSERT INTO salon_claims (salon_id, user_id, role_title, company_email, phone, message, domain_match, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, "pending", NOW())',
            [$salonId, $userId, mb_substr($role, 0, 120), mb_substr($email, 0, 190), mb_substr($phone, 0, 40), $message, $domainMatch ? 1 : 0]
        );
        $id = (int) Database::lastId();

        self::notifyAdmins($id, $salon, $user, $role, $email, $message, $domainMatch);
        Mailer::notify($user, 'transactional', 'salon-revendication-recue', [
            'salon' => (string) $salon['name'],
            'lien_fiche' => Share::absolute((string) $salon['href']),
        ]);

        return $id;
    }

    private static function assertDailyLimit(int $userId): void
    {
        $today = (int) (Database::fetch(
            'SELECT COUNT(*) AS n FROM salon_claims WHERE user_id = ? AND created_at > (NOW() - INTERVAL 1 DAY)',
            [$userId]
        )['n'] ?? 0);
        if ($today >= self::DAILY_LIMIT) {
            throw new RuntimeException('Vous avez déjà envoyé plusieurs demandes aujourd\'hui. Réessayez demain.');
        }
    }

    /**
     * @param array{role_title: string, company_email: string, message: string, phone?: string} $data
     * @return array{0: string, 1: string, 2: string, 3: string} rôle, e-mail, téléphone, message
     */
    private static function validateApplicant(array $data): array
    {
        $role = trim($data['role_title']);
        $email = strtolower(trim($data['company_email']));
        $message = trim($data['message']);
        $phone = trim((string) ($data['phone'] ?? ''));
        if (mb_strlen($role) < 2) {
            throw new RuntimeException('Indiquez votre fonction auprès de la manifestation.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Indiquez une adresse e-mail professionnelle valide.');
        }
        if (mb_strlen($message) < 20) {
            throw new RuntimeException('Décrivez en quelques lignes votre lien avec la manifestation (20 caractères minimum).');
        }
        if (mb_strlen($message) > 2000) {
            throw new RuntimeException('Le message est limité à 2 000 caractères.');
        }
        return [$role, $email, $phone, $message];
    }

    public static function domainMatches(string $email, string $website): bool
    {
        $domain = strtolower((string) substr($email, (int) strrpos($email, '@') + 1));
        if ($domain === '') {
            return false;
        }
        $host = Publisher::websiteHost($website);
        if ($host === '') {
            return false;
        }
        $candidate = strtolower($host);
        return $candidate === $domain
            || str_ends_with($domain, '.' . $candidate)
            || str_ends_with($candidate, '.' . $domain);
    }

    private static function notifyAdmins(int $claimId, array $salon, array $user, string $role, string $email, string $message, bool $domainMatch): void
    {
        $who = User::displayName($user);
        $link = '/admin/salons?onglet=revendications';
        try {
            $admins = User::activeAdmins();
        } catch (\Throwable) {
            return;
        }
        $vars = [
            'salon' => (string) $salon['name'],
            'demandeur' => $who,
            'email_compte' => (string) ($user['email'] ?? ''),
            'email_pro' => $email,
            'fonction' => $role,
            'domaine' => $domainMatch
                ? 'Le domaine de l\'e-mail correspond au site de la manifestation.'
                : (($salon['website'] ?? '') === ''
                    ? 'Aucun site web déclaré : impossible de recouper le domaine de l\'e-mail.'
                    : 'Le domaine de l\'e-mail ne correspond pas au site connu : vérification recommandée.'),
            'message' => $message,
            'lien_admin' => Share::absolute($link),
            'lien_fiche' => Share::absolute((string) $salon['href']),
        ];
        foreach ($admins as $admin) {
            $adminId = (int) ($admin['id'] ?? 0);
            if ($adminId < 1) {
                continue;
            }
            try {
                Notification::upsertUnread(
                    $adminId,
                    'Un salon souhaite gérer sa fiche',
                    $who . ' demande à prendre la main sur « ' . $salon['name'] . ' ».',
                    $link,
                    'salon_claim',
                    'salon_claim',
                    $claimId
                );
            } catch (\Throwable) {
            }
            Mailer::notify($admin, 'transactional', 'salon-revendication-admin', $vars);
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
                'UPDATE salon_claims SET status = ?, admin_note = ?, decided_by = ?, decided_at = NOW() WHERE id = ?',
                [$status, $note !== '' ? $note : null, $adminId, (int) $claim['id']]
            );
            if ($status === self::STATUS_APPROVED) {
                $salonId = (int) $claim['salon_id'];
                Salon::assignOwner($salonId, (int) $claim['user_id']);
                Database::query(
                    'UPDATE salon_claims SET status = "refused", admin_note = ?, decided_by = ?, decided_at = NOW()
                     WHERE salon_id = ? AND status = "pending" AND id != ?',
                    ['Une autre demande a été validée pour cette fiche.', $adminId, $salonId, (int) $claim['id']]
                );
            }
        });

        try {
            foreach (User::activeAdmins() as $admin) {
                Notification::markSubjectRead((int) $admin['id'], 'salon_claim', 'salon_claim', $id);
            }
        } catch (\Throwable) {
        }

        $user = User::find((int) $claim['user_id']);
        $approved = $status === self::STATUS_APPROVED;
        $fiche = '/salons/' . $claim['salon_slug'];
        try {
            Notification::create(
                (int) $claim['user_id'],
                $approved ? 'Votre fiche salon est à vous' : 'Votre demande n\'a pas été retenue',
                $approved
                    ? 'Vous gérez désormais la fiche « ' . $claim['salon_name'] . ' ». Complétez-la depuis votre espace.'
                    : 'La demande pour « ' . $claim['salon_name'] . ' » a été refusée. Motif : ' . $note,
                $approved ? '/espace/salon' : $fiche,
                $approved ? 'salon_claim_approved' : 'salon_claim_refused',
                'salon',
                (int) $claim['salon_id']
            );
        } catch (\Throwable) {
        }
        Mailer::notify($user, 'transactional', $approved ? 'salon-revendication-validee' : 'salon-revendication-refusee', [
            'salon' => (string) $claim['salon_name'],
            'motif' => $note,
            'lien_espace' => Share::absolute('/espace/salon'),
            'lien_fiche' => Share::absolute($fiche),
        ]);

        return $claim;
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): array
    {
        $row['status_label'] = self::statusLabel((string) ($row['status'] ?? 'pending'));
        $row['who'] = trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) ?: (string) ($row['user_email'] ?? 'Membre');
        $row['salon_href'] = '/salons/' . ($row['salon_slug'] ?? '');
        $row['when'] = admin_date((string) ($row['created_at'] ?? ''));
        $row['decided_label'] = !empty($row['decided_at']) ? admin_date((string) $row['decided_at']) : '';
        $row['domain_match'] = (int) ($row['domain_match'] ?? 0) === 1;
        $row['already_owned'] = !empty($row['owner_user_id']) && (int) $row['owner_user_id'] !== (int) ($row['user_id'] ?? 0);
        return $row;
    }
}
