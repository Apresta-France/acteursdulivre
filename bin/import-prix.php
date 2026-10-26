<?php

declare(strict_types=1);

/*
 * Importe (ou complète) l'annuaire des prix et concours depuis un CSV
 * (séparateur ";", en-têtes en français) ou le JSON de seed.
 *
 *   php bin/import-prix.php [chemin/vers/fichier.csv|fichier.json]
 *
 * Par défaut : database/seeds/prix.json. Les récompenses déjà présentes
 * (même nom + même ville + même année) sont ignorées : les fiches retouchées
 * ne sont jamais écrasées.
 *
 * Colonnes attendues du CSV (ordre libre) :
 *   nom;type;categorie;genres;pays;ville_lieu;organisateur;edition;annee_edition;statut;
 *   ouverture_candidatures;date_limite_candidature;date_proclamation;precision_date;
 *   dotation;avantages_publication;mode_participation;eligibilite;frais_inscription;
 *   contraintes_texte;theme_impose;jury_president;selection_connue;laureat_precedent;
 *   site_officiel;sources;notes;fiabilite
 */

define('ADL_ROOT', dirname(__DIR__));
require ADL_ROOT . '/app/bootstrap.php';

use Adl\Models\Contest;

$file = $argv[1] ?? ADL_ROOT . '/database/seeds/prix.json';
if (!is_file($file)) {
    fwrite(STDERR, "Fichier introuvable : {$file}\n");
    exit(1);
}

$isCsv = str_ends_with(strtolower($file), '.csv');
$items = $isCsv ? rowsFromCsv($file) : rowsFromJson($file);
if ($items === null) {
    fwrite(STDERR, "Fichier illisible ou vide.\n");
    exit(1);
}

$result = Contest::importItems($items);
echo 'inserted=' . $result['inserted'] . ' skipped=' . $result['skipped'] . ' total=' . count($items) . PHP_EOL;

/** @return list<array<string, mixed>>|null */
function rowsFromJson(string $file): ?array
{
    $rows = json_decode((string) file_get_contents($file), true);
    return is_array($rows) ? $rows : null;
}

/** @return list<array<string, mixed>>|null */
function rowsFromCsv(string $file): ?array
{
    $handle = fopen($file, 'rb');
    if ($handle === false) {
        return null;
    }
    $headers = fgetcsv($handle, 0, ';', '"', '');
    if (!is_array($headers)) {
        fclose($handle);
        return null;
    }
    $headers = array_map(
        static fn (mixed $h): string => slugify(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)),
        $headers
    );

    $items = [];
    while (($row = fgetcsv($handle, 0, ';', '"', '')) !== false) {
        if (!is_array($row) || count(array_filter($row, static fn (mixed $v): bool => trim((string) $v) !== '')) === 0) {
            continue;
        }
        $data = [];
        foreach ($headers as $i => $key) {
            if ($key === '') {
                continue;
            }
            $data[$key] = trim((string) ($row[$i] ?? ''));
        }
        $item = mapCsvRow($data);
        if ($item !== null) {
            $items[] = $item;
        }
    }
    fclose($handle);
    return $items;
}

/** @param array<string, string> $data */
function mapCsvRow(array $data): ?array
{
    $name = trim((string) ($data['nom'] ?? $data['name'] ?? ''));
    if ($name === '') {
        return null;
    }

    [$city, $venue] = splitPlace((string) ($data['ville-lieu'] ?? $data['ville'] ?? $data['lieu'] ?? ''));
    $country = firstSegment((string) ($data['pays'] ?? 'France'));
    $year = trim((string) ($data['annee-edition'] ?? ''));

    $deadline = csvDate((string) ($data['date-limite-candidature'] ?? $data['date-limite'] ?? ''));
    $awarded = csvDate((string) ($data['date-proclamation'] ?? $data['proclamation'] ?? ''));
    $opens = csvDate((string) ($data['ouverture-candidatures'] ?? $data['ouverture'] ?? ''));

    $precision = strtolower((string) ($data['precision-date'] ?? ''));
    $confirmed = str_contains($precision, 'exacte');

    $prize = (string) ($data['dotation'] ?? '');
    $extra = trim((string) ($data['avantages-publication'] ?? ''));
    if ($extra !== '') {
        $prize = trim($prize . ($prize !== '' ? ' · ' : '') . $extra);
    }

    $notes = [];
    $edition = trim((string) ($data['edition'] ?? ''));
    if ($edition !== '') {
        $notes[] = trim($edition . ' édition' . ($year !== '' ? ' (' . $year . ')' : ''), ' .');
    }
    foreach ([
        'statut' => '',
        'jury-president' => 'Jury : ',
        'contraintes-texte' => 'Contraintes : ',
        'theme-impose' => 'Thème : ',
        'selection-connue' => 'Sélection : ',
        'laureat-precedent' => 'Lauréat·e précédent·e : ',
        'notes' => '',
    ] as $key => $prefix) {
        $value = trim((string) ($data[$key] ?? ''));
        if ($value !== '') {
            $notes[] = $prefix . $value;
        }
    }
    $sources = trim((string) ($data['sources'] ?? ''));
    if ($sources !== '') {
        $notes[] = 'Sources : ' . str_replace('|', '·', $sources);
    }

    $descriptionParts = array_filter([
        trim((string) ($data['genres'] ?? '')),
        trim((string) ($data['mode-participation'] ?? '')),
    ]);

    return [
        'name' => $name,
        'kind' => csvKind((string) ($data['type'] ?? '')),
        'kind_label' => trim((string) ($data['type'] ?? '')),
        'category' => trim((string) ($data['categorie'] ?? $data['genre'] ?? '')),
        'opens_on' => $opens,
        'deadline_on' => $deadline,
        'awarded_on' => $awarded,
        'dates_raw' => rawDatesCsv($deadline, $awarded, $data),
        'dates_confirmed' => $confirmed ? 1 : 0,
        'prize' => $prize,
        'entry_fee' => trim((string) ($data['frais-inscription'] ?? $data['frais'] ?? '')),
        'eligibility' => trim((string) ($data['eligibilite'] ?? '')),
        'city' => $city,
        'region' => trim((string) ($data['region'] ?? '')),
        'country' => $country !== '' ? $country : 'France',
        'venue' => $venue,
        'website' => trim((string) ($data['site-officiel'] ?? $data['site'] ?? '')),
        'organizer' => trim((string) ($data['organisateur'] ?? '')),
        'description' => implode('. ', $descriptionParts),
        'audience' => trim((string) ($data['mode-participation'] ?? '')),
        'notes' => implode(' ', $notes),
    ];
}

function csvKind(string $type): string
{
    $n = search_norm($type);
    if (str_contains($n, 'concours')) {
        return 'concours';
    }
    if (str_contains($n, 'appel')) {
        return 'appel';
    }
    if (str_contains($n, 'bourse') || str_contains($n, 'residence')) {
        return 'bourse';
    }
    return 'prix';
}

function csvDate(string $raw): ?string
{
    $raw = trim($raw);
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m) === 1 && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
        return $raw;
    }
    return null;
}

/** @param array<string, string> $data */
function rawDatesCsv(?string $deadline, ?string $awarded, array $data): string
{
    $parts = [];
    $rawDeadline = trim((string) ($data['date-limite-candidature'] ?? $data['date-limite'] ?? ''));
    $rawAwarded = trim((string) ($data['date-proclamation'] ?? $data['proclamation'] ?? ''));
    if ($deadline !== null) {
        $parts[] = 'limite ' . $deadline;
    } elseif ($rawDeadline !== '') {
        $parts[] = 'limite ' . $rawDeadline;
    }
    if ($awarded !== null && $awarded !== $deadline) {
        $parts[] = 'remise ' . $awarded;
    } elseif ($awarded === null && $rawAwarded !== '') {
        $parts[] = 'remise ' . $rawAwarded;
    }
    return implode(' – ', $parts);
}

/** @return array{0: string, 1: string} */
function splitPlace(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return ['', ''];
    }
    $parts = preg_split('/\s+[—–-]\s+/u', $raw, 2) ?: [$raw];
    $city = trim((string) ($parts[0] ?? ''));
    $venue = trim((string) ($parts[1] ?? ''));
    return [$city, $venue];
}

function firstSegment(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }
    $parts = preg_split('/\s*[\/,]\s*/u', $raw) ?: [$raw];
    return trim((string) ($parts[0] ?? ''));
}
