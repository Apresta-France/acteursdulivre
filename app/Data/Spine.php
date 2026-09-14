<?php

declare(strict_types=1);

namespace Adl\Data;

final class Spine
{
    public const PAGES_MIN = 16;
    public const PAGES_MAX = 2000;
    public const BLEED_DEFAULT = 5.0;
    public const BLEED_MAX = 15.0;
    public const HINGE_MM = 8.0;
    public const SQUARE_MM = 3.0;
    public const FLAP_DEFAULT = 80.0;
    public const FLAP_MIN = 40.0;
    public const FLAP_MAX = 140.0;
    public const SIZE_MIN = 80.0;
    public const SIZE_MAX = 420.0;
    public const GRAMMAGE_MIN = 50.0;
    public const GRAMMAGE_MAX = 300.0;
    public const BULK_MIN = 0.6;
    public const BULK_MAX = 2.6;

    /**
     * @return array<string, array{label: string, w: float, h: float}>
     */
    public static function formats(): array
    {
        return [
            'poche' => ['label' => 'Poche (11 × 18 cm)', 'w' => 110.0, 'h' => 180.0],
            'semi-poche' => ['label' => 'Semi-poche (12 × 19 cm)', 'w' => 120.0, 'h' => 190.0],
            'roman' => ['label' => 'Roman (14 × 21 cm)', 'w' => 140.0, 'h' => 210.0],
            'a5' => ['label' => 'A5 (14,8 × 21 cm)', 'w' => 148.0, 'h' => 210.0],
            '15x21' => ['label' => '15 × 21 cm', 'w' => 150.0, 'h' => 210.0],
            '15x23' => ['label' => '15 × 23 cm', 'w' => 150.0, 'h' => 230.0],
            '16x24' => ['label' => '16 × 24 cm', 'w' => 160.0, 'h' => 240.0],
            'a4' => ['label' => 'A4 (21 × 29,7 cm)', 'w' => 210.0, 'h' => 297.0],
            'custom' => ['label' => 'Format libre', 'w' => 140.0, 'h' => 210.0],
        ];
    }

    /**
     * @return array<string, array{label: string, g: float, v: float}>
     */
    public static function papers(): array
    {
        return [
            'bouffant-70' => ['label' => 'Bouffant 70 g (vol. 2,0)', 'g' => 70.0, 'v' => 2.0],
            'bouffant-80' => ['label' => 'Bouffant 80 g (vol. 1,8)', 'g' => 80.0, 'v' => 1.8],
            'bouffant-90' => ['label' => 'Bouffant 90 g (vol. 1,8)', 'g' => 90.0, 'v' => 1.8],
            'offset-80' => ['label' => 'Offset 80 g (vol. 1,25)', 'g' => 80.0, 'v' => 1.25],
            'offset-90' => ['label' => 'Offset 90 g (vol. 1,25)', 'g' => 90.0, 'v' => 1.25],
            'recycle-80' => ['label' => 'Recyclé 80 g (vol. 1,3)', 'g' => 80.0, 'v' => 1.3],
            'couche-135' => ['label' => 'Couché 135 g (vol. 0,95)', 'g' => 135.0, 'v' => 0.95],
            'custom' => ['label' => 'Papier libre', 'g' => 80.0, 'v' => 1.8],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function bindings(): array
    {
        return [
            'broche' => 'Broché (dos carré collé)',
            'relie' => 'Relié / cartonnage',
        ];
    }

    /**
     * @return array{
     *   format: string,
     *   paper: string,
     *   binding: string,
     *   pages: int,
     *   width: float,
     *   height: float,
     *   grammage: float,
     *   bulk: float,
     *   bleed: float,
     *   flaps: bool,
     *   flap: float
     * }
     */
    public static function defaults(): array
    {
        return [
            'format' => 'roman',
            'paper' => 'bouffant-80',
            'binding' => 'broche',
            'pages' => 280,
            'width' => 140.0,
            'height' => 210.0,
            'grammage' => 80.0,
            'bulk' => 1.8,
            'bleed' => self::BLEED_DEFAULT,
            'flaps' => false,
            'flap' => self::FLAP_DEFAULT,
        ];
    }

    /**
     * @return array{
     *   format: string,
     *   paper: string,
     *   binding: string,
     *   pages: int,
     *   width: float,
     *   height: float,
     *   grammage: float,
     *   bulk: float,
     *   bleed: float,
     *   flaps: bool,
     *   flap: float
     * }
     */
    public static function fromQuery(
        string $format,
        string $paper,
        string $binding,
        string $pages,
        string $width,
        string $height,
        string $grammage,
        string $bulk,
        string $bleed,
        string $flaps,
        string $flap
    ): array {
        $base = self::defaults();
        $formats = self::formats();
        $papers = self::papers();
        $format = isset($formats[$format]) ? $format : $base['format'];
        $paper = isset($papers[$paper]) ? $paper : $base['paper'];
        $binding = $binding === 'relie' ? 'relie' : 'broche';

        $presetFormat = $formats[$format];
        $presetPaper = $papers[$paper];
        $w = $format === 'custom' ? (self::parseDecimal($width) ?? $presetFormat['w']) : $presetFormat['w'];
        $h = $format === 'custom' ? (self::parseDecimal($height) ?? $presetFormat['h']) : $presetFormat['h'];
        $g = $paper === 'custom' ? (self::parseDecimal($grammage) ?? $presetPaper['g']) : $presetPaper['g'];
        $v = $paper === 'custom' ? (self::parseDecimal($bulk) ?? $presetPaper['v']) : $presetPaper['v'];

        $pageCount = self::parseInt($pages);
        if ($pageCount === null) {
            $pageCount = $base['pages'];
        }

        $bleedMm = self::parseDecimal($bleed);
        if ($bleedMm === null) {
            $bleedMm = $base['bleed'];
        }

        $hasFlaps = $flaps === '1' || $flaps === 'on' || $flaps === 'oui';
        $flapMm = self::parseDecimal($flap);
        if ($flapMm === null) {
            $flapMm = $base['flap'];
        }

        return [
            'format' => $format,
            'paper' => $paper,
            'binding' => $binding,
            'pages' => self::clampInt($pageCount, self::PAGES_MIN, self::PAGES_MAX),
            'width' => self::clamp($w, self::SIZE_MIN, self::SIZE_MAX),
            'height' => self::clamp($h, self::SIZE_MIN, self::SIZE_MAX),
            'grammage' => self::clamp($g, self::GRAMMAGE_MIN, self::GRAMMAGE_MAX),
            'bulk' => self::clamp($v, self::BULK_MIN, self::BULK_MAX),
            'bleed' => self::clamp($bleedMm, 0.0, self::BLEED_MAX),
            'flaps' => $hasFlaps && $binding === 'broche',
            'flap' => self::clamp($flapMm, self::FLAP_MIN, self::FLAP_MAX),
        ];
    }

    /**
     * Dos = (pages / 2) × (grammage × volume / 1000).
     *
     * @param array{
     *   format: string,
     *   paper: string,
     *   binding: string,
     *   pages: int,
     *   width: float,
     *   height: float,
     *   grammage: float,
     *   bulk: float,
     *   bleed: float,
     *   flaps: bool,
     *   flap: float
     * } $input
     * @return array<string, mixed>
     */
    public static function compute(array $input): array
    {
        $pages = (int) $input['pages'];
        $width = (float) $input['width'];
        $height = (float) $input['height'];
        $grammage = (float) $input['grammage'];
        $bulk = (float) $input['bulk'];
        $bleed = (float) $input['bleed'];
        $binding = (string) $input['binding'];
        $flaps = !empty($input['flaps']) && $binding === 'broche';
        $flap = (float) $input['flap'];

        $sheet = $grammage * $bulk / 1000;
        $spine = ($pages / 2) * $sheet;
        $hinge = $binding === 'relie' ? self::HINGE_MM : 0.0;
        $square = $binding === 'relie' ? self::SQUARE_MM : 0.0;
        $panelW = $width + $square;
        $coverW = (2 * $panelW) + $spine + (2 * $hinge) + (2 * $bleed);
        if ($flaps) {
            $coverW += 2 * $flap;
        }
        $coverH = $height + (2 * $square) + (2 * $bleed);

        $warnings = [];
        if ($pages % 2 !== 0) {
            $warnings[] = 'Une pagination impaire est inhabituelle. On compte généralement un nombre pair de pages.';
        } elseif ($pages % 4 !== 0) {
            $warnings[] = 'L’offset se cale souvent par 4 ou 16 pages. Arrondissez avant le BAT.';
        }
        if ($spine < 6) {
            $warnings[] = 'Dos étroit : un titre au dos sera difficile à poser.';
        }
        if ($spine > 45) {
            $warnings[] = 'Dos très épais : vérifiez le façonnage (couture, dos carré collé, cartonnage).';
        }
        if ($binding === 'relie') {
            $warnings[] = 'Le cartonnage ajoute charnières et cartons. L’atelier confirme les cotes de la jaquette ou des plats.';
        }

        $coverParts = [];
        if ($flaps) {
            $coverParts[] = ['id' => 'flap-back', 'label' => 'Rabat 4e', 'mm' => $flap];
        }
        $coverParts[] = ['id' => 'back', 'label' => '4e', 'mm' => $panelW];
        if ($hinge > 0) {
            $coverParts[] = ['id' => 'hinge', 'label' => 'Charnière', 'mm' => $hinge];
        }
        $coverParts[] = ['id' => 'spine', 'label' => 'Dos', 'mm' => $spine];
        if ($hinge > 0) {
            $coverParts[] = ['id' => 'hinge', 'label' => 'Charnière', 'mm' => $hinge];
        }
        $coverParts[] = ['id' => 'front', 'label' => '1re', 'mm' => $panelW];
        if ($flaps) {
            $coverParts[] = ['id' => 'flap-front', 'label' => 'Rabat 1re', 'mm' => $flap];
        }

        return [
            'pages' => $pages,
            'width' => $width,
            'height' => $height,
            'grammage' => $grammage,
            'bulk' => $bulk,
            'bleed' => $bleed,
            'binding' => $binding,
            'flaps' => $flaps,
            'flap' => $flap,
            'sheet' => $sheet,
            'spine' => $spine,
            'hinge' => $hinge,
            'square' => $square,
            'cover_w' => $coverW,
            'cover_h' => $coverH,
            'panel_w' => $panelW,
            'warnings' => $warnings,
            'parts' => $coverParts,
            'summary' => self::summary($input, $spine, $coverW, $coverH),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function jsConfig(): array
    {
        return [
            'formats' => self::formats(),
            'papers' => self::papers(),
            'pagesMin' => self::PAGES_MIN,
            'pagesMax' => self::PAGES_MAX,
            'bleedMax' => self::BLEED_MAX,
            'hinge' => self::HINGE_MM,
            'square' => self::SQUARE_MM,
            'flapMin' => self::FLAP_MIN,
            'flapMax' => self::FLAP_MAX,
            'sizeMin' => self::SIZE_MIN,
            'sizeMax' => self::SIZE_MAX,
            'grammageMin' => self::GRAMMAGE_MIN,
            'grammageMax' => self::GRAMMAGE_MAX,
            'bulkMin' => self::BULK_MIN,
            'bulkMax' => self::BULK_MAX,
        ];
    }

    /**
     * @return list<array{q: string, a: string}>
     */
    public static function faqs(): array
    {
        return [
            [
                'q' => 'Comment se calcule la largeur de dos ?',
                'a' => 'Dos (mm) = (nombre de pages / 2) × (grammage × volume du papier / 1 000). Le volume, ou « main », est l’épaisseur relative de la feuille. Un bouffant 80 g à volume 1,8 n’a pas le même dos qu’un offset 80 g à 1,25.',
            ],
            [
                'q' => 'Pourquoi l’imprimeur a-t-il le dernier mot ?',
                'a' => 'Le grammage et le volume du nuancier ne sont pas l’épaisseur réelle de la rame livrée. L’humidité, le calage et le façonnage déplacent le dos de quelques dixièmes. Envoyez ces cotes comme brief, pas comme BAT définitif.',
            ],
            [
                'q' => 'Qu’est-ce que le fond perdu ?',
                'a' => 'En France, on prévoit en général 5 mm de fond perdu tout autour du PDF couverture. Le massicot coupe dans cette marge. Sans elle, un filet blanc apparaît sur le chant.',
            ],
            [
                'q' => 'Broché ou relié ?',
                'a' => 'Le broché (dos carré collé) est le roman courant : 1re, dos, 4e, éventuellement rabats. Le cartonnage ajoute des charnières et des cartons : le fichier n’est plus le même. Parlez jaquette ou plats avec l’atelier.',
            ],
            [
                'q' => 'Faut-il un nombre de pages multiple de 4 ?',
                'a' => 'En offset, oui, souvent par 4, 8 ou 16 selon le cahier. Le numérique est plus souple. Un 278 pages sera probablement imprimé en 280. Mieux vaut le décider avant la couverture.',
            ],
        ];
    }

    public static function formatMm(float $n, int $decimals = 1): string
    {
        return Tools::formatNumber($n, $decimals) . ' mm';
    }

    public static function formatCm(float $n): string
    {
        return Tools::formatNumber($n / 10, 1) . ' cm';
    }

    public static function formatSize(float $w, float $h, string $unit = 'mm'): string
    {
        if ($unit === 'cm') {
            return Tools::formatNumber($w / 10, 1) . ' × ' . Tools::formatNumber($h / 10, 1) . ' cm';
        }
        return Tools::formatNumber($w, 1) . ' × ' . Tools::formatNumber($h, 1) . ' mm';
    }

    /**
     * @param array<string, mixed> $input
     */
    private static function summary(array $input, float $spine, float $coverW, float $coverH): string
    {
        $binding = (string) $input['binding'] === 'relie' ? 'relié' : 'broché';
        $paper = Tools::formatNumber((float) $input['grammage'], 0) . ' g vol. ' . Tools::formatNumber((float) $input['bulk'], 2);
        $line = 'Dos ' . self::formatMm($spine, 1)
            . ' · PDF couverture ' . self::formatSize($coverW, $coverH)
            . ' · intérieur ' . self::formatSize((float) $input['width'], (float) $input['height'])
            . ' · ' . (int) $input['pages'] . ' pages · ' . $paper
            . ' · fond perdu ' . self::formatMm((float) $input['bleed'], 0)
            . ' · ' . $binding;
        if (!empty($input['flaps'])) {
            $line .= ' · rabats ' . self::formatMm((float) $input['flap'], 0);
        }
        return $line;
    }

    public static function parseDecimal(string $raw): ?float
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
        return (float) $raw;
    }

    private static function parseInt(string $raw): ?int
    {
        $raw = trim(str_replace(["\u{00A0}", ' '], '', $raw));
        if ($raw === '' || !preg_match('/^-?\d+$/', $raw)) {
            $n = self::parseDecimal($raw);
            if ($n === null) {
                return null;
            }
            return (int) round($n);
        }
        return (int) $raw;
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
