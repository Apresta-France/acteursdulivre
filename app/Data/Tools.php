<?php

declare(strict_types=1);

namespace Adl\Data;

final class Tools
{
    public const PREFIX = '/outils';
    public const FEUILLET_SIGNES = 1500;
    public const SIGNS_PER_WORD = 6.0;
    public const READ_WPM = 230;
    public const AUDIO_WPM = 155;
    public const CORRECTION_SAMPLE_SIGNES = 520000;
    public const CORRECTION_LOW = 620;
    public const CORRECTION_HIGH = 1100;
    public const TRANSLATION_LOW = 0.12;
    public const TRANSLATION_HIGH = 0.22;
    public const AMOUNT_MAX = 100000000;
    public const RATE_MAX = 10000;
    public const TEXT_MAX = 500000;

    /** @var list<string> */
    public const UNITS = ['signes', 'signes-nc', 'mots', 'feuillets'];

    /** @var list<string> */
    public const RATE_UNITS = ['feuillets', 'mille', 'mots'];

    /**
     * @return list<array{
     *   slug: string,
     *   title: string,
     *   kicker: string,
     *   lead: string,
     *   href: string,
     *   icon: string,
     *   available: bool
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'slug' => 'volume',
                'title' => 'Signes et feuillets',
                'kicker' => 'Volume',
                'lead' => 'Collez un texte ou un nombre. Obtenez signes, feuillets, mots, pages et durées — le décompte reste dans votre navigateur.',
                'href' => self::path('volume'),
                'icon' => 'counter',
                'available' => true,
            ],
            [
                'slug' => 'dos',
                'title' => 'Dos et couverture',
                'kicker' => 'Fabrication',
                'lead' => 'Format, pagination, papier : largeur de dos, fonds perdus et taille du PDF couverture. L’imprimeur a le dernier mot.',
                'href' => self::path('dos'),
                'icon' => 'trade-reliure',
                'available' => true,
            ],
            [
                'slug' => 'isbn',
                'title' => 'ISBN et EAN',
                'kicker' => 'Identifiant',
                'lead' => 'Vérifiez la clé, voyez la structure, convertissez ISBN-10 et 13. Le code-barres de la 4e, sans attribution.',
                'href' => self::path('isbn'),
                'icon' => 'barcode',
                'available' => true,
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function find(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }
        foreach (self::all() as $tool) {
            if ($tool['slug'] === $slug) {
                return $tool;
            }
        }
        return null;
    }

    public static function path(?string $slug = null): string
    {
        if ($slug === null || $slug === '') {
            return self::PREFIX;
        }
        return self::PREFIX . '/' . ltrim($slug, '/');
    }

    /**
     * @return array<string, string>
     */
    public static function unitLabels(): array
    {
        return [
            'signes' => 'Signes espaces compris',
            'signes-nc' => 'Signes espaces non compris',
            'mots' => 'Mots',
            'feuillets' => 'Feuillets (1 500 signes)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function rateUnitLabels(): array
    {
        return [
            'feuillets' => '€ / feuillet',
            'mille' => '€ / 1 000 signes',
            'mots' => '€ / mot',
        ];
    }

    /**
     * @return array{
     *   sec: int,
     *   senc: int,
     *   words: int,
     *   feuillets: float,
     *   read_min: float,
     *   audio_min: float
     * }
     */
    public static function countText(string $text): array
    {
        $text = self::normalizeText($text);
        if ($text === '') {
            return self::emptyStats();
        }
        $sec = mb_strlen($text);
        $senc = mb_strlen(preg_replace('/\s+/u', '', $text) ?? $text);
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = is_array($words) ? count($words) : 0;

        return self::fromSecSencWords($sec, $senc, $wordCount);
    }

    /**
     * @return array{
     *   sec: int,
     *   senc: int,
     *   words: int,
     *   feuillets: float,
     *   read_min: float,
     *   audio_min: float
     * }|null
     */
    public static function fromAmount(float $amount, string $unit): ?array
    {
        if ($amount < 0 || $amount > self::AMOUNT_MAX) {
            return null;
        }
        $unit = self::normalizeUnit($unit);
        if ($amount == 0.0) {
            return self::emptyStats();
        }

        $sec = 0.0;
        $senc = 0.0;
        $words = 0.0;
        $ratio = (self::SIGNS_PER_WORD - 1) / self::SIGNS_PER_WORD;

        switch ($unit) {
            case 'signes-nc':
                $senc = $amount;
                $sec = $amount / $ratio;
                $words = $amount / (self::SIGNS_PER_WORD - 1);
                break;
            case 'mots':
                $words = $amount;
                $sec = $amount * self::SIGNS_PER_WORD;
                $senc = $amount * (self::SIGNS_PER_WORD - 1);
                break;
            case 'feuillets':
                $sec = $amount * self::FEUILLET_SIGNES;
                $senc = $sec * $ratio;
                $words = $sec / self::SIGNS_PER_WORD;
                break;
            default:
                $sec = $amount;
                $senc = $amount * $ratio;
                $words = $amount / self::SIGNS_PER_WORD;
                break;
        }

        return self::fromSecSencWords((int) round($sec), (int) round($senc), (int) round($words));
    }

    public static function parseAmount(string $raw): ?float
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $raw = str_replace(["\u{00A0}", ' '], '', $raw);
        $raw = str_replace(',', '.', $raw);
        if (!is_numeric($raw)) {
            return null;
        }
        $value = (float) $raw;
        if ($value < 0 || $value > self::AMOUNT_MAX) {
            return null;
        }
        return $value;
    }

    public static function normalizeUnit(string $unit): string
    {
        $unit = trim($unit);
        return in_array($unit, self::UNITS, true) ? $unit : 'signes';
    }

    public static function parseRate(string $raw): ?float
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $raw = str_replace(["\u{00A0}", ' '], '', $raw);
        $raw = str_replace(',', '.', $raw);
        if (!is_numeric($raw)) {
            return null;
        }
        $value = (float) $raw;
        if ($value <= 0 || $value > self::RATE_MAX) {
            return null;
        }
        return $value;
    }

    public static function normalizeRateUnit(string $unit): string
    {
        $unit = trim($unit);
        return in_array($unit, self::RATE_UNITS, true) ? $unit : 'feuillets';
    }

    /**
     * @param array{sec: int, senc: int, words: int, feuillets: float, read_min: float, audio_min: float} $stats
     */
    public static function simulate(array $stats, float $rate, string $rateUnit): ?float
    {
        if ($rate <= 0 || $rate > self::RATE_MAX) {
            return null;
        }
        $qty = match (self::normalizeRateUnit($rateUnit)) {
            'mots' => (float) ($stats['words'] ?? 0),
            'mille' => ((int) ($stats['sec'] ?? 0)) / 1000,
            default => (float) ($stats['feuillets'] ?? 0),
        };
        if ($qty <= 0) {
            return null;
        }
        return $qty * $rate;
    }

    public static function formatEuros(float $n): string
    {
        if ($n <= 0) {
            return '—';
        }
        if ($n < 20 && abs($n - round($n)) >= 0.005) {
            return number_format($n, 2, ',', ' ') . ' €';
        }
        return format_int((int) round($n)) . ' €';
    }

    public static function formatNumber(float $n, int $maxDecimals = 1): string
    {
        if (abs($n - round($n)) < 0.05) {
            return format_int((int) round($n));
        }
        return number_format($n, $maxDecimals, ',', ' ');
    }

    public static function formatDuration(float $minutes): string
    {
        if ($minutes <= 0) {
            return '—';
        }
        if ($minutes < 1) {
            return 'moins d’1 min';
        }
        if ($minutes < 60) {
            return (string) (int) round($minutes) . ' min';
        }
        $hours = (int) floor($minutes / 60);
        $rest = (int) round($minutes - ($hours * 60));
        if ($rest === 60) {
            $hours++;
            $rest = 0;
        }
        if ($rest === 0) {
            return format_int($hours) . ' h';
        }
        return format_int($hours) . ' h ' . $rest . ' min';
    }

    /**
     * @param array{sec: int, senc: int, words: int, feuillets: float, read_min: float, audio_min: float} $stats
     * @return array{low: int, high: int}|null
     */
    public static function correctionRange(array $stats): ?array
    {
        $sec = (int) ($stats['sec'] ?? 0);
        if ($sec < 8000) {
            return null;
        }
        $low = (int) round($sec * self::CORRECTION_LOW / self::CORRECTION_SAMPLE_SIGNES);
        $high = (int) round($sec * self::CORRECTION_HIGH / self::CORRECTION_SAMPLE_SIGNES);
        if ($low < 1 || $high < $low) {
            return null;
        }
        return ['low' => $low, 'high' => $high];
    }

    /**
     * @param array{sec: int, senc: int, words: int, feuillets: float, read_min: float, audio_min: float} $stats
     * @return array{low: int, high: int}|null
     */
    public static function translationRange(array $stats): ?array
    {
        $words = (int) ($stats['words'] ?? 0);
        if ($words < 1500) {
            return null;
        }
        $low = (int) round($words * self::TRANSLATION_LOW);
        $high = (int) round($words * self::TRANSLATION_HIGH);
        if ($low < 1 || $high < $low) {
            return null;
        }
        return ['low' => $low, 'high' => $high];
    }

    /**
     * @return array<string, mixed>
     */
    public static function jsConfig(): array
    {
        return [
            'feuillet' => self::FEUILLET_SIGNES,
            'signsPerWord' => self::SIGNS_PER_WORD,
            'readWpm' => self::READ_WPM,
            'audioWpm' => self::AUDIO_WPM,
            'correctionSample' => self::CORRECTION_SAMPLE_SIGNES,
            'correctionLow' => self::CORRECTION_LOW,
            'correctionHigh' => self::CORRECTION_HIGH,
            'translationLow' => self::TRANSLATION_LOW,
            'translationHigh' => self::TRANSLATION_HIGH,
            'amountMax' => self::AMOUNT_MAX,
            'rateMax' => self::RATE_MAX,
            'textMax' => self::TEXT_MAX,
        ];
    }

    /**
     * @return list<array{q: string, a: string}>
     */
    public static function volumeFaqs(): array
    {
        return [
            [
                'q' => 'Qu’est-ce qu’un feuillet ?',
                'a' => 'En France, le feuillet de référence fait 1 500 signes espaces compris. C’est l’unité des devis de correction, de préparation de copie et souvent de traduction. Un roman courant d’environ 520 000 signes, c’est un peu moins de 350 feuillets.',
            ],
            [
                'q' => 'Signes espaces compris ou non compris ?',
                'a' => 'Les devis du livre se chiffrent presque toujours en signes espaces compris (SEC) : lettres, espaces, ponctuation. Les signes non compris (SENC) servent surtout à contrôler un volume « nu ». Si un prestataire ne précise pas, demandez-le avant d’accepter.',
            ],
            [
                'q' => 'Le texte collé est-il envoyé quelque part ?',
                'a' => 'Non. Le décompte d’un texte collé se fait dans votre navigateur. Rien n’est enregistré, ni transmis. Un nombre tapé dans le champ « partir d’un nombre » peut figurer dans l’adresse de la page, pour partager le résultat.',
            ],
            [
                'q' => 'Les durées et les prix sont-ils contractuels ?',
                'a' => 'Non. Lecture silencieuse vers 230 mots/min, narration vers 155 mots/min : ce sont des ordres de grandeur. Les fourchettes de correction et de traduction reprennent les pages métier du site, pas un tarif d’Acteurs du Livre. Le devis se fait sur votre fichier.',
            ],
            [
                'q' => 'Comment simuler un tarif ?',
                'a' => 'Dans la colonne de droite, indiquez votre prix et l’unité : au feuillet (1 500 signes) pour la correction, au mot source pour la traduction, ou aux 1 000 signes. Le montant est volume × tarif, pas un devis. Un prestataire chiffre sur le fichier, le délai et le périmètre.',
            ],
        ];
    }

    public static function normalizeText(string $text): string
    {
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);
        if (mb_strlen($text) > self::TEXT_MAX) {
            $text = mb_substr($text, 0, self::TEXT_MAX);
        }
        return trim($text);
    }

    /**
     * @return array{sec: int, senc: int, words: int, feuillets: float, read_min: float, audio_min: float}
     */
    private static function emptyStats(): array
    {
        return [
            'sec' => 0,
            'senc' => 0,
            'words' => 0,
            'feuillets' => 0.0,
            'read_min' => 0.0,
            'audio_min' => 0.0,
        ];
    }

    /**
     * @return array{sec: int, senc: int, words: int, feuillets: float, read_min: float, audio_min: float}
     */
    private static function fromSecSencWords(int $sec, int $senc, int $words): array
    {
        $sec = max(0, $sec);
        $senc = max(0, $senc);
        $words = max(0, $words);

        return [
            'sec' => $sec,
            'senc' => $senc,
            'words' => $words,
            'feuillets' => $sec > 0 ? $sec / self::FEUILLET_SIGNES : 0.0,
            'read_min' => $words > 0 ? $words / self::READ_WPM : 0.0,
            'audio_min' => $words > 0 ? $words / self::AUDIO_WPM : 0.0,
        ];
    }
}
