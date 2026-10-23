<?php

declare(strict_types=1);

namespace Adl\Data;

use Adl\Models\Publisher;
use Adl\Models\Salon;
use PDO;

/**
 * Import des CSV internationaux + corrections des fiches existantes.
 */
final class InternationalImport
{
    public const PUBLISHERS_CSV = 'database/seeds/editeurs-international.csv';
    public const EVENTS_CSV = 'database/seeds/evenements-international.csv';
    public const ENRICH_CSV = 'database/seeds/enrichissement-existants.csv';
    public const PROVENANCE_FILE = 'database/seeds/international-provenance.json';
    public const REJECTED_FILE = 'database/seeds/international-exclus.json';

    /**
     * @return array{
     *   publishers: array{inserted: int, skipped: int, rejected: list<array{name: string, reason: string}>},
     *   salons: array{inserted: int, updated: int, skipped: int, rejected: list<array{name: string, reason: string}>},
     *   enrichments: int
     * }
     */
    public static function apply(PDO $pdo): array
    {
        $publishers = self::importPublishers($pdo);
        $salons = self::importSalons($pdo);
        $enrichments = self::applyEnrichments($pdo);
        self::fixPlaceholderSalonDates($pdo);

        return [
            'publishers' => [
                'inserted' => $publishers['inserted'],
                'skipped' => $publishers['skipped'],
                'rejected' => $publishers['rejected'],
            ],
            'salons' => [
                'inserted' => $salons['inserted'],
                'updated' => $salons['updated'],
                'skipped' => $salons['skipped'],
                'rejected' => $salons['rejected'],
            ],
            'enrichments' => $enrichments,
        ];
    }

    /**
     * @return array{inserted: int, skipped: int, rejected: list<array{name: string, reason: string}>, provenance: list<array<string, string>>}
     */
    public static function importPublishers(PDO $pdo): array
    {
        $rows = self::readCsv(ADL_ROOT . '/' . self::PUBLISHERS_CSV);
        $mapped = [];
        $rejected = [];
        $provenance = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $country = trim((string) ($row['country'] ?? $row['pays'] ?? ''));
            if ($name === '') {
                $rejected[] = ['name' => '', 'reason' => 'nom vide'];
                continue;
            }
            if ($country === '') {
                $rejected[] = ['name' => $name, 'reason' => 'pays vide'];
                continue;
            }
            $contact = trim((string) ($row['contact'] ?? ''));
            if ($contact === '') {
                $email = trim((string) ($row['email'] ?? ''));
                $address = trim((string) ($row['adresse'] ?? ''));
                $contact = trim($email . ($email !== '' && $address !== '' ? ' — ' : '') . $address);
            }
            $mapped[] = [
                'name' => $name,
                'country' => $country,
                'region' => trim((string) ($row['region'] ?? '')),
                'city' => trim((string) ($row['city'] ?? '')),
                'founded' => trim((string) ($row['founded'] ?? '')),
                'group' => trim((string) ($row['group'] ?? '')),
                'size' => trim((string) ($row['size'] ?? '')),
                'typology' => trim((string) ($row['typology'] ?? '')),
                'genres' => trim((string) ($row['genres'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
                'website' => trim((string) ($row['website'] ?? '')),
                'contact' => $contact,
                'telephone' => trim((string) ($row['telephone'] ?? '')),
                'segments' => trim((string) ($row['segments'] ?? '')),
            ];
            $provenance[] = [
                'type' => 'publisher',
                'name' => $name,
                'country' => $country,
                'source_nom' => trim((string) ($row['source_nom'] ?? '')),
                'source_url' => trim((string) ($row['source_url'] ?? '')),
                'statut' => trim((string) ($row['statut'] ?? '')),
                'date_verification' => trim((string) ($row['date_verification'] ?? '')),
            ];
        }

        $result = Publisher::importRows($pdo, $mapped);
        return [
            'inserted' => $result['inserted'],
            'skipped' => $result['skipped'],
            'rejected' => $rejected,
            'provenance' => $provenance,
        ];
    }

    /**
     * @return array{inserted: int, updated: int, skipped: int, rejected: list<array{name: string, reason: string}>, provenance: list<array<string, string>>}
     */
    public static function importSalons(PDO $pdo): array
    {
        $rows = self::readCsv(ADL_ROOT . '/' . self::EVENTS_CSV);
        $existing = $pdo->query('SELECT slug FROM salons')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $taken = array_fill_keys(array_map('strval', $existing), true);

        $stmt = $pdo->prepare(
            'INSERT INTO salons (
                slug, name, category, category_slug, type_label, is_direct,
                dates_raw, starts_on, ends_on, dates_confirmed,
                city, department, region, region_slug, country, country_slug,
                venue, website, attendance, exhibitors, ticket, organizer, contact, socials,
                description, audience, notes, search_text
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?
            )
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                category = VALUES(category),
                category_slug = VALUES(category_slug),
                type_label = VALUES(type_label),
                is_direct = VALUES(is_direct),
                dates_raw = VALUES(dates_raw),
                starts_on = VALUES(starts_on),
                ends_on = VALUES(ends_on),
                dates_confirmed = VALUES(dates_confirmed),
                city = VALUES(city),
                department = VALUES(department),
                region = VALUES(region),
                region_slug = VALUES(region_slug),
                country = VALUES(country),
                country_slug = VALUES(country_slug),
                venue = VALUES(venue),
                website = VALUES(website),
                attendance = VALUES(attendance),
                exhibitors = VALUES(exhibitors),
                ticket = VALUES(ticket),
                organizer = VALUES(organizer),
                contact = VALUES(contact),
                socials = VALUES(socials),
                description = VALUES(description),
                audience = VALUES(audience),
                notes = VALUES(notes),
                search_text = VALUES(search_text)'
        );

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $rejected = [];
        $provenance = [];
        $seen = [];

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                $rejected[] = ['name' => '', 'reason' => 'nom vide'];
                continue;
            }
            $country = trim((string) ($row['country'] ?? $row['pays'] ?? ''));
            $city = trim((string) ($row['city'] ?? ''));
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug === '') {
                $slug = slugify(trim($name . ' ' . $city));
            }
            if ($slug === '') {
                $rejected[] = ['name' => $name, 'reason' => 'slug vide'];
                continue;
            }
            $dupKey = search_norm($name) . '|' . slugify($country) . '|' . slugify($city);
            if (isset($seen[$dupKey])) {
                $rejected[] = ['name' => $name, 'reason' => 'doublon dans le CSV'];
                continue;
            }
            $seen[$dupKey] = true;

            $confirmed = self::boolish($row['dates_confirmed'] ?? false);
            $start = self::dateOrNull((string) ($row['starts_on'] ?? ''), $confirmed);
            $end = self::dateOrNull((string) ($row['ends_on'] ?? ''), $confirmed);
            if ($end !== null && $start !== null && $end < $start) {
                $end = $start;
            }
            $datesRaw = Salon::cleanDateText((string) ($row['dates_raw'] ?? ''));
            $category = trim((string) ($row['category'] ?? ''));
            $region = self::cleanDash((string) ($row['region'] ?? ''));
            $department = self::cleanDash((string) ($row['department'] ?? ''));
            $search = search_norm(implode(' ', array_filter([
                $name,
                $category,
                (string) ($row['type_label'] ?? ''),
                $city,
                $region,
                $country,
                (string) ($row['description'] ?? ''),
                (string) ($row['organizer'] ?? ''),
            ])));

            $existed = isset($taken[$slug]);
            $stmt->execute([
                $slug,
                $name,
                $category,
                slugify($category),
                trim((string) ($row['type_label'] ?? '')),
                self::boolish($row['direct'] ?? true) ? 1 : 0,
                $datesRaw,
                $start,
                $end,
                $confirmed ? 1 : 0,
                $city,
                $department,
                $region,
                slugify($region),
                $country,
                slugify($country),
                Salon::cleanDateText((string) ($row['venue'] ?? '')),
                trim((string) ($row['website'] ?? '')),
                Salon::cleanDateText((string) ($row['attendance'] ?? '')),
                Salon::cleanDateText((string) ($row['exhibitors'] ?? '')),
                Salon::cleanDateText((string) ($row['ticket'] ?? '')),
                trim((string) ($row['organizer'] ?? '')),
                trim((string) ($row['contact'] ?? '')),
                Salon::cleanDateText((string) ($row['socials'] ?? '')),
                trim((string) ($row['description'] ?? '')) ?: null,
                trim((string) ($row['audience'] ?? '')),
                trim((string) ($row['notes'] ?? '')) ?: null,
                $search,
            ]);
            $taken[$slug] = true;
            if ($existed) {
                $updated++;
            } else {
                $inserted++;
            }
            $provenance[] = [
                'type' => 'salon',
                'name' => $name,
                'slug' => $slug,
                'country' => $country,
                'source_url' => trim((string) ($row['source_url'] ?? '')),
                'statut' => trim((string) ($row['statut'] ?? '')),
                'date_verification' => trim((string) ($row['date_verification'] ?? '')),
            ];
        }

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'rejected' => $rejected,
            'provenance' => $provenance,
        ];
    }

    public static function applyEnrichments(PDO $pdo): int
    {
        $rows = self::readCsv(ADL_ROOT . '/' . self::ENRICH_CSV);
        $applied = 0;
        foreach ($rows as $row) {
            $key = trim((string) ($row['id/url_existante'] ?? $row['id'] ?? ''));
            $table = trim((string) ($row['table'] ?? ''));
            $field = trim((string) ($row['champ'] ?? ''));
            $value = (string) ($row['valeur_proposee'] ?? '');
            if ($key === '' || $field === '') {
                continue;
            }
            if ($table === 'salons') {
                $applied += self::enrichSalon($pdo, $key, $field, $value) ? 1 : 0;
                continue;
            }
            if ($table === 'maisons_edition' || $table === 'publishers') {
                $applied += self::enrichPublisher($pdo, $key, $field, $value) ? 1 : 0;
            }
        }
        return $applied;
    }

    private static function enrichSalon(PDO $pdo, string $slug, string $field, string $value): bool
    {
        $allowed = [
            'dates_raw', 'starts_on', 'ends_on', 'dates_confirmed',
            'venue', 'ticket', 'website', 'city', 'region', 'notes',
        ];
        if (!in_array($field, $allowed, true)) {
            return false;
        }
        $row = $pdo->prepare('SELECT id FROM salons WHERE slug = ?');
        $row->execute([$slug]);
        if (!$row->fetchColumn()) {
            return false;
        }
        if ($field === 'dates_confirmed') {
            $value = self::boolish($value) ? '1' : '0';
        }
        if (in_array($field, ['starts_on', 'ends_on'], true)) {
            $value = self::dateOrNull($value, true);
        }
        $stmt = $pdo->prepare('UPDATE salons SET ' . $field . ' = ? WHERE slug = ?');
        $stmt->execute([$value, $slug]);
        return $stmt->rowCount() > 0 || true;
    }

    private static function enrichPublisher(PDO $pdo, string $name, string $field, string $value): bool
    {
        $map = [
            'founded' => 'founded',
            'city' => 'city',
            'website' => 'website',
            'contact (email)' => 'contact_email',
            'contact (adresse)' => 'contact_address',
        ];
        $column = $map[$field] ?? '';
        if ($column === '') {
            return false;
        }
        $norm = search_norm($name);
        $find = $pdo->prepare('SELECT id, founded, city, website, contact_email, contact_address FROM publishers WHERE name_search = ? LIMIT 1');
        $find->execute([$norm]);
        $row = $find->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $find = $pdo->prepare('SELECT id, founded, city, website, contact_email, contact_address FROM publishers WHERE name_search LIKE ? LIMIT 1');
            $find->execute(['%' . $norm . '%']);
            $row = $find->fetch(PDO::FETCH_ASSOC);
        }
        if (!$row) {
            return false;
        }
        if ($column === 'website') {
            $value = Publisher::normalizeWebsite($value);
        }
        if ($column === 'city') {
            $value = Publisher::normalizeCity($value);
        }
        $payload = [
            'founded' => (string) $row['founded'],
            'city' => (string) $row['city'],
            'website' => (string) $row['website'],
            'contact_email' => (string) $row['contact_email'],
            'contact_address' => (string) $row['contact_address'],
        ];
        $payload[$column] = $value;
        if ($column === 'founded') {
            $year = Publisher::foundedYear($value);
            $upd = $pdo->prepare('UPDATE publishers SET founded = ?, founded_year = ?, updated_at = NOW() WHERE id = ?');
            $upd->execute([$value, $year, (int) $row['id']]);
            return true;
        }
        if ($column === 'city') {
            $upd = $pdo->prepare('UPDATE publishers SET city = ?, city_slug = ?, updated_at = NOW() WHERE id = ?');
            $upd->execute([$value, $value !== '' ? slugify($value) : '', (int) $row['id']]);
            return true;
        }
        $upd = $pdo->prepare('UPDATE publishers SET ' . $column . ' = ?, updated_at = NOW() WHERE id = ?');
        $upd->execute([$value, (int) $row['id']]);
        return true;
    }

    /**
     * Dates fictives au 1er janvier + « non trouvé » sur les fiches étrangères déjà en base.
     */
    public static function fixPlaceholderSalonDates(PDO $pdo): void
    {
        $rows = $pdo->query(
            'SELECT id, dates_raw, starts_on, ends_on, dates_confirmed, venue, attendance, exhibitors, ticket, socials, country
             FROM salons'
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $upd = $pdo->prepare(
            'UPDATE salons SET dates_raw = ?, starts_on = ?, ends_on = ?, dates_confirmed = ?,
                    venue = ?, attendance = ?, exhibitors = ?, ticket = ?, socials = ?
             WHERE id = ?'
        );
        foreach ($rows as $row) {
            $confirmed = !empty($row['dates_confirmed']);
            $start = (string) ($row['starts_on'] ?? '');
            $end = (string) ($row['ends_on'] ?? '');
            $raw = Salon::cleanDateText((string) ($row['dates_raw'] ?? ''));
            $newStart = $start;
            $newEnd = $end;
            $newConfirmed = $confirmed ? 1 : 0;
            if (Salon::isPlaceholderDate($start, $confirmed)) {
                $newStart = null;
                $newEnd = null;
                $newConfirmed = 0;
                if ($raw === '' || Salon::isUnverifiedText($raw)) {
                    $raw = '';
                }
            }
            $venue = Salon::cleanDateText((string) ($row['venue'] ?? ''));
            $attendance = Salon::cleanDateText((string) ($row['attendance'] ?? ''));
            $exhibitors = Salon::cleanDateText((string) ($row['exhibitors'] ?? ''));
            $ticket = Salon::cleanDateText((string) ($row['ticket'] ?? ''));
            $socials = Salon::cleanDateText((string) ($row['socials'] ?? ''));
            if (
                $raw === (string) $row['dates_raw']
                && $newStart === $start
                && $newEnd === $end
                && $newConfirmed === (int) $row['dates_confirmed']
                && $venue === (string) $row['venue']
                && $attendance === (string) $row['attendance']
                && $exhibitors === (string) $row['exhibitors']
                && $ticket === (string) $row['ticket']
                && $socials === (string) $row['socials']
            ) {
                continue;
            }
            $upd->execute([
                $raw,
                $newStart !== '' ? $newStart : null,
                $newEnd !== '' ? $newEnd : null,
                $newConfirmed,
                $venue,
                $attendance,
                $exhibitors,
                $ticket,
                $socials,
                (int) $row['id'],
            ]);
        }
    }

    /**
     * @param list<array<string, string>> $publisherProv
     * @param list<array<string, string>> $salonProv
     * @param list<array{name: string, reason: string}> $pubRejected
     * @param list<array{name: string, reason: string}> $salonRejected
     */
    private static function writeSidecar(array $publisherProv, array $salonProv, array $pubRejected, array $salonRejected): void
    {
        $dir = ADL_ROOT . '/database/seeds';
        if (!is_dir($dir)) {
            return;
        }
        $payload = [
            'generated_at' => '2026-10-23',
            'publishers' => $publisherProv,
            'salons' => $salonProv,
        ];
        file_put_contents(
            ADL_ROOT . '/' . self::PROVENANCE_FILE,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n"
        );
        $rejected = [
            'publishers' => $pubRejected,
            'salons' => $salonRejected,
        ];
        file_put_contents(
            ADL_ROOT . '/' . self::REJECTED_FILE,
            json_encode($rejected, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n"
        );
    }

    /**
     * @return list<array<string, string>>
     */
    public static function readCsv(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }
        $fh = fopen($path, 'r');
        if ($fh === false) {
            return [];
        }
        $bom = fread($fh, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($fh);
        }
        $header = fgetcsv($fh, 0, ';');
        if (!is_array($header) || $header === []) {
            fclose($fh);
            return [];
        }
        $header = array_map(static fn ($h): string => trim((string) $h), $header);
        $out = [];
        while (($cols = fgetcsv($fh, 0, ';')) !== false) {
            if ($cols === [null] || $cols === false) {
                continue;
            }
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = isset($cols[$i]) ? (string) $cols[$i] : '';
            }
            $out[] = $row;
        }
        fclose($fh);
        return $out;
    }

    private static function boolish(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $n = search_norm((string) $value);
        return in_array($n, ['1', 'true', 'oui', 'yes', 'confirme', 'vrai'], true);
    }

    private static function dateOrNull(string $raw, bool $confirmed): ?string
    {
        $raw = trim($raw);
        if ($raw === '' || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
            return null;
        }
        if (!checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }
        if (Salon::isPlaceholderDate($raw, $confirmed)) {
            return null;
        }
        return $raw;
    }

    private static function cleanDash(string $raw): string
    {
        $raw = trim($raw);
        return ($raw === '' || $raw === '—' || $raw === '-') ? '' : $raw;
    }
}
