<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Core\Mailer;
use Adl\Data\Share;
use RuntimeException;

final class SalonProposal
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REFUSED = 'refused';

    public const DAILY_LIMIT = 3;

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_APPROVED => 'Publiée',
            self::STATUS_REFUSED => 'Refusée',
            default => 'En attente',
        };
    }

    public static function tableExists(): bool
    {
        try {
            Database::fetch('SELECT 1 FROM salon_proposals LIMIT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function find(int $id): ?array
    {
        $row = Database::fetch(
            'SELECT p.*, u.first_name, u.last_name, u.email AS user_email, u.avatar_url
             FROM salon_proposals p
             LEFT JOIN users u ON u.id = p.user_id
             WHERE p.id = ?',
            [$id]
        );
        return $row ? self::hydrate($row) : null;
    }

    public static function countPending(): int
    {
        return (int) (Database::fetch('SELECT COUNT(*) AS n FROM salon_proposals WHERE status = "pending"')['n'] ?? 0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function forAdmin(string $filter = 'pending'): array
    {
        $where = match ($filter) {
            'approved' => 'p.status = "approved"',
            'refused' => 'p.status = "refused"',
            'tous' => '1=1',
            default => 'p.status = "pending"',
        };
        $rows = Database::fetchAll(
            'SELECT p.*, u.first_name, u.last_name, u.email AS user_email, u.avatar_url, u.created_at AS user_since
             FROM salon_proposals p
             LEFT JOIN users u ON u.id = p.user_id
             WHERE ' . $where . '
             ORDER BY (p.status = "pending") DESC, p.created_at DESC
             LIMIT 200'
        );
        return array_map([self::class, 'hydrate'], $rows);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data, ?array $user = null): int
    {
        $fields = self::validate($data);
        $email = $fields['contact_email'];
        $userId = $user ? (int) $user['id'] : null;

        self::assertDailyLimit($email, $userId);

        if (self::pendingDuplicate($fields['name'], $fields['city'], $fields['starts_on'])) {
            throw new RuntimeException('Une proposition identique est déjà en cours d\'examen.');
        }

        $existing = Salon::sameListing($fields['name'], $fields['city'], $fields['starts_on']);
        if ($existing) {
            throw new RuntimeException('Ce salon figure déjà dans l\'agenda : ' . (string) $existing['name'] . '. Consultez sa fiche plutôt que d\'en créer une seconde.');
        }

        Database::query(
            'INSERT INTO salon_proposals (
                user_id, contact_name, contact_email, name, category,
                starts_on, ends_on, city, region, country, venue, website,
                organizer, ticket, audience, description, notes, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", NOW())',
            [
                $userId,
                $fields['contact_name'],
                $email,
                $fields['name'],
                $fields['category'],
                $fields['starts_on'],
                $fields['ends_on'],
                $fields['city'],
                $fields['region'],
                $fields['country'],
                $fields['venue'],
                $fields['website'],
                $fields['organizer'],
                $fields['ticket'],
                $fields['audience'],
                $fields['description'] !== '' ? $fields['description'] : null,
                $fields['notes'] !== '' ? $fields['notes'] : null,
            ]
        );
        $id = (int) Database::lastId();

        $similar = Salon::similar($fields['name'], $fields['city'], 4);
        self::notifyAdmins($id, $fields, $user, $similar);
        self::mailProposer($user, $email, $fields['contact_name'], 'salon-proposition-recue', [
            'salon' => $fields['name'],
            'lien_agenda' => Share::absolute('/salons'),
        ]);
        if ($userId) {
            try {
                Notification::create(
                    $userId,
                    'Votre salon est en cours de validation',
                    '« ' . $fields['name'] . ' » sera publié dans l\'agenda après vérification.',
                    '/salons',
                    'salon_proposal',
                    'salon_proposal',
                    $id
                );
            } catch (\Throwable) {
            }
        }

        return $id;
    }

    /**
     * @param array<string, mixed> $overrides champs corrigés par l'admin avant publication
     */
    public static function decide(int $id, string $status, string $note, int $adminId, array $overrides = []): array
    {
        $proposal = self::find($id);
        if (!$proposal) {
            throw new RuntimeException('Proposition introuvable.');
        }
        if ($proposal['status'] !== self::STATUS_PENDING) {
            throw new RuntimeException('Cette proposition a déjà été traitée.');
        }
        if (!in_array($status, [self::STATUS_APPROVED, self::STATUS_REFUSED], true)) {
            throw new RuntimeException('Décision inconnue.');
        }
        $note = trim($note);
        if ($status === self::STATUS_REFUSED && $note === '') {
            throw new RuntimeException('Indiquez un motif de refus : il sera transmis au demandeur.');
        }

        $salon = null;
        Database::transaction(static function () use ($proposal, $status, $note, $adminId, $overrides, &$salon): void {
            $locked = Database::fetch('SELECT * FROM salon_proposals WHERE id = ? FOR UPDATE', [(int) $proposal['id']]);
            if (!$locked || ($locked['status'] ?? '') !== self::STATUS_PENDING) {
                throw new RuntimeException('Cette proposition a déjà été traitée.');
            }
            $salonId = null;
            if ($status === self::STATUS_APPROVED) {
                $payload = self::mergeOverrides($proposal, $overrides);
                $salon = Salon::createFromProposal($payload);
                $salonId = (int) $salon['id'];
            }
            Database::query(
                'UPDATE salon_proposals SET status = ?, admin_note = ?, salon_id = ?, decided_by = ?, decided_at = NOW() WHERE id = ? AND status = "pending"',
                [$status, $note !== '' ? $note : null, $salonId, $adminId, (int) $proposal['id']]
            );
        });

        try {
            foreach (User::activeAdmins() as $admin) {
                Notification::markSubjectRead((int) $admin['id'], 'salon_proposal', 'salon_proposal', $id);
            }
        } catch (\Throwable) {
        }

        $approved = $status === self::STATUS_APPROVED;
        $fiche = $salon ? (string) $salon['href'] : '/salons';
        $userId = (int) ($proposal['user_id'] ?? 0);
        if ($userId > 0) {
            try {
                Notification::markSubjectRead($userId, 'salon_proposal', 'salon_proposal', $id);
            } catch (\Throwable) {
            }
            try {
                Notification::create(
                    $userId,
                    $approved ? 'Votre salon est dans l\'agenda' : 'Votre proposition de salon n\'a pas été retenue',
                    $approved
                        ? '« ' . ($salon['name'] ?? $proposal['name']) . ' » est désormais visible dans l\'agenda.'
                        : 'La proposition pour « ' . $proposal['name'] . ' » a été refusée. Motif : ' . $note,
                    $fiche,
                    $approved ? 'salon_proposal_approved' : 'salon_proposal_refused',
                    'salon',
                    $salon ? (int) $salon['id'] : 0
                );
            } catch (\Throwable) {
            }
        }

        $name = (string) ($salon['name'] ?? $proposal['name']);
        self::mailProposer(
            $userId > 0 ? User::find($userId) : null,
            (string) $proposal['contact_email'],
            (string) $proposal['contact_name'],
            $approved ? 'salon-proposition-validee' : 'salon-proposition-refusee',
            [
                'salon' => $name,
                'motif' => $note,
                'lien_fiche' => Share::absolute($fiche),
                'lien_agenda' => Share::absolute('/salons'),
            ]
        );

        $proposal['status'] = $status;
        $proposal['salon'] = $salon;
        return $proposal;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string|null>
     */
    public static function validate(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $city = trim((string) ($data['city'] ?? ''));
        $country = trim((string) ($data['country'] ?? 'France')) ?: 'France';
        $email = strtolower(trim((string) ($data['contact_email'] ?? '')));
        $contactName = trim((string) ($data['contact_name'] ?? ''));
        $start = trim((string) ($data['starts_on'] ?? ''));
        $end = trim((string) ($data['ends_on'] ?? ''));
        $website = self::normalizeWebsite((string) ($data['website'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));

        if (mb_strlen($name) < 2) {
            throw new RuntimeException('Indiquez le nom du salon.');
        }
        if (mb_strlen($city) < 2) {
            throw new RuntimeException('Indiquez la ville.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Indiquez un e-mail valide pour vous recontacter.');
        }
        if ($contactName === '') {
            throw new RuntimeException('Indiquez votre nom.');
        }
        if ($start === '' || !self::isValidDate($start)) {
            throw new RuntimeException('Indiquez une date de début.');
        }
        if ($end !== '' && !self::isValidDate($end)) {
            throw new RuntimeException('La date de fin n\'est pas valide.');
        }
        if ($end !== '' && $end < $start) {
            throw new RuntimeException('La date de fin doit être postérieure au début.');
        }
        if (mb_strlen($description) > 2000) {
            throw new RuntimeException('La présentation est trop longue (2 000 caractères maximum).');
        }
        if (mb_strlen($notes) > 2000) {
            throw new RuntimeException('Le message est trop long (2 000 caractères maximum).');
        }

        return [
            'name' => mb_substr($name, 0, 255),
            'category' => mb_substr(trim((string) ($data['category'] ?? '')), 0, 120),
            'starts_on' => $start,
            'ends_on' => $end !== '' ? $end : null,
            'city' => mb_substr($city, 0, 120),
            'region' => mb_substr(trim((string) ($data['region'] ?? '')), 0, 120),
            'country' => mb_substr($country, 0, 120),
            'venue' => mb_substr(trim((string) ($data['venue'] ?? '')), 0, 255),
            'website' => $website,
            'organizer' => mb_substr(trim((string) ($data['organizer'] ?? '')), 0, 255),
            'ticket' => mb_substr(trim((string) ($data['ticket'] ?? '')), 0, 255),
            'audience' => mb_substr(trim((string) ($data['audience'] ?? '')), 0, 190),
            'description' => $description,
            'notes' => $notes,
            'contact_name' => mb_substr($contactName, 0, 190),
            'contact_email' => mb_substr($email, 0, 190),
            'dates_confirmed' => !empty($data['dates_confirmed']) ? '1' : '',
        ];
    }

    public static function normalizeWebsite(string $raw, bool $strict = true): string
    {
        $url = trim($raw);
        if ($url === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }
        $host = parse_url($url, PHP_URL_HOST);
        $ok = is_string($host) && str_contains($host, '.') && filter_var($url, FILTER_VALIDATE_URL);
        if (!$ok) {
            if ($strict) {
                throw new RuntimeException('Le site web n\'est pas une adresse valide.');
            }
            return '';
        }
        return mb_substr(rtrim($url, '/'), 0, 255);
    }

    private static function isValidDate(string $raw): bool
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m) !== 1) {
            return false;
        }
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }

    /**
     * @param array<string, mixed> $proposal
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private static function mergeOverrides(array $proposal, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if ($key === 'dates_confirmed') {
                $proposal[$key] = $value;
                continue;
            }
            if (is_string($value) && trim($value) !== '') {
                $proposal[$key] = $value;
            }
        }
        return $proposal;
    }

    private static function assertDailyLimit(string $email, ?int $userId): void
    {
        $n = (int) (Database::fetch(
            'SELECT COUNT(*) AS n FROM salon_proposals
             WHERE created_at > (NOW() - INTERVAL 1 DAY)
               AND (contact_email = ?' . ($userId ? ' OR user_id = ?' : '') . ')',
            $userId ? [$email, $userId] : [$email]
        )['n'] ?? 0);
        if ($n >= self::DAILY_LIMIT) {
            throw new RuntimeException('Vous avez déjà proposé plusieurs salons aujourd\'hui. Réessayez demain.');
        }
    }

    private static function pendingDuplicate(string $name, string $city, ?string $start): bool
    {
        $nameKey = Salon::nameKey($name);
        $cityKey = Salon::nameKey($city);
        $rows = Database::fetchAll(
            'SELECT name, city, starts_on FROM salon_proposals WHERE status = "pending" LIMIT 200'
        );
        foreach ($rows as $row) {
            if (Salon::nameKey((string) ($row['name'] ?? '')) !== $nameKey) {
                continue;
            }
            if (Salon::nameKey((string) ($row['city'] ?? '')) !== $cityKey) {
                continue;
            }
            if ((string) ($row['starts_on'] ?? '') === (string) $start) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<string, mixed> $fields
     * @param list<array<string, mixed>> $similar
     */
    private static function notifyAdmins(int $id, array $fields, ?array $user, array $similar): void
    {
        $who = $user ? User::displayName($user) : (string) $fields['contact_name'];
        $link = '/admin/salons';
        $lieu = trim($fields['city'] . ($fields['country'] !== '' && $fields['country'] !== 'France' ? ' · ' . $fields['country'] : ''));
        $dates = (string) $fields['starts_on'];
        if (!empty($fields['ends_on']) && $fields['ends_on'] !== $fields['starts_on']) {
            $dates .= ' – ' . $fields['ends_on'];
        }
        $vars = [
            'demandeur' => $who,
            'email' => (string) $fields['contact_email'],
            'salon' => (string) $fields['name'],
            'lieu' => $lieu !== '' ? $lieu : 'lieu non précisé',
            'dates' => $dates,
            'doublons' => $similar === []
                ? 'Aucun salon au nom proche dans l\'agenda.'
                : 'Salon proche déjà recensé : ' . implode(', ', array_map(static fn (array $s): string => (string) $s['name'], $similar)) . '.',
            'message' => (string) ($fields['notes'] !== '' ? $fields['notes'] : 'Aucun message complémentaire.'),
            'lien_admin' => Share::absolute($link),
        ];
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
                    'Un salon a été proposé',
                    $who . ' propose d\'ajouter « ' . $fields['name'] . ' » à l\'agenda.',
                    $link,
                    'salon_proposal',
                    'salon_proposal',
                    $id
                );
            } catch (\Throwable) {
            }
            Mailer::notify($admin, 'transactional', 'salon-proposition-admin', $vars);
        }
    }

    /** @param array<string, string> $vars */
    private static function mailProposer(?array $user, string $email, string $name, string $slug, array $vars): void
    {
        $vars['prenom'] = $user
            ? (string) ($user['first_name'] ?? '')
            : trim(explode(' ', $name)[0] ?? $name);
        try {
            Mailer::sendTemplate($slug, $email, $vars);
        } catch (\Throwable) {
        }
        $account = strtolower(trim((string) ($user['email'] ?? '')));
        if ($user && $account !== '' && $account !== strtolower($email)) {
            Mailer::notify($user, 'transactional', $slug, $vars);
        }
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): array
    {
        $row['status_label'] = self::statusLabel((string) ($row['status'] ?? 'pending'));
        $row['who'] = trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')))
            ?: ((string) ($row['contact_name'] ?? '') !== '' ? (string) $row['contact_name'] : (string) ($row['contact_email'] ?? 'Visiteur'));
        $row['when'] = admin_date((string) ($row['created_at'] ?? ''));
        $row['decided_label'] = !empty($row['decided_at']) ? admin_date((string) $row['decided_at']) : '';
        $city = (string) ($row['city'] ?? '');
        $country = (string) ($row['country'] ?? '');
        $row['place'] = $country !== '' && $country !== 'France'
            ? trim($city . ($city !== '' ? ' · ' : '') . $country)
            : $city;
        $start = (string) ($row['starts_on'] ?? '');
        $end = (string) ($row['ends_on'] ?? '');
        $row['dates'] = $start;
        if ($end !== '' && $end !== $start) {
            $row['dates'] .= ' – ' . $end;
        }
        return $row;
    }
}
