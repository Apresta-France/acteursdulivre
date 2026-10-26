<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Core\Mailer;
use Adl\Data\Share;
use RuntimeException;

final class ContestProposal
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
            Database::fetch('SELECT 1 FROM contest_proposals LIMIT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function find(int $id): ?array
    {
        $row = Database::fetch(
            'SELECT p.*, u.first_name, u.last_name, u.email AS user_email, u.avatar_url
             FROM contest_proposals p
             LEFT JOIN users u ON u.id = p.user_id
             WHERE p.id = ?',
            [$id]
        );
        return $row ? self::hydrate($row) : null;
    }

    public static function countPending(): int
    {
        return (int) (Database::fetch('SELECT COUNT(*) AS n FROM contest_proposals WHERE status = "pending"')['n'] ?? 0);
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
             FROM contest_proposals p
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

        if (self::pendingDuplicate($fields['name'], $fields['deadline_on'])) {
            throw new RuntimeException('Une proposition identique est déjà en cours d\'examen.');
        }

        $existing = Contest::sameListing($fields['name'], $fields['city'], $fields['deadline_on']);
        if ($existing) {
            throw new RuntimeException('Cette récompense figure déjà dans l\'annuaire : ' . (string) $existing['name'] . '. Consultez sa fiche plutôt que d\'en créer une seconde.');
        }

        Database::query(
            'INSERT INTO contest_proposals (
                user_id, contact_name, contact_email, name, kind, category,
                opens_on, deadline_on, awarded_on, prize, entry_fee, eligibility,
                city, region, country, venue, website, organizer,
                description, notes, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", NOW())',
            [
                $userId,
                $fields['contact_name'],
                $email,
                $fields['name'],
                $fields['kind'],
                $fields['category'],
                $fields['opens_on'],
                $fields['deadline_on'],
                $fields['awarded_on'],
                $fields['prize'],
                $fields['entry_fee'],
                $fields['eligibility'],
                $fields['city'],
                $fields['region'],
                $fields['country'],
                $fields['venue'],
                $fields['website'],
                $fields['organizer'],
                $fields['description'] !== '' ? $fields['description'] : null,
                $fields['notes'] !== '' ? $fields['notes'] : null,
            ]
        );
        $id = (int) Database::lastId();

        $similar = Contest::similar($fields['name'], $fields['city'], 4);
        self::notifyAdmins($id, $fields, $user, $similar);
        self::mailProposer($user, $email, $fields['contact_name'], 'prix-proposition-recue', [
            'prix' => $fields['name'],
            'lien_agenda' => Share::absolute('/prix-et-concours'),
        ]);
        if ($userId) {
            try {
                Notification::create(
                    $userId,
                    'Votre proposition est en cours de validation',
                    '« ' . $fields['name'] . ' » sera publiée dans l\'annuaire après vérification.',
                    '/prix-et-concours',
                    'contest_proposal',
                    'contest_proposal',
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

        $contest = null;
        Database::transaction(static function () use ($proposal, $status, $note, $adminId, $overrides, &$contest): void {
            $locked = Database::fetch('SELECT * FROM contest_proposals WHERE id = ? FOR UPDATE', [(int) $proposal['id']]);
            if (!$locked || ($locked['status'] ?? '') !== self::STATUS_PENDING) {
                throw new RuntimeException('Cette proposition a déjà été traitée.');
            }
            $contestId = null;
            if ($status === self::STATUS_APPROVED) {
                $payload = self::mergeOverrides($proposal, $overrides);
                $contest = Contest::createFromProposal($payload);
                $contestId = (int) $contest['id'];
            }
            Database::query(
                'UPDATE contest_proposals SET status = ?, admin_note = ?, contest_id = ?, decided_by = ?, decided_at = NOW() WHERE id = ? AND status = "pending"',
                [$status, $note !== '' ? $note : null, $contestId, $adminId, (int) $proposal['id']]
            );
        });

        try {
            foreach (User::activeAdmins() as $admin) {
                Notification::markSubjectRead((int) $admin['id'], 'contest_proposal', 'contest_proposal', $id);
            }
        } catch (\Throwable) {
        }

        $approved = $status === self::STATUS_APPROVED;
        $fiche = $contest ? (string) $contest['href'] : '/prix-et-concours';
        $userId = (int) ($proposal['user_id'] ?? 0);
        if ($userId > 0) {
            try {
                Notification::markSubjectRead($userId, 'contest_proposal', 'contest_proposal', $id);
            } catch (\Throwable) {
            }
            try {
                Notification::create(
                    $userId,
                    $approved ? 'Votre prix est dans l\'annuaire' : 'Votre proposition n\'a pas été retenue',
                    $approved
                        ? '« ' . ($contest['name'] ?? $proposal['name']) . ' » est désormais visible dans l\'annuaire des prix et concours.'
                        : 'La proposition pour « ' . $proposal['name'] . ' » a été refusée. Motif : ' . $note,
                    $fiche,
                    $approved ? 'contest_proposal_approved' : 'contest_proposal_refused',
                    'contest',
                    $contest ? (int) $contest['id'] : 0
                );
            } catch (\Throwable) {
            }
        }

        $name = (string) ($contest['name'] ?? $proposal['name']);
        self::mailProposer(
            $userId > 0 ? User::find($userId) : null,
            (string) $proposal['contact_email'],
            (string) $proposal['contact_name'],
            $approved ? 'prix-proposition-validee' : 'prix-proposition-refusee',
            [
                'prix' => $name,
                'motif' => $note,
                'lien_fiche' => Share::absolute($fiche),
                'lien_agenda' => Share::absolute('/prix-et-concours'),
            ]
        );

        $proposal['status'] = $status;
        $proposal['contest'] = $contest;
        return $proposal;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string|null>
     */
    public static function validate(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $country = trim((string) ($data['country'] ?? 'France')) ?: 'France';
        $email = strtolower(trim((string) ($data['contact_email'] ?? '')));
        $contactName = trim((string) ($data['contact_name'] ?? ''));
        $opens = trim((string) ($data['opens_on'] ?? ''));
        $deadline = trim((string) ($data['deadline_on'] ?? ''));
        $awarded = trim((string) ($data['awarded_on'] ?? ''));
        $website = self::normalizeWebsite((string) ($data['website'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));
        $kind = slugify((string) ($data['kind'] ?? 'prix'));
        if (!isset(Contest::KINDS[$kind])) {
            $kind = 'prix';
        }

        if (mb_strlen($name) < 2) {
            throw new RuntimeException('Indiquez le nom du prix ou du concours.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Indiquez un e-mail valide pour vous recontacter.');
        }
        if ($contactName === '') {
            throw new RuntimeException('Indiquez votre nom.');
        }
        if ($opens !== '' && !self::isValidDate($opens)) {
            throw new RuntimeException('La date d\'ouverture n\'est pas valide.');
        }
        if ($deadline !== '' && !self::isValidDate($deadline)) {
            throw new RuntimeException('La date limite n\'est pas valide.');
        }
        if ($awarded !== '' && !self::isValidDate($awarded)) {
            throw new RuntimeException('La date de proclamation n\'est pas valide.');
        }
        if ($deadline === '' && $awarded === '') {
            throw new RuntimeException('Indiquez au moins la date limite de candidature ou la date de proclamation.');
        }
        if ($opens !== '' && $deadline !== '' && $deadline < $opens) {
            throw new RuntimeException('La date limite doit être postérieure à l\'ouverture.');
        }
        if (mb_strlen($description) > 2000) {
            throw new RuntimeException('La présentation est trop longue (2 000 caractères maximum).');
        }
        if (mb_strlen($notes) > 2000) {
            throw new RuntimeException('Le message est trop long (2 000 caractères maximum).');
        }

        return [
            'name' => mb_substr($name, 0, 255),
            'kind' => $kind,
            'category' => mb_substr(trim((string) ($data['category'] ?? '')), 0, 120),
            'opens_on' => $opens !== '' ? $opens : null,
            'deadline_on' => $deadline !== '' ? $deadline : null,
            'awarded_on' => $awarded !== '' ? $awarded : null,
            'prize' => mb_substr(trim((string) ($data['prize'] ?? '')), 0, 255),
            'entry_fee' => mb_substr(trim((string) ($data['entry_fee'] ?? '')), 0, 255),
            'eligibility' => mb_substr(trim((string) ($data['eligibility'] ?? '')), 0, 255),
            'city' => mb_substr(trim((string) ($data['city'] ?? '')), 0, 120),
            'region' => mb_substr(trim((string) ($data['region'] ?? '')), 0, 120),
            'country' => mb_substr($country, 0, 120),
            'venue' => mb_substr(trim((string) ($data['venue'] ?? '')), 0, 255),
            'website' => $website,
            'organizer' => mb_substr(trim((string) ($data['organizer'] ?? '')), 0, 255),
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
        $clean = public_http_url($url);
        if ($clean === '') {
            if ($strict) {
                throw new RuntimeException('Le site web n\'est pas une adresse valide.');
            }
            return '';
        }
        return mb_substr(rtrim($clean, '/'), 0, 255);
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
            'SELECT COUNT(*) AS n FROM contest_proposals
             WHERE created_at > (NOW() - INTERVAL 1 DAY)
               AND (contact_email = ?' . ($userId ? ' OR user_id = ?' : '') . ')',
            $userId ? [$email, $userId] : [$email]
        )['n'] ?? 0);
        if ($n >= self::DAILY_LIMIT) {
            throw new RuntimeException('Vous avez déjà proposé plusieurs récompenses aujourd\'hui. Réessayez demain.');
        }
    }

    private static function pendingDuplicate(string $name, ?string $deadline): bool
    {
        $nameKey = Contest::nameKey($name);
        $rows = Database::fetchAll(
            'SELECT name, deadline_on FROM contest_proposals WHERE status = "pending" LIMIT 200'
        );
        foreach ($rows as $row) {
            if (Contest::nameKey((string) ($row['name'] ?? '')) !== $nameKey) {
                continue;
            }
            if ((string) ($row['deadline_on'] ?? '') === (string) $deadline) {
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
        $link = '/admin/prix';
        $lieu = trim(($fields['city'] ?? '') . ($fields['country'] !== '' && $fields['country'] !== 'France' ? ' · ' . $fields['country'] : ''));
        $dates = (string) $fields['deadline_on'];
        if (!empty($fields['awarded_on']) && $fields['awarded_on'] !== $fields['deadline_on']) {
            $dates .= ($dates !== '' ? ' · remise ' : 'remise ') . $fields['awarded_on'];
        }
        if ($dates === '') {
            $dates = 'dates à confirmer';
        }
        $vars = [
            'demandeur' => $who,
            'email' => (string) $fields['contact_email'],
            'prix' => (string) $fields['name'],
            'lieu' => $lieu !== '' ? $lieu : 'lieu non précisé',
            'dates' => $dates,
            'doublons' => $similar === []
                ? 'Aucune récompense au nom proche dans l\'annuaire.'
                : 'Récompense proche déjà recensée : ' . implode(', ', array_map(static fn (array $s): string => (string) $s['name'], $similar)) . '.',
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
                    'Un prix ou concours a été proposé',
                    $who . ' propose d\'ajouter « ' . $fields['name'] . ' » à l\'annuaire.',
                    $link,
                    'contest_proposal',
                    'contest_proposal',
                    $id
                );
            } catch (\Throwable) {
            }
            Mailer::notify($admin, 'transactional', 'prix-proposition-admin', $vars);
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
        $row['kind_label'] = Contest::kindLabel((string) ($row['kind'] ?? 'prix'));
        $deadline = (string) ($row['deadline_on'] ?? '');
        $awarded = (string) ($row['awarded_on'] ?? '');
        $row['dates'] = $deadline !== '' ? 'limite ' . $deadline : '';
        if ($awarded !== '' && $awarded !== $deadline) {
            $row['dates'] .= ($row['dates'] !== '' ? ' · remise ' : 'remise ') . $awarded;
        }
        return $row;
    }
}
