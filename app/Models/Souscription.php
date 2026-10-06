<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Core\Mailer;
use Adl\Data\Share;
use Throwable;

final class Souscription
{
    public const KINDS = [
        'souscription' => 'En création',
        'prevente' => 'Prévente',
        'vente' => 'En vente',
        'sponsorise' => 'Sponsorisé',
    ];

    public const STATUSES = [
        'pending' => 'Proposée',
        'draft' => 'Brouillon',
        'open' => 'Ouverte',
        'closed' => 'Clôturée',
    ];

    public const DAILY_LIMIT = 3;

    /** @return list<array<string, mixed>> */
    public static function publicItems(): array
    {
        try {
            $rows = Database::fetchAll(
                'SELECT * FROM souscriptions
                 WHERE status IN ("open", "closed")
                 ORDER BY featured DESC, closes_on IS NULL, closes_on ASC, id ASC'
            );
        } catch (Throwable) {
            return [];
        }

        return array_map([self::class, 'shape'], $rows);
    }

    /** @return list<array<string, mixed>> */
    public static function adminList(): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM souscriptions
             ORDER BY FIELD(status, "pending", "open", "draft", "closed"), featured DESC, closes_on IS NULL, closes_on ASC, title ASC'
        );
        $today = date('Y-m-d');
        $items = [];
        foreach ($rows as $row) {
            $item = self::shape($row);
            [$label, $tone] = self::adminStatus($item, $today);
            $item['status_label'] = $label;
            $item['status_tone'] = $tone;
            $items[] = $item;
        }

        return $items;
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        $row = Database::fetch('SELECT * FROM souscriptions WHERE id = ?', [$id]);
        if (!$row) {
            return null;
        }
        $item = self::shape($row);
        $item['body_text'] = implode("\n\n", $item['body']);
        $item['trades_text'] = self::pairsToText($item['trades'], 'role', 'name');
        $item['facts_text'] = self::pairsToText($item['facts'], 0, 1);
        $item['cover_ink'] = $item['cover']['ink'];
        $item['cover_paper'] = $item['cover']['paper'];
        $item['cover_rule'] = $item['cover']['rule'];

        return $item;
    }

    /** @return array<string, mixed> */
    public static function blank(): array
    {
        return [
            'id' => 0,
            'title' => '',
            'slug' => '',
            'genre' => '',
            'kind' => 'souscription',
            'status' => 'draft',
            'featured' => false,
            'bearer' => '',
            'bearer_role' => '',
            'pitch' => '',
            'body_text' => '',
            'trades_text' => '',
            'facts_text' => '',
            'cover_ink' => '#15212f',
            'cover_paper' => '#f4efe6',
            'cover_rule' => '#eb963b',
            'cover_image' => '',
            'closes' => '',
            'proposer_name' => '',
            'proposer_email' => '',
            'proposer_note' => '',
            'host' => '',
            'external_url' => '',
            'cta' => '',
            'outcome' => '',
        ];
    }

    /** @param array<string, mixed> $data */
    public static function save(?int $id, array $data): int
    {
        $current = $id ? self::find($id) : null;
        if ($id && !$current) {
            throw new \RuntimeException('Annonce introuvable.');
        }

        $title = self::limited(trim((string) ($data['title'] ?? '')), 190, 'Le titre');
        if ($title === '') {
            throw new \InvalidArgumentException('Le titre est obligatoire.');
        }
        $kind = (string) ($data['kind'] ?? 'souscription');
        if (!isset(self::KINDS[$kind])) {
            throw new \InvalidArgumentException('Le type d’annonce n’est pas reconnu.');
        }
        $status = (string) ($data['status'] ?? 'draft');
        if (!isset(self::STATUSES[$status])) {
            throw new \InvalidArgumentException('Le statut n’est pas reconnu.');
        }
        $closes = trim((string) ($data['closes'] ?? ''));
        if ($closes !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $closes)) {
            throw new \InvalidArgumentException('La date de clôture n’est pas valide.');
        }
        $dateOptional = in_array($status, ['draft', 'pending'], true) || ($status === 'open' && $kind === 'sponsorise');
        if ($closes === '' && !$dateOptional) {
            throw new \InvalidArgumentException('Indiquez la date de clôture pour une annonce visible.');
        }

        $slug = unique_slug(
            trim((string) ($data['slug'] ?? '')) ?: $title,
            static fn (string $candidate): bool => Database::fetch(
                'SELECT id FROM souscriptions WHERE slug = ? AND id != ?',
                [$candidate, $id ?? 0]
            ) !== null
        );
        $featured = !empty($data['featured']) ? 1 : 0;
        $coverImage = self::coverPath((string) ($current['cover_image'] ?? ''));
        if (!empty($data['remove_cover'])) {
            self::forgetCover($coverImage);
            $coverImage = '';
        }
        $upload = $data['cover_file'] ?? null;
        if (is_array($upload)) {
            $stored = store_upload($upload, 'souscriptions', ['jpg', 'jpeg', 'png', 'webp'], 4 * 1024 * 1024);
            if (is_string($stored) && $stored !== '') {
                if ($coverImage !== '' && $coverImage !== $stored) {
                    self::forgetCover($coverImage);
                }
                $coverImage = $stored;
            }
        }
        $payload = [
            $title,
            $slug,
            self::limited(trim((string) ($data['genre'] ?? '')), 120, 'Le genre'),
            $kind,
            $status,
            $featured,
            self::limited(trim((string) ($data['bearer'] ?? '')), 190, 'Le porteur'),
            self::limited(trim((string) ($data['bearer_role'] ?? '')), 120, 'Le rôle du porteur'),
            self::limited(trim((string) ($data['pitch'] ?? '')), 600, 'Le chapô'),
            self::body(self::asText($data['body'] ?? '')),
            self::encodePairs(self::parseLines(self::asText($data['trades'] ?? ''), 'role', 'name'), 12, 'intervenants'),
            self::encodePairs(self::parseLines(self::asText($data['facts'] ?? ''), 0, 1), 12, 'renseignements'),
            self::hex((string) ($data['cover_ink'] ?? ''), '#15212f'),
            self::hex((string) ($data['cover_paper'] ?? ''), '#f4efe6'),
            self::hex((string) ($data['cover_rule'] ?? ''), '#eb963b'),
            $coverImage,
            $closes !== '' ? $closes : null,
            self::limited(trim((string) ($data['host'] ?? '')), 190, 'La plateforme'),
            self::externalUrl(trim((string) ($data['external_url'] ?? ''))),
            self::limited(trim((string) ($data['cta'] ?? '')), 120, 'Le libellé du bouton'),
            self::limited(trim((string) ($data['outcome'] ?? '')), 190, 'La mention de clôture'),
        ];

        return (int) Database::transaction(static function () use ($id, $current, $featured, $status, $payload): int {
            if ($featured === 1 && $status === 'open') {
                Database::query('UPDATE souscriptions SET featured = 0 WHERE id != ?', [$id ?? 0]);
            }
            if ($current) {
                Database::query(
                    'UPDATE souscriptions SET
                        title = ?, slug = ?, genre = ?, kind = ?, status = ?, featured = ?,
                        bearer = ?, bearer_role = ?, pitch = ?, body = ?, trades_json = ?, facts_json = ?,
                        cover_ink = ?, cover_paper = ?, cover_rule = ?, cover_image = ?, closes_on = ?,
                        host = ?, external_url = ?, cta = ?, outcome = ?, updated_at = NOW()
                     WHERE id = ?',
                    [...$payload, $id]
                );

                return (int) $id;
            }
            Database::query(
                'INSERT INTO souscriptions (
                    title, slug, genre, kind, status, featured,
                    bearer, bearer_role, pitch, body, trades_json, facts_json,
                    cover_ink, cover_paper, cover_rule, cover_image, closes_on,
                    host, external_url, cta, outcome, created_at
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                $payload
            );

            return (int) Database::lastId();
        });
    }

    public static function delete(int $id): void
    {
        $row = Database::fetch('SELECT cover_image FROM souscriptions WHERE id = ?', [$id]);
        Database::query('DELETE FROM souscriptions WHERE id = ?', [$id]);
        if ($row) {
            self::forgetCover((string) ($row['cover_image'] ?? ''));
        }
    }

    public static function countPending(): int
    {
        return (int) (Database::fetch('SELECT COUNT(*) AS n FROM souscriptions WHERE status = "pending"')['n'] ?? 0);
    }

    /**
     * Proposition publique : invisible tant que l’équipe ne l’ouvre pas.
     *
     * @param array<string, mixed> $data
     */
    public static function propose(array $data, ?array $user = null): int
    {
        $kind = (string) ($data['kind'] ?? '');
        if (!isset(self::KINDS[$kind])) {
            throw new \InvalidArgumentException('Choisissez ce que vous annoncez : création, prévente, vente ou sponsoring.');
        }
        $title = self::limited(trim((string) ($data['title'] ?? '')), 190, 'Le titre');
        if ($title === '') {
            throw new \InvalidArgumentException('Le titre est obligatoire.');
        }
        $bearer = self::limited(trim((string) ($data['bearer'] ?? '')), 190, 'Le porteur');
        if ($bearer === '') {
            throw new \InvalidArgumentException('Indiquez qui porte le livre.');
        }
        $pitch = self::limited(trim((string) ($data['pitch'] ?? '')), 600, 'Le chapô');
        if (mb_strlen($pitch) < 40) {
            throw new \InvalidArgumentException('Le chapô doit faire au moins 40 caractères.');
        }
        $host = self::limited(trim((string) ($data['host'] ?? '')), 190, 'La plateforme');
        if ($host === '') {
            throw new \InvalidArgumentException('Indiquez où la campagne est ouverte.');
        }
        $url = self::externalUrl(trim((string) ($data['external_url'] ?? '')));
        if ($url === '') {
            throw new \InvalidArgumentException('Le lien vers la campagne est obligatoire.');
        }
        $closes = trim((string) ($data['closes'] ?? ''));
        if ($closes !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $closes)) {
            throw new \InvalidArgumentException('La date de clôture n’est pas valide.');
        }
        if ($kind !== 'sponsorise' && $closes === '') {
            throw new \InvalidArgumentException('Indiquez la date de clôture.');
        }
        if ($closes !== '' && $closes < date('Y-m-d')) {
            throw new \InvalidArgumentException('Cette campagne est déjà close.');
        }
        $name = self::limited(trim((string) ($data['contact_name'] ?? '')), 190, 'Votre nom');
        if ($name === '') {
            throw new \InvalidArgumentException('Votre nom est obligatoire.');
        }
        $email = strtolower(trim((string) ($data['contact_email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            throw new \InvalidArgumentException('L’adresse e-mail n’est pas valide.');
        }
        $note = self::body(self::asText($data['note'] ?? ''));
        self::assertDailyLimit($email);
        $listed = Database::fetch(
            'SELECT status FROM souscriptions WHERE external_url = ? AND status IN ("pending", "open") LIMIT 1',
            [$url]
        );
        if ($listed) {
            $message = ($listed['status'] ?? '') === 'open'
                ? 'Ce livre figure déjà dans les annonces.'
                : 'Une proposition avec ce lien est déjà en cours d’examen.';
            throw new \RuntimeException($message);
        }

        $slug = unique_slug(
            $title,
            static fn (string $candidate): bool => Database::fetch(
                'SELECT id FROM souscriptions WHERE slug = ?',
                [$candidate]
            ) !== null
        );
        $role = self::limited(trim((string) ($data['bearer_role'] ?? '')), 120, 'Le rôle du porteur');
        $genre = self::limited(trim((string) ($data['genre'] ?? '')), 120, 'Le genre');
        $cta = match ($kind) {
            'prevente' => 'Réserver',
            'vente' => 'Voir la vente',
            'sponsorise' => 'Voir le livre',
            default => 'Soutenir la souscription',
        };

        Database::query(
            'INSERT INTO souscriptions (
                title, slug, genre, kind, status, featured,
                bearer, bearer_role, pitch, closes_on, host, external_url, cta,
                proposer_name, proposer_email, proposer_note, created_at
             ) VALUES (?, ?, ?, ?, "pending", 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                $title,
                $slug,
                $genre,
                $kind,
                $bearer,
                $role,
                $pitch,
                $closes !== '' ? $closes : null,
                $host,
                $url,
                $cta,
                $name,
                $email,
                $note,
            ]
        );
        $id = (int) Database::lastId();
        try {
            self::notifyProposal($id, [
                'title' => $title,
                'kind' => $kind,
                'host' => $host,
                'contact_name' => $name,
                'contact_email' => $email,
                'note' => (string) $note,
            ], $user);
        } catch (Throwable) {
        }

        return $id;
    }

    /** @param array<string, mixed> $row */
    private static function shape(array $row): array
    {
        $kind = (string) ($row['kind'] ?? 'souscription');

        return [
            'id' => (int) ($row['id'] ?? 0),
            'slug' => (string) ($row['slug'] ?? ''),
            'title' => (string) ($row['title'] ?? ''),
            'genre' => (string) ($row['genre'] ?? ''),
            'kind' => $kind,
            'kind_label' => self::KINDS[$kind] ?? 'En création',
            'status' => (string) ($row['status'] ?? 'draft'),
            'featured' => (int) ($row['featured'] ?? 0) === 1,
            'bearer' => (string) ($row['bearer'] ?? ''),
            'bearer_role' => (string) ($row['bearer_role'] ?? ''),
            'pitch' => (string) ($row['pitch'] ?? ''),
            'body' => self::paragraphs((string) ($row['body'] ?? '')),
            'trades' => self::decodeList((string) ($row['trades_json'] ?? '')),
            'facts' => self::decodeList((string) ($row['facts_json'] ?? '')),
            'cover' => [
                'ink' => (string) ($row['cover_ink'] ?? '#15212f'),
                'paper' => (string) ($row['cover_paper'] ?? '#f4efe6'),
                'rule' => (string) ($row['cover_rule'] ?? '#eb963b'),
            ],
            'cover_image' => self::coverPath((string) ($row['cover_image'] ?? '')),
            'proposer_name' => (string) ($row['proposer_name'] ?? ''),
            'proposer_email' => (string) ($row['proposer_email'] ?? ''),
            'proposer_note' => (string) ($row['proposer_note'] ?? ''),
            'closes' => $row['closes_on'] ? (string) $row['closes_on'] : '',
            'host' => (string) ($row['host'] ?? ''),
            'external_url' => (string) ($row['external_url'] ?? ''),
            'cta' => (string) ($row['cta'] ?? ''),
            'outcome' => (string) ($row['outcome'] ?? ''),
        ];
    }

    /** @param array<string, mixed> $item
     * @return array{0: string, 1: string}
     */
    private static function adminStatus(array $item, string $today): array
    {
        $status = (string) ($item['status'] ?? 'draft');
        if ($status === 'pending') {
            return ['Proposée', 'orange'];
        }
        if ($status === 'draft') {
            return ['Brouillon', 'grey'];
        }
        if ($status === 'closed') {
            return ['Clôturée', 'navy'];
        }
        $closes = (string) ($item['closes'] ?? '');
        if ($closes !== '' && $closes < $today) {
            return ['Échue', 'orange'];
        }

        return ['Ouverte', 'green'];
    }

    private static function asText(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private static function limited(string $value, int $max, string $label): string
    {
        if (mb_strlen($value) > $max) {
            throw new \InvalidArgumentException($label . ' est trop long (' . $max . ' caractères maximum).');
        }

        return $value;
    }

    private static function body(string $value): ?string
    {
        $value = trim(strip_tags($value));
        if ($value === '') {
            return null;
        }
        if (mb_strlen($value) > 20000) {
            throw new \InvalidArgumentException('Le texte est trop long.');
        }

        return $value;
    }

    private static function externalUrl(string $value): string
    {
        if ($value === '') {
            return '';
        }
        if (mb_strlen($value) > 500) {
            throw new \InvalidArgumentException('L’adresse du lien est trop longue.');
        }
        if (!preg_match('#^https?://#i', $value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('L’adresse du lien doit commencer par https://.');
        }

        return $value;
    }

    private static function hex(string $value, string $fallback): string
    {
        $value = strtolower(trim($value));

        return preg_match('/^#[0-9a-f]{6}$/', $value) ? $value : $fallback;
    }

    /** @return list<string> */
    private static function paragraphs(string $body): array
    {
        $parts = preg_split("/\n\s*\n/", trim($body)) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $out[] = $part;
            }
        }

        return $out;
    }

    /**
     * @param 'role'|0 $left
     * @param 'name'|1 $right
     * @return list<array<int|string, string>>
     */
    private static function parseLines(string $text, string|int $left, string|int $right): array
    {
        $out = [];
        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line, 2));
            $a = self::limited($parts[0] ?? '', 120, 'Une ligne');
            $b = self::limited($parts[1] ?? '', 190, 'Une ligne');
            if ($a === '' && $b === '') {
                continue;
            }
            $out[] = [$left => $a, $right => $b];
        }

        return $out;
    }

    /** @param list<array<int|string, string>> $rows */
    private static function encodePairs(array $rows, int $max, string $label): ?string
    {
        if (count($rows) > $max) {
            throw new \InvalidArgumentException('Trop de ' . $label . ' (' . $max . ' maximum).');
        }
        if ($rows === []) {
            return null;
        }

        return json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /** @return list<array<int|string, string>> */
    private static function decodeList(string $json): array
    {
        if ($json === '') {
            return [];
        }
        $data = json_decode($json, true);

        return is_array($data) ? array_values(array_filter($data, 'is_array')) : [];
    }

    /** @param list<array<int|string, mixed>> $rows */
    private static function pairsToText(array $rows, string|int $left, string|int $right): string
    {
        $lines = [];
        foreach ($rows as $row) {
            $a = trim((string) ($row[$left] ?? ''));
            $b = trim((string) ($row[$right] ?? ''));
            if ($a === '' && $b === '') {
                continue;
            }
            $lines[] = $b === '' ? $a : $a . ' | ' . $b;
        }

        return implode("\n", $lines);
    }

    private static function coverPath(string $path): string
    {
        $path = str_replace(['\\', "\0"], '/', trim($path));
        if ($path === '' || str_contains($path, '..') || !str_starts_with($path, 'souscriptions/')) {
            return '';
        }

        return $path;
    }

    private static function forgetCover(string $path): void
    {
        $path = self::coverPath($path);
        if ($path === '') {
            return;
        }
        $file = ADL_ROOT . '/public/uploads/' . $path;
        if (is_file($file)) {
            unlink($file);
        }
    }

    private static function assertDailyLimit(string $email): void
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM souscriptions
             WHERE proposer_email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)',
            [$email]
        );
        if ((int) ($row['n'] ?? 0) >= self::DAILY_LIMIT) {
            throw new \RuntimeException('Vous avez déjà proposé plusieurs livres aujourd’hui. Réessayez demain.');
        }
    }

    /**
     * @param array{title: string, kind: string, host: string, contact_name: string, contact_email: string, note: string} $fields
     */
    private static function notifyProposal(int $id, array $fields, ?array $user): void
    {
        $who = $user ? User::displayName($user) : $fields['contact_name'];
        $link = '/admin/souscriptions/' . $id;
        $prenom = $user
            ? trim((string) ($user['first_name'] ?? ''))
            : trim(explode(' ', $fields['contact_name'])[0] ?? $fields['contact_name']);
        $adminVars = [
            'demandeur' => $who,
            'email' => $fields['contact_email'],
            'livre' => $fields['title'],
            'type' => self::KINDS[$fields['kind']] ?? 'En création',
            'plateforme' => $fields['host'],
            'message' => $fields['note'] !== '' ? $fields['note'] : 'Aucun message complémentaire.',
            'lien_admin' => Share::absolute($link),
        ];
        try {
            $admins = User::activeAdmins();
        } catch (Throwable) {
            $admins = [];
        }
        foreach ($admins as $admin) {
            $adminId = (int) ($admin['id'] ?? 0);
            if ($adminId < 1) {
                continue;
            }
            try {
                Notification::upsertUnread(
                    $adminId,
                    'Un livre a été proposé',
                    $who . ' propose d’annoncer « ' . $fields['title'] . ' ».',
                    $link,
                    'souscription_proposal',
                    'souscription',
                    $id
                );
            } catch (Throwable) {
            }
            try {
                Mailer::notify($admin, 'transactional', 'souscription-proposition-admin', $adminVars);
            } catch (Throwable) {
            }
        }

        $receipt = [
            'prenom' => $prenom !== '' ? $prenom : $fields['contact_name'],
            'livre' => $fields['title'],
            'lien' => Share::absolute('/souscriptions'),
        ];
        try {
            Mailer::sendTemplate('souscription-proposition-recue', $fields['contact_email'], $receipt);
        } catch (Throwable) {
        }
    }
}
