<?php

declare(strict_types=1);

namespace Adl\Data;

final class Isbn
{
    public const DEFAULT = '978-2-07-036822-8';
    public const RAW_MAX = 40;

    /**
     * @return array<string, list<string>>
     */
    public static function groupPrefixes(): array
    {
        return [
            '978' => [
                '65', '80', '81', '82', '83', '84', '85', '86', '87', '88', '89',
                '90', '91', '92', '93', '94',
                '0', '1', '2', '3', '4', '5', '7',
            ],
            '979' => ['10', '11', '12', '8', '0'],
        ];
    }

    /**
     * @return array<string, list<array{from: string, to: string, len: int}>>
     */
    public static function registrantRules(): array
    {
        return [
            '978-2' => [
                ['from' => '00', 'to' => '19', 'len' => 2],
                ['from' => '200', 'to' => '349', 'len' => 3],
                ['from' => '35000', 'to' => '39999', 'len' => 5],
                ['from' => '400', 'to' => '699', 'len' => 3],
                ['from' => '7000', 'to' => '8399', 'len' => 4],
                ['from' => '84000', 'to' => '89999', 'len' => 5],
                ['from' => '900000', 'to' => '949999', 'len' => 6],
                ['from' => '9500000', 'to' => '9999999', 'len' => 7],
            ],
            '979-10' => [
                ['from' => '00', 'to' => '19', 'len' => 2],
                ['from' => '200', 'to' => '699', 'len' => 3],
                ['from' => '7000', 'to' => '8999', 'len' => 4],
                ['from' => '90000', 'to' => '97599', 'len' => 5],
                ['from' => '976000', 'to' => '999999', 'len' => 6],
            ],
            '978-0' => [
                ['from' => '00', 'to' => '19', 'len' => 2],
                ['from' => '200', 'to' => '227', 'len' => 3],
                ['from' => '2280', 'to' => '2289', 'len' => 4],
                ['from' => '229', 'to' => '647', 'len' => 3],
                ['from' => '6480000', 'to' => '6489999', 'len' => 7],
                ['from' => '649', 'to' => '654', 'len' => 3],
                ['from' => '6550', 'to' => '6559', 'len' => 4],
                ['from' => '656', 'to' => '699', 'len' => 3],
                ['from' => '7000', 'to' => '8499', 'len' => 4],
                ['from' => '85000', 'to' => '89999', 'len' => 5],
                ['from' => '900000', 'to' => '949999', 'len' => 6],
                ['from' => '9500000', 'to' => '9999999', 'len' => 7],
            ],
            '978-1' => [
                ['from' => '00', 'to' => '09', 'len' => 2],
                ['from' => '100', 'to' => '327', 'len' => 3],
                ['from' => '3280', 'to' => '3289', 'len' => 4],
                ['from' => '329', 'to' => '399', 'len' => 3],
                ['from' => '4000', 'to' => '5499', 'len' => 4],
                ['from' => '55000', 'to' => '86979', 'len' => 5],
                ['from' => '869800', 'to' => '998999', 'len' => 6],
                ['from' => '9990000', 'to' => '9999999', 'len' => 7],
            ],
            '978-3' => [
                ['from' => '00', 'to' => '02', 'len' => 2],
                ['from' => '030', 'to' => '033', 'len' => 3],
                ['from' => '0340', 'to' => '0369', 'len' => 4],
                ['from' => '03700', 'to' => '03999', 'len' => 5],
                ['from' => '04', 'to' => '19', 'len' => 2],
                ['from' => '200', 'to' => '699', 'len' => 3],
                ['from' => '7000', 'to' => '8499', 'len' => 4],
                ['from' => '85000', 'to' => '89999', 'len' => 5],
                ['from' => '900000', 'to' => '949999', 'len' => 6],
                ['from' => '9500000', 'to' => '9999999', 'len' => 7],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function groupHints(): array
    {
        return [
            '978-0' => 'Zone anglophone',
            '978-1' => 'Zone anglophone',
            '978-2' => 'AFNIL',
            '978-3' => 'Zone germanophone',
            '978-4' => 'Japon',
            '978-5' => 'Ancienne URSS',
            '978-7' => 'Chine',
            '979-8' => 'États-Unis (979)',
            '979-10' => 'France (979)',
            '979-11' => 'Corée (979)',
            '979-12' => 'Italie (979)',
            '979-0' => 'ISMN (partitions)',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function analyse(string $raw): array
    {
        $raw = self::clip($raw);
        $compact = self::compact($raw);
        $len = strlen($compact);

        if ($compact === '') {
            return self::pack([
                'input' => $raw,
                'compact' => '',
                'length' => 0,
                'target' => 13,
                'status' => 'empty',
                'status_label' => 'Saisissez un ISBN',
                'kind' => '',
                'kind_label' => '',
                'valid' => false,
                'check_ok' => null,
                'expected_check' => null,
                'notices' => ['ISBN-10 (dix caractères, éventuellement un X) ou ISBN-13 (978 ou 979).'],
            ]);
        }

        if (str_contains(substr($compact, 0, max(0, $len - 1)), 'X')) {
            return self::pack([
                'input' => $raw,
                'compact' => $compact,
                'length' => $len,
                'target' => 10,
                'status' => 'bad',
                'status_label' => 'Caractère incorrect',
                'kind' => '',
                'kind_label' => '',
                'valid' => false,
                'check_ok' => false,
                'expected_check' => null,
                'warnings' => ['Le X de l’ISBN-10 n’est autorisé qu’en dernière position, à la place du chiffre 10.'],
            ]);
        }

        if ($len > 13) {
            return self::pack([
                'input' => $raw,
                'compact' => $compact,
                'length' => $len,
                'target' => 13,
                'status' => 'bad',
                'status_label' => 'Trop de chiffres',
                'kind' => '',
                'kind_label' => '',
                'valid' => false,
                'check_ok' => false,
                'expected_check' => null,
                'warnings' => ['Un ISBN fait 10 ou 13 caractères. Vérifiez un copier-coller.'],
            ]);
        }

        if ($len === 10) {
            return self::analyse10($raw, $compact);
        }
        if ($len === 13) {
            return self::analyse13($raw, $compact);
        }
        if ($len === 12 && self::starts978or979($compact)) {
            $expected = self::check13($compact . '0');
            return self::pack([
                'input' => $raw,
                'compact' => $compact,
                'length' => 12,
                'target' => 13,
                'status' => 'partial',
                'status_label' => 'Clé attendue : ' . $expected,
                'kind' => 'isbn13',
                'kind_label' => 'ISBN-13 incomplet',
                'valid' => false,
                'check_ok' => null,
                'expected_check' => $expected,
                'hyphenated' => self::hyphenate13($compact . $expected),
                'notices' => ['Encore un chiffre : la clé de contrôle devrait être ' . $expected . '.'],
            ]);
        }
        if ($len === 9 && !str_starts_with($compact, '978') && !str_starts_with($compact, '979')) {
            $expected = self::check10($compact . '0');
            return self::pack([
                'input' => $raw,
                'compact' => $compact,
                'length' => 9,
                'target' => 10,
                'status' => 'partial',
                'status_label' => 'Clé attendue : ' . $expected,
                'kind' => 'isbn10',
                'kind_label' => 'ISBN-10 incomplet',
                'valid' => false,
                'check_ok' => null,
                'expected_check' => $expected,
                'hyphenated' => self::hyphenate10($compact . $expected),
                'notices' => ['Encore un caractère : la clé de contrôle devrait être ' . $expected . '.'],
            ]);
        }

        $target = ($len >= 11 || self::starts978or979($compact)) ? 13 : 10;
        return self::pack([
            'input' => $raw,
            'compact' => $compact,
            'length' => $len,
            'target' => $target,
            'status' => 'partial',
            'status_label' => $len . ' / ' . $target . ' caractères',
            'kind' => $target === 13 ? 'isbn13' : 'isbn10',
            'kind_label' => $target === 13 ? 'ISBN-13 incomplet' : 'ISBN-10 incomplet',
            'valid' => false,
            'check_ok' => null,
            'expected_check' => null,
            'notices' => ['Continuez la saisie : ' . ($target - $len) . ' caractère' . ($target - $len > 1 ? 's' : '') . ' encore.'],
        ]);
    }

    public static function compact(string $raw): string
    {
        $raw = self::stripLabel($raw);
        $raw = strtoupper(str_replace(["\u{2013}", "\u{2014}", "\u{2212}"], '-', $raw));
        $raw = preg_replace('/[^0-9X]/', '', $raw) ?? '';
        return $raw;
    }

    /**
     * @return array<string, mixed>
     */
    public static function jsConfig(): array
    {
        return [
            'default' => self::DEFAULT,
            'groups' => self::groupPrefixes(),
            'registrant' => self::registrantRules(),
            'hints' => self::groupHints(),
        ];
    }

    /**
     * @return list<array{q: string, a: string}>
     */
    public static function faqs(): array
    {
        return [
            [
                'q' => 'ISBN ou EAN : quelle différence ?',
                'a' => 'L’ISBN-13 est un EAN-13 du livre : le code-barres de la 4e reprend exactement ces 13 chiffres. Les préfixes 978 et 979 (« Bookland ») disent qu’il s’agit d’un livre. Un EAN d’un autre préfixe identifie un produit, pas un ouvrage.',
            ],
            [
                'q' => 'Comment se calcule la clé de contrôle ?',
                'a' => 'ISBN-13 : on pèse les 12 premiers chiffres 1, 3, 1, 3… La clé complète la somme pour un multiple de 10. ISBN-10 : pondération 10 à 2 ; le reste à 11 donne la clé, écrite X si elle vaut 10. Un scanner refuse une clé fausse.',
            ],
            [
                'q' => 'Faut-il encore parler d’ISBN-10 ?',
                'a' => 'L’ISBN-10 reste sur d’anciennes éditions et dans quelques bases. Depuis 2007, l’identifiant courant est l’ISBN-13. Un 978 se convertit dans les deux sens ; un 979 n’a pas d’équivalent à 10 chiffres.',
            ],
            [
                'q' => 'Un même livre peut-il avoir un seul ISBN ?',
                'a' => 'Non : un ISBN identifie une édition précise. Broché, poche, grand format et e-pub prennent chacun le leur. Une réimpression à l’identique conserve le numéro ; une édition nettement remaniée en prend un autre.',
            ],
            [
                'q' => 'Acteurs du Livre attribue-t-il des ISBN ?',
                'a' => 'Non. En zone francophone, l’AFNIL attribue les listes aux éditeurs déclarés. Un autoédité passe souvent par un intermédiaire (quelques dizaines d’euros). Cet outil vérifie une clé, il n’en délivre pas.',
            ],
        ];
    }

    public static function barcodeSvg(string $digits): string
    {
        if (!preg_match('/^\d{13}$/', $digits)) {
            return '';
        }
        $modules = self::eanModules($digits);
        if ($modules === '') {
            return '';
        }
        $quiet = 7;
        $width = $quiet + strlen($modules) + $quiet;
        $barH = 58;
        $guardH = 64;
        $rects = '';
        $len = strlen($modules);
        for ($i = 0; $i < $len; $i++) {
            if ($modules[$i] !== '1') {
                continue;
            }
            $h = self::eanGuard($i) ? $guardH : $barH;
            $rects .= '<rect x="' . ($quiet + $i) . '" y="0" width="1" height="' . $h . '"/>';
        }
        $left = substr($digits, 1, 6);
        $right = substr($digits, 7, 6);
        $firstX = 3.2;
        $leftX = $quiet + 3 + 18;
        $rightX = $quiet + 3 + 42 + 5 + 18;
        return '<svg class="tool-isbn-svg" viewBox="0 0 ' . $width . ' 80" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Code-barres EAN-13 ' . $digits . '">'
            . '<g fill="currentColor">' . $rects . '</g>'
            . '<text x="' . $firstX . '" y="76" text-anchor="middle" fill="currentColor" class="tool-isbn-ocr">' . $digits[0] . '</text>'
            . '<text x="' . $leftX . '" y="76" text-anchor="middle" fill="currentColor" class="tool-isbn-ocr">' . $left . '</text>'
            . '<text x="' . $rightX . '" y="76" text-anchor="middle" fill="currentColor" class="tool-isbn-ocr">' . $right . '</text>'
            . '</svg>';
    }

    private static function analyse10(string $raw, string $compact): array
    {
        $expected = self::check10($compact);
        $ok = $compact[9] === $expected;
        $isbn13 = null;
        $isbn13Hyphen = '';
        if ($ok) {
            $isbn13 = '978' . substr($compact, 0, 9) . self::check13('978' . substr($compact, 0, 9) . '0');
            $isbn13Hyphen = self::hyphenate13($isbn13);
        }
        $hyphen = self::hyphenate10($compact);
        $ean = $ok ? $isbn13 : null;
        $warnings = [];
        $notices = ['ISBN-10 encore rencontré sur d’anciennes éditions. L’identifiant courant est l’ISBN-13.'];
        if (!$ok) {
            $warnings[] = 'Clé invalide : le caractère de contrôle devrait être ' . $expected . '.';
            $notices = [];
        } else {
            $notices[] = 'Un format = un ISBN. Broché, poche et e-pub prennent chacun le leur.';
        }

        return self::pack([
            'input' => $raw,
            'compact' => $compact,
            'length' => 10,
            'target' => 10,
            'status' => $ok ? 'ok' : 'bad',
            'status_label' => $ok ? 'Clé valide' : 'Clé invalide',
            'kind' => 'isbn10',
            'kind_label' => 'ISBN-10',
            'valid' => $ok,
            'check_ok' => $ok,
            'expected_check' => $expected,
            'hyphenated' => $hyphen,
            'isbn10' => $compact,
            'isbn10_hyphen' => $hyphen,
            'isbn13' => $isbn13,
            'isbn13_hyphen' => $isbn13Hyphen,
            'ean' => $ean,
            'parts' => self::parts10($compact),
            'warnings' => $warnings,
            'notices' => $notices,
        ]);
    }

    private static function analyse13(string $raw, string $compact): array
    {
        if (!ctype_digit($compact)) {
            return self::pack([
                'input' => $raw,
                'compact' => $compact,
                'length' => 13,
                'target' => 13,
                'status' => 'bad',
                'status_label' => 'Caractère incorrect',
                'kind' => '',
                'kind_label' => '',
                'valid' => false,
                'check_ok' => false,
                'expected_check' => null,
                'warnings' => ['L’ISBN-13 ne contient que des chiffres, pas de X.'],
            ]);
        }

        $expected = self::check13($compact);
        $ok = $compact[12] === $expected;
        $prefix = substr($compact, 0, 3);
        $isBook = $prefix === '978' || $prefix === '979';
        $isIsmn = str_starts_with($compact, '9790');
        $kind = 'ean';
        $kindLabel = 'EAN-13';
        if ($isIsmn) {
            $kind = 'ismn';
            $kindLabel = 'ISMN';
        } elseif ($isBook) {
            $kind = 'isbn13';
            $kindLabel = 'ISBN-13 · EAN livre';
        }

        $hyphen = $isBook
            ? self::hyphenate13($compact)
            : (substr($compact, 0, 3) . '-' . substr($compact, 3, 9) . '-' . $compact[12]);
        $isbn10 = null;
        $isbn10Hyphen = '';
        $warnings = [];
        $notices = [];

        if (!$ok) {
            $warnings[] = 'Clé invalide : le chiffre de contrôle devrait être ' . $expected . '.';
        } elseif ($isIsmn) {
            $notices[] = '979-0 identifie une partition (ISMN), pas un livre.';
        } elseif (!$isBook) {
            $warnings[] = 'Ce code-barres EAN n’est pas un ISBN : un livre commence par 978 ou 979.';
        } else {
            $notices[] = 'L’ISBN-13 et le code-barres de la 4e sont le même nombre.';
            $notices[] = 'Un format = un ISBN. Broché, poche et e-pub prennent chacun le leur.';
            if ($prefix === '979') {
                $notices[] = 'Les ISBN en 979 n’ont pas d’équivalent ISBN-10.';
            }
        }

        if ($ok && $prefix === '978') {
            $isbn10 = substr($compact, 3, 9) . self::check10(substr($compact, 3, 9) . '0');
            $isbn10Hyphen = self::hyphenate10($isbn10);
        }

        return self::pack([
            'input' => $raw,
            'compact' => $compact,
            'length' => 13,
            'target' => 13,
            'status' => $ok ? ($isBook && !$isIsmn ? 'ok' : ($ok && !$isBook ? 'warn' : 'ok')) : 'bad',
            'status_label' => !$ok ? 'Clé invalide' : ($isBook && !$isIsmn ? 'Clé valide' : ($isIsmn ? 'ISMN valide' : 'EAN valide, pas un ISBN')),
            'kind' => $kind,
            'kind_label' => $kindLabel,
            'valid' => $ok && $isBook && !$isIsmn,
            'check_ok' => $ok,
            'expected_check' => $expected,
            'hyphenated' => $hyphen,
            'isbn13' => $isBook ? $compact : null,
            'isbn13_hyphen' => $isBook ? $hyphen : '',
            'isbn10' => $isbn10,
            'isbn10_hyphen' => $isbn10Hyphen,
            'ean' => $ok ? $compact : null,
            'parts' => $isBook ? self::parts13($compact) : self::partsEan($compact),
            'warnings' => $warnings,
            'notices' => $notices,
        ]);
    }

    /**
     * @param array<string, mixed> $base
     * @return array<string, mixed>
     */
    private static function pack(array $base): array
    {
        $ean = isset($base['ean']) && is_string($base['ean']) ? $base['ean'] : '';
        $isbn13 = isset($base['isbn13']) && is_string($base['isbn13']) ? $base['isbn13'] : '';
        $isbn10 = isset($base['isbn10']) && is_string($base['isbn10']) ? $base['isbn10'] : '';
        $hyphen = (string) ($base['hyphenated'] ?? '');
        $status = (string) ($base['status'] ?? 'empty');
        $lines = [];
        if ($hyphen !== '') {
            $lines[] = (string) ($base['kind_label'] ?? 'ISBN') . ' : ' . $hyphen;
        }
        if ($isbn13 !== '' && ($base['kind'] ?? '') !== 'isbn13') {
            $lines[] = 'ISBN-13 : ' . (string) ($base['isbn13_hyphen'] ?: $isbn13);
        }
        if ($isbn10 !== '') {
            $lines[] = 'ISBN-10 : ' . (string) ($base['isbn10_hyphen'] ?: $isbn10);
        }
        if ($ean !== '') {
            $lines[] = 'EAN : ' . $ean;
        }
        $check = $base['check_ok'] ?? null;
        if ($check === true) {
            $lines[] = 'Clé : valide';
        } elseif ($check === false && isset($base['expected_check'])) {
            $lines[] = 'Clé attendue : ' . (string) $base['expected_check'];
        }
        $summary = $lines !== [] ? implode("\n", $lines) : '';

        return array_merge([
            'input' => '',
            'compact' => '',
            'length' => 0,
            'target' => 13,
            'status' => 'empty',
            'status_label' => '',
            'kind' => '',
            'kind_label' => '',
            'valid' => false,
            'check_ok' => null,
            'expected_check' => null,
            'hyphenated' => '',
            'isbn13' => null,
            'isbn13_hyphen' => '',
            'isbn10' => null,
            'isbn10_hyphen' => '',
            'ean' => null,
            'parts' => [],
            'warnings' => [],
            'notices' => [],
            'summary' => $summary,
            'barcode_svg' => ($status !== 'bad' && strlen($ean) === 13) ? self::barcodeSvg($ean) : '',
            'progress' => ((int) ($base['length'] ?? 0)) . ' / ' . ((int) ($base['target'] ?? 13)),
        ], $base, [
            'summary' => $summary,
            'barcode_svg' => ($status !== 'bad' && strlen($ean) === 13) ? self::barcodeSvg($ean) : '',
            'progress' => ((int) ($base['length'] ?? 0)) . ' / ' . ((int) ($base['target'] ?? 13)),
        ]);
    }

    /**
     * @return list<array{id: string, label: string, value: string, hint: string}>
     */
    private static function parts13(string $digits): array
    {
        $split = self::split13($digits);
        $prefix = $split['prefix'];
        $group = $split['group'];
        $registrant = $split['registrant'];
        $publication = $split['publication'];
        $check = $split['check'];
        $key = $prefix . '-' . $group;
        $hints = self::groupHints();

        $parts = [
            ['id' => 'prefix', 'label' => 'Préfixe', 'value' => $prefix, 'hint' => $prefix === '979' ? 'Bookland 979' : 'Bookland 978'],
            ['id' => 'group', 'label' => 'Groupe', 'value' => $group, 'hint' => $hints[$key] ?? 'Agence ISBN'],
        ];
        if ($registrant !== '') {
            $parts[] = ['id' => 'registrant', 'label' => 'Éditeur', 'value' => $registrant, 'hint' => 'Identifiant d’éditeur'];
        }
        if ($publication !== '') {
            $parts[] = ['id' => 'publication', 'label' => 'Publication', 'value' => $publication, 'hint' => 'Titre / format'];
        }
        $parts[] = ['id' => 'check', 'label' => 'Clé', 'value' => $check, 'hint' => 'Contrôle'];
        return $parts;
    }

    /**
     * @return list<array{id: string, label: string, value: string, hint: string}>
     */
    private static function parts10(string $digits): array
    {
        $split = self::split10($digits);
        $group = $split['group'];
        $key = '978-' . $group;
        $hints = self::groupHints();
        $parts = [
            ['id' => 'group', 'label' => 'Groupe', 'value' => $group, 'hint' => $hints[$key] ?? 'Agence ISBN'],
        ];
        if ($split['registrant'] !== '') {
            $parts[] = ['id' => 'registrant', 'label' => 'Éditeur', 'value' => $split['registrant'], 'hint' => 'Identifiant d’éditeur'];
        }
        if ($split['publication'] !== '') {
            $parts[] = ['id' => 'publication', 'label' => 'Publication', 'value' => $split['publication'], 'hint' => 'Titre / format'];
        }
        $parts[] = ['id' => 'check', 'label' => 'Clé', 'value' => $split['check'], 'hint' => 'Contrôle'];
        return $parts;
    }

    /**
     * @return list<array{id: string, label: string, value: string, hint: string}>
     */
    private static function partsEan(string $digits): array
    {
        return [
            ['id' => 'prefix', 'label' => 'Pays / GS1', 'value' => substr($digits, 0, 3), 'hint' => 'Préfixe EAN'],
            ['id' => 'publication', 'label' => 'Article', 'value' => substr($digits, 3, 9), 'hint' => 'Identifiant produit'],
            ['id' => 'check', 'label' => 'Clé', 'value' => $digits[12], 'hint' => 'Contrôle'],
        ];
    }

    public static function hyphenate13(string $digits): string
    {
        $digits = self::compact($digits);
        if (strlen($digits) !== 13) {
            return $digits;
        }
        $split = self::split13($digits);
        $chunks = array_values(array_filter([
            $split['prefix'],
            $split['group'],
            $split['registrant'],
            $split['publication'],
            $split['check'],
        ], static fn (string $part): bool => $part !== ''));
        return implode('-', $chunks);
    }

    public static function hyphenate10(string $digits): string
    {
        $digits = self::compact($digits);
        if (strlen($digits) !== 10) {
            return $digits;
        }
        $split = self::split10($digits);
        $chunks = array_values(array_filter([
            $split['group'],
            $split['registrant'],
            $split['publication'],
            $split['check'],
        ], static fn (string $part): bool => $part !== ''));
        return implode('-', $chunks);
    }

    /**
     * @return array{prefix: string, group: string, registrant: string, publication: string, check: string}
     */
    public static function split13(string $digits): array
    {
        $prefix = substr($digits, 0, 3);
        $check = substr($digits, 12, 1);
        $body = substr($digits, 3, 9);
        $group = self::matchGroup($prefix, $body);
        $rest = $group !== '' ? substr($body, strlen($group)) : $body;
        $registrant = '';
        $publication = $rest;
        if ($group !== '') {
            $len = self::matchRegistrant($prefix . '-' . $group, $rest);
            if ($len !== null && $len > 0 && $len < strlen($rest)) {
                $registrant = substr($rest, 0, $len);
                $publication = substr($rest, $len);
            } elseif ($len !== null && $len === strlen($rest)) {
                $registrant = $rest;
                $publication = '';
            }
        }
        return [
            'prefix' => $prefix,
            'group' => $group,
            'registrant' => $registrant,
            'publication' => $publication,
            'check' => $check,
        ];
    }

    /**
     * @return array{group: string, registrant: string, publication: string, check: string}
     */
    public static function split10(string $digits): array
    {
        $check = substr($digits, 9, 1);
        $body = substr($digits, 0, 9);
        $group = self::matchGroup('978', $body);
        $rest = $group !== '' ? substr($body, strlen($group)) : $body;
        $registrant = '';
        $publication = $rest;
        if ($group !== '') {
            $len = self::matchRegistrant('978-' . $group, $rest);
            if ($len !== null && $len > 0 && $len < strlen($rest)) {
                $registrant = substr($rest, 0, $len);
                $publication = substr($rest, $len);
            }
        }
        return [
            'group' => $group,
            'registrant' => $registrant,
            'publication' => $publication,
            'check' => $check,
        ];
    }

    public static function check13(string $digits): string
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $n = (int) $digits[$i];
            $sum += ($i % 2 === 0) ? $n : $n * 3;
        }
        return (string) ((10 - ($sum % 10)) % 10);
    }

    public static function check10(string $digits): string
    {
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += ((int) $digits[$i]) * (10 - $i);
        }
        $mod = $sum % 11;
        $check = (11 - $mod) % 11;
        return $check === 10 ? 'X' : (string) $check;
    }

    private static function matchGroup(string $prefix, string $body): string
    {
        $groups = self::groupPrefixes()[$prefix] ?? [];
        usort($groups, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        foreach ($groups as $group) {
            if (str_starts_with($body, $group)) {
                return $group;
            }
        }
        return '';
    }

    private static function matchRegistrant(string $key, string $rest): ?int
    {
        $rules = self::registrantRules()[$key] ?? [];
        foreach ($rules as $rule) {
            $from = $rule['from'];
            $width = strlen($from);
            if (strlen($rest) < $width) {
                continue;
            }
            $slice = substr($rest, 0, $width);
            if ($slice >= $from && $slice <= $rule['to']) {
                return (int) $rule['len'];
            }
        }
        return null;
    }

    private static function starts978or979(string $compact): bool
    {
        return str_starts_with($compact, '978') || str_starts_with($compact, '979');
    }

    private static function stripLabel(string $raw): string
    {
        $raw = trim($raw);
        $raw = preg_replace('/^\s*(?:ISBN(?:-1[03])?|EAN(?:-13)?|GTIN)\s*:?\s*/i', '', $raw) ?? $raw;
        return trim($raw);
    }

    private static function clip(string $raw): string
    {
        $raw = trim($raw);
        if (mb_strlen($raw) > self::RAW_MAX) {
            $raw = mb_substr($raw, 0, self::RAW_MAX);
        }
        return $raw;
    }

    private static function eanModules(string $digits): string
    {
        $l = [
            '0' => '0001101', '1' => '0011001', '2' => '0010011', '3' => '0111101', '4' => '0100011',
            '5' => '0110001', '6' => '0101111', '7' => '0111011', '8' => '0110111', '9' => '0001011',
        ];
        $g = [
            '0' => '0100111', '1' => '0110011', '2' => '0011011', '3' => '0100001', '4' => '0011101',
            '5' => '0111001', '6' => '0000101', '7' => '0010001', '8' => '0001001', '9' => '0010111',
        ];
        $r = [
            '0' => '1110010', '1' => '1100110', '2' => '1101100', '3' => '1000010', '4' => '1011100',
            '5' => '1001110', '6' => '1010000', '7' => '1000100', '8' => '1001000', '9' => '1110100',
        ];
        $parity = [
            '0' => 'LLLLLL', '1' => 'LLGLGG', '2' => 'LLGGLG', '3' => 'LLGGGL', '4' => 'LGLLGG',
            '5' => 'LGGLLG', '6' => 'LGGGLL', '7' => 'LGLGLG', '8' => 'LGLGGL', '9' => 'LGGLGL',
        ];
        $first = $digits[0];
        $pattern = $parity[$first] ?? '';
        if ($pattern === '') {
            return '';
        }
        $out = '101';
        for ($i = 0; $i < 6; $i++) {
            $d = $digits[$i + 1];
            $out .= $pattern[$i] === 'G' ? $g[$d] : $l[$d];
        }
        $out .= '01010';
        for ($i = 7; $i < 13; $i++) {
            $out .= $r[$digits[$i]];
        }
        $out .= '101';
        return $out;
    }

    private static function eanGuard(int $i): bool
    {
        return $i < 3 || ($i >= 45 && $i < 50) || $i >= 92;
    }
}
