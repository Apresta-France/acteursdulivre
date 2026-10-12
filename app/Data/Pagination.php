<?php

declare(strict_types=1);

namespace Adl\Data;

final class Pagination
{
    /** Point PostScript, en millimètres (25,4 / 72). */
    public const PT_MM = 25.4 / 72;
    /** Chasse moyenne d’un romain de labeur, espaces compris, en cadratins. */
    public const CHASSE = 0.45;
    /** Blanc moyen laissé par une ouverture de chapitre. */
    public const CHAPTER_PAGE = 0.5;
    public const DEFAULT_AMOUNT = 520000.0;
    public const EXTRA_DEFAULT = 8;
    public const CORPS_MIN = 7.0;
    public const CORPS_MAX = 18.0;
    public const LEADING_MIN = 8.0;
    public const LEADING_MAX = 28.0;
    public const EXTRA_MAX = 96;
    public const CHAPTERS_MAX = 400;
    public const LINE_LONG = 70;
    public const LINE_SHORT = 45;
    public const MIN_TEXT_W = 60.0;
    public const MIN_TEXT_H = 90.0;

    /**
     * @return array<string, array{
     *   label: string,
     *   corps: float,
     *   leading: float,
     *   inner: float,
     *   outer: float,
     *   head: float,
     *   foot: float,
     *   note: string
     * }>
     */
    public static function gabarits(): array
    {
        return [
            'roman' => [
                'label' => 'Roman courant (11 / 13,5)',
                'corps' => 11.0,
                'leading' => 13.5,
                'inner' => 16.0,
                'outer' => 22.0,
                'head' => 16.0,
                'foot' => 24.0,
                'note' => 'Corps 11 pt, interligne 13,5 pt. Marges de roman : petit fond 16 mm, grand fond 22 mm, tête 16 mm, pied 24 mm.',
            ],
            'poche' => [
                'label' => 'Poche serré (9 / 11)',
                'corps' => 9.0,
                'leading' => 11.0,
                'inner' => 11.0,
                'outer' => 13.0,
                'head' => 12.0,
                'foot' => 16.0,
                'note' => 'Corps 9 pt, interligne 11 pt. Marges plus justes, pour un 11 × 18.',
            ],
            'essai' => [
                'label' => 'Essai aéré (11 / 16)',
                'corps' => 11.0,
                'leading' => 16.0,
                'inner' => 20.0,
                'outer' => 26.0,
                'head' => 20.0,
                'foot' => 28.0,
                'note' => 'Corps 11 pt, interligne 16 pt. Marges plus larges : la page contient moins de signes.',
            ],
            'libre' => [
                'label' => 'Corps libre',
                'corps' => 11.0,
                'leading' => 13.5,
                'inner' => 16.0,
                'outer' => 22.0,
                'head' => 16.0,
                'foot' => 24.0,
                'note' => 'Marges du roman courant. La chasse reste 0,45 cadratin, espaces compris — une police très large ou très étroite déplace le résultat.',
            ],
        ];
    }

    /**
     * @return array{
     *   amount: float,
     *   unit: string,
     *   format: string,
     *   width: float,
     *   height: float,
     *   gabarit: string,
     *   corps: float,
     *   leading: float,
     *   extra: int,
     *   chapters: int
     * }
     */
    public static function defaults(): array
    {
        $gabarit = self::gabarits()['roman'];
        $format = Spine::formats()['roman'];

        return [
            'amount' => self::DEFAULT_AMOUNT,
            'unit' => 'signes',
            'format' => 'roman',
            'width' => $format['w'],
            'height' => $format['h'],
            'gabarit' => 'roman',
            'corps' => $gabarit['corps'],
            'leading' => $gabarit['leading'],
            'extra' => self::EXTRA_DEFAULT,
            'chapters' => 0,
        ];
    }

    /**
     * @return array{
     *   amount: float,
     *   unit: string,
     *   format: string,
     *   width: float,
     *   height: float,
     *   gabarit: string,
     *   corps: float,
     *   leading: float,
     *   extra: int,
     *   chapters: int
     * }
     */
    public static function fromQuery(
        string $amount,
        string $unit,
        string $format,
        string $width,
        string $height,
        string $gabarit,
        string $corps,
        string $leading,
        string $extra,
        string $chapters
    ): array {
        $base = self::defaults();
        $formats = Spine::formats();
        $gabarits = self::gabarits();
        $format = isset($formats[$format]) ? $format : $base['format'];
        $gabarit = isset($gabarits[$gabarit]) ? $gabarit : $base['gabarit'];
        $presetFormat = $formats[$format];
        $presetGabarit = $gabarits[$gabarit];

        $amountRaw = trim($amount);
        if ($amountRaw === '') {
            $amountValue = $base['amount'];
            $unit = $base['unit'];
        } else {
            $parsed = Tools::parseAmount($amountRaw);
            $amountValue = $parsed ?? 0.0;
            $unit = Tools::normalizeUnit($unit);
        }

        $w = $format === 'custom' ? (Spine::parseDecimal($width) ?? $presetFormat['w']) : $presetFormat['w'];
        $h = $format === 'custom' ? (Spine::parseDecimal($height) ?? $presetFormat['h']) : $presetFormat['h'];

        if ($gabarit === 'libre') {
            $corpsValue = Spine::parseDecimal($corps) ?? $presetGabarit['corps'];
            $leadingValue = Spine::parseDecimal($leading) ?? $presetGabarit['leading'];
        } else {
            $corpsValue = $presetGabarit['corps'];
            $leadingValue = $presetGabarit['leading'];
        }

        $extraRaw = trim($extra);
        $extraValue = $extraRaw === '' ? $base['extra'] : (int) round(Spine::parseDecimal($extra) ?? $base['extra']);
        $chapterRaw = trim($chapters);
        $chapterValue = $chapterRaw === '' ? 0 : (int) round(Spine::parseDecimal($chapters) ?? 0);

        return [
            'amount' => $amountValue,
            'unit' => $unit,
            'format' => $format,
            'width' => self::clamp($w, Spine::SIZE_MIN, Spine::SIZE_MAX),
            'height' => self::clamp($h, Spine::SIZE_MIN, Spine::SIZE_MAX),
            'gabarit' => $gabarit,
            'corps' => self::clamp($corpsValue, self::CORPS_MIN, self::CORPS_MAX),
            'leading' => self::clamp($leadingValue, self::LEADING_MIN, self::LEADING_MAX),
            'extra' => self::clampInt($extraValue, 0, self::EXTRA_MAX),
            'chapters' => self::clampInt($chapterValue, 0, self::CHAPTERS_MAX),
        ];
    }

    /**
     * @param array{
     *   amount: float,
     *   unit: string,
     *   format: string,
     *   width: float,
     *   height: float,
     *   gabarit: string,
     *   corps: float,
     *   leading: float,
     *   extra: int,
     *   chapters: int
     * } $input
     * @return array<string, mixed>
     */
    public static function compute(array $input): array
    {
        $gabarits = self::gabarits();
        $gabaritKey = (string) $input['gabarit'];
        $gabarit = $gabarits[$gabaritKey] ?? $gabarits['roman'];
        $stats = Tools::fromAmount((float) $input['amount'], (string) $input['unit']) ?? Tools::fromAmount(0, 'signes');
        $sec = (int) ($stats['sec'] ?? 0);

        $corps = (float) $input['corps'];
        $leading = (float) $input['leading'];
        $leadingClamped = false;
        if ($leading < $corps) {
            $leading = $corps;
            $leadingClamped = true;
        }

        $block = self::textBlock((float) $input['width'], (float) $input['height'], $gabarit);
        $chasse = $corps * self::PT_MM * self::CHASSE;
        $signsPerLine = (int) max(1, round($block['text_w'] / $chasse));
        $leadMm = $leading * self::PT_MM;
        $lines = (int) max(1, floor(($block['text_h'] / $leadMm) + 1e-9));
        $signsPerPage = $signsPerLine * $lines;

        $chapters = (int) $input['chapters'];
        $extra = (int) $input['extra'];
        $has = $sec > 0 && $signsPerPage > 0;
        $textPages = 0;
        $bookPages = 0;
        $sig16 = 0;
        $sig8 = 0;
        $sig4 = 0;
        if ($has) {
            $flow = ($sec / $signsPerPage) + ($chapters * self::CHAPTER_PAGE);
            $textPages = (int) ceil($flow - 1e-9);
            $bookPages = $textPages + $extra;
            $sig16 = self::pad($bookPages, 16);
            $sig8 = self::pad($bookPages, 8);
            $sig4 = self::pad($bookPages, 4);
        }

        $warnings = [];
        if ($signsPerLine > self::LINE_LONG) {
            $warnings[] = 'La ligne dépasse 70 signes : la lecture suivie fatigue. Élargissez les marges, baissez le corps, ou passez sur un format plus étroit.';
        } elseif ($signsPerLine < self::LINE_SHORT) {
            $warnings[] = 'La ligne est courte (moins de 45 signes). Le poche le supporte ; en grand format, réduisez les marges.';
        }
        if ($leadingClamped) {
            $warnings[] = 'L’interligne est inférieur au corps. Le calcul aligne l’interligne sur le corps.';
        }
        if ($block['scaled']) {
            $warnings[] = 'Les marges du gabarit ont été réduites : ce format est petit pour ces fonds.';
        }
        if ($has && ($sig16 - $bookPages) >= 8) {
            $blanks = $sig16 - $bookPages;
            $warnings[] = 'Le cahier de 16 ajoute ' . $blanks . ' pages blanches. Un cahier de 8, ou le numérique, colle mieux à ce volume.';
        }
        if ($textPages >= 700) {
            $warnings[] = 'Au-delà de 700 pages de texte, le façonnage et le prix public changent de nature. Vérifiez le dos.';
        }

        return [
            'sec' => $sec,
            'signs_per_line' => $signsPerLine,
            'lines' => $lines,
            'signs_per_page' => $signsPerPage,
            'text_pages' => $textPages,
            'book_pages' => $bookPages,
            'sig16' => $sig16,
            'sig8' => $sig8,
            'sig4' => $sig4,
            'text_w' => $block['text_w'],
            'text_h' => $block['text_h'],
            'inner' => $block['inner'],
            'outer' => $block['outer'],
            'head' => $block['head'],
            'foot' => $block['foot'],
            'corps' => $corps,
            'leading' => $leading,
            'has' => $has,
            'warnings' => $warnings,
            'note' => (string) $gabarit['note'],
            'summary' => self::summary($input, $signsPerLine, $lines, $signsPerPage, $textPages, $sig16, $has),
            'spine' => self::spinePath((string) $input['format'], (float) $input['width'], (float) $input['height'], $sig16),
        ];
    }

    public static function pad(int $pages, int $signature): int
    {
        if ($pages <= 0 || $signature <= 1) {
            return max(0, $pages);
        }
        $mod = $pages % $signature;
        if ($mod === 0) {
            return $pages;
        }
        return $pages + ($signature - $mod);
    }

    /**
     * @return array<string, mixed>
     */
    public static function jsConfig(): array
    {
        return [
            'formats' => Spine::formats(),
            'gabarits' => self::gabarits(),
            'ptMm' => self::PT_MM,
            'chasse' => self::CHASSE,
            'chapterPage' => self::CHAPTER_PAGE,
            'defaultAmount' => self::DEFAULT_AMOUNT,
            'defaultExtra' => self::EXTRA_DEFAULT,
            'amountMax' => Tools::AMOUNT_MAX,
            'corpsMin' => self::CORPS_MIN,
            'corpsMax' => self::CORPS_MAX,
            'leadingMin' => self::LEADING_MIN,
            'leadingMax' => self::LEADING_MAX,
            'extraMax' => self::EXTRA_MAX,
            'chaptersMax' => self::CHAPTERS_MAX,
            'sizeMin' => Spine::SIZE_MIN,
            'sizeMax' => Spine::SIZE_MAX,
            'lineLong' => self::LINE_LONG,
            'lineShort' => self::LINE_SHORT,
            'minTextW' => self::MIN_TEXT_W,
            'minTextH' => self::MIN_TEXT_H,
            'feuillet' => Tools::FEUILLET_SIGNES,
            'signsPerWord' => Tools::SIGNS_PER_WORD,
        ];
    }

    /**
     * @return list<array{q: string, a: string}>
     */
    public static function faqs(): array
    {
        return [
            [
                'q' => 'Comment passe-t-on des signes aux pages ?',
                'a' => 'Un romain de labeur (Garamond, Minion, Caslon) a une chasse moyenne d’environ 0,45 cadratin, espaces compris. Le bloc de texte se déduit du format et des marges du gabarit. Signes par ligne = largeur du bloc ÷ chasse. Lignes = hauteur du bloc ÷ interligne, vers le bas. Pages de texte = signes du manuscrit ÷ signes par page, arrondi au-dessus.',
            ],
            [
                'q' => 'Pourquoi Word ne donne-t-il pas le même nombre ?',
                'a' => 'Word compte des pages A4, souvent en Times 12, avec ses marges. Un roman 14 × 21 en 11/13,5 n’a ni la même surface, ni la même chasse. Le chiffre utile pour l’imprimeur est celui du format fini.',
            ],
            [
                'q' => 'Qu’est-ce qu’un cahier ?',
                'a' => 'En offset, les pages s’imposent par cahiers de 4, 8 ou 16. Un manuscrit qui tombe à 265 pages avec les hors-texte part souvent à 272 (cahier de 16) ou reste à 268 (cahier de 4). Le numérique est plus souple. L’imprimeur dit quel multiple il tient.',
            ],
            [
                'q' => 'Les 8 pages hors texte sont-elles obligatoires ?',
                'a' => 'Non. C’est un point de départ : faux-titre, titre, copyright, un blanc. Mettez 0 pour ne voir que le texte, ou 16 si vous avez une table, une préface, des remerciements.',
            ],
            [
                'q' => '520 000 signes, ça fait combien de pages ?',
                'a' => 'En roman 14 × 21, corps 11 sur 13,5, environ 257 pages de texte (autour de 2 030 signes par page), 265 avec 8 pages hors texte, 272 au cahier de 16. Une autre police déplace ce chiffre. Ce n’est pas une maquette.',
            ],
            [
                'q' => 'Ce nombre est-il envoyé quelque part ?',
                'a' => 'Non. Vous entrez un nombre de signes, de feuillets ou de mots. Rien n’est enregistré ni transmis. L’adresse peut contenir ce nombre pour partager le résultat. Pour décompter un texte collé, le compteur de signes le fait dans le navigateur.',
            ],
        ];
    }

    /**
     * @param array{inner: float, outer: float, head: float, foot: float} $gabarit
     * @return array{inner: float, outer: float, head: float, foot: float, text_w: float, text_h: float, scaled: bool}
     */
    private static function textBlock(float $width, float $height, array $gabarit): array
    {
        $inner = (float) $gabarit['inner'];
        $outer = (float) $gabarit['outer'];
        $head = (float) $gabarit['head'];
        $foot = (float) $gabarit['foot'];
        $scaled = false;

        $textW = $width - $inner - $outer;
        if ($textW < self::MIN_TEXT_W) {
            $available = $width - self::MIN_TEXT_W;
            $sum = $inner + $outer;
            if ($sum > 0 && $available > 16) {
                $factor = $available / $sum;
                $inner *= $factor;
                $outer *= $factor;
                $scaled = true;
            }
            $textW = $width - $inner - $outer;
        }

        $textH = $height - $head - $foot;
        if ($textH < self::MIN_TEXT_H) {
            $available = $height - self::MIN_TEXT_H;
            $sum = $head + $foot;
            if ($sum > 0 && $available > 20) {
                $factor = $available / $sum;
                $head *= $factor;
                $foot *= $factor;
                $scaled = true;
            }
            $textH = $height - $head - $foot;
        }

        return [
            'inner' => $inner,
            'outer' => $outer,
            'head' => $head,
            'foot' => $foot,
            'text_w' => max(1.0, $textW),
            'text_h' => max(1.0, $textH),
            'scaled' => $scaled,
        ];
    }

    public static function spinePath(string $format, float $width, float $height, int $pages): string
    {
        $params = [];
        if ($format !== 'roman') {
            $params['f'] = $format;
        }
        if ($format === 'custom') {
            $params['w'] = self::queryNumber($width);
            $params['h'] = self::queryNumber($height);
        }
        if ($pages > 0 && $pages !== 280) {
            $params['p'] = (string) $pages;
        }
        $path = Tools::path('dos');
        if ($params === []) {
            return $path;
        }
        return $path . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @param array<string, mixed> $input
     */
    private static function summary(
        array $input,
        int $signsPerLine,
        int $lines,
        int $signsPerPage,
        int $textPages,
        int $sig16,
        bool $has
    ): string {
        if (!$has) {
            return '';
        }
        $formats = Spine::formats();
        $formatKey = (string) ($input['format'] ?? 'roman');
        $formatLabel = (string) ($formats[$formatKey]['label'] ?? $formatKey);
        $unit = (string) ($input['unit'] ?? 'signes');
        $unitLabel = match ($unit) {
            'feuillets' => 'feuillets',
            'mots' => 'mots',
            'signes-nc' => 'signes hors espaces',
            default => 'signes',
        };
        $decimals = $unit === 'feuillets' ? 1 : 0;
        $line = Tools::formatNumber((float) $input['amount'], $decimals)
            . ' ' . $unitLabel
            . ' · ' . $formatLabel
            . ' · ' . Tools::formatNumber((float) $input['corps'], 1)
            . ' / ' . Tools::formatNumber((float) $input['leading'], 1)
            . ' · ' . $signsPerLine . ' signes/ligne · ' . $lines . ' lignes'
            . ' · ' . Tools::formatNumber((float) $signsPerPage, 0) . ' signes/page'
            . ' · ' . format_int($textPages) . ' pages de texte';
        $chapters = (int) ($input['chapters'] ?? 0);
        if ($chapters > 0) {
            $line .= ' · ' . format_int($chapters) . ' chapitres';
        }
        $line .= ' · ' . (int) $input['extra'] . ' hors texte · ' . format_int($sig16) . ' pages au cahier de 16';
        return $line;
    }

    private static function queryNumber(float $n): string
    {
        $formatted = number_format($n, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');
        return str_replace('.', ',', $formatted);
    }

    private static function clamp(float $n, float $min, float $max): float
    {
        return max($min, min($max, $n));
    }

    private static function clampInt(int $n, int $min, int $max): int
    {
        return max($min, min($max, $n));
    }
}
