<?php

declare(strict_types=1);

namespace Adl\Core;

/**
 * QR Code (mode octet, niveau M, versions 1 à 10).
 */
final class QrCode
{
    private const EC_M = 0;

    /** @var list<int> */
    private static array $exp = [];

    /** @var list<int> */
    private static array $log = [];

    public static function svg(string $text, int $size = 256, int $margin = 2, string $dark = '#15212F', string $light = '#FFFFFF'): string
    {
        $matrix = self::matrix($text);
        $n = count($matrix);
        $dim = $n + (2 * $margin);
        $modules = [];
        foreach ($matrix as $y => $row) {
            foreach ($row as $x => $bit) {
                if ($bit === 1) {
                    $modules[] = '<rect x="' . ($x + $margin) . '" y="' . ($y + $margin) . '" width="1" height="1"/>';
                }
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $dim . ' ' . $dim . '" width="' . $size . '" height="' . $size . '" shape-rendering="crispEdges" aria-hidden="true">'
            . '<rect width="' . $dim . '" height="' . $dim . '" fill="' . $light . '"/>'
            . '<g fill="' . $dark . '">' . implode('', $modules) . '</g>'
            . '</svg>';
    }

    /**
     * @return list<list<int>>
     */
    public static function matrix(string $text): array
    {
        $bytes = array_values(unpack('C*', $text) ?: []);
        $version = self::versionFor(count($bytes));
        $blocks = self::blockPlan($version);
        $dataCw = 0;
        $ecPerBlock = $blocks['ec'];
        foreach ($blocks['groups'] as [$count, $data]) {
            $dataCw += $count * $data;
        }

        $bits = self::encodeBits($bytes, $version, $dataCw * 8);
        $data = self::bitsToBytes($bits);
        $ecBlocks = [];
        $dataBlocks = [];
        $offset = 0;
        foreach ($blocks['groups'] as [$count, $dataLen]) {
            for ($i = 0; $i < $count; $i++) {
                $chunk = array_slice($data, $offset, $dataLen);
                $offset += $dataLen;
                $dataBlocks[] = $chunk;
                $ecBlocks[] = self::rsEncode($chunk, $ecPerBlock);
            }
        }

        $interleaved = self::interleave($dataBlocks, $ecBlocks);
        $size = ($version * 4) + 17;
        $reserved = self::reservedMap($version, $size);
        $grid = array_fill(0, $size, array_fill(0, $size, 0));
        self::placeData($grid, $reserved, $interleaved, $version);
        $mask = self::bestMask($grid, $reserved, $version, $size);
        self::applyMask($grid, $reserved, $mask);
        self::drawFunctionPatterns($grid, $version, $size);
        self::drawFormat($grid, $size, $mask);
        if ($version >= 7) {
            self::drawVersion($grid, $size, $version);
        }

        return $grid;
    }

    private static function versionFor(int $len): int
    {
        foreach ([1 => 14, 2 => 26, 3 => 42, 4 => 62, 5 => 84, 6 => 106, 7 => 122, 8 => 152, 9 => 180, 10 => 213] as $version => $cap) {
            if ($len <= $cap) {
                return $version;
            }
        }

        throw new \InvalidArgumentException('Le texte est trop long pour le QR code.');
    }

    /**
     * @return array{ec: int, groups: list<array{0: int, 1: int}>}
     */
    private static function blockPlan(int $version): array
    {
        return match ($version) {
            1 => ['ec' => 10, 'groups' => [[1, 16]]],
            2 => ['ec' => 16, 'groups' => [[1, 28]]],
            3 => ['ec' => 26, 'groups' => [[1, 44]]],
            4 => ['ec' => 18, 'groups' => [[2, 32]]],
            5 => ['ec' => 24, 'groups' => [[2, 43]]],
            6 => ['ec' => 16, 'groups' => [[4, 27]]],
            7 => ['ec' => 18, 'groups' => [[4, 31]]],
            8 => ['ec' => 22, 'groups' => [[2, 38], [2, 39]]],
            9 => ['ec' => 22, 'groups' => [[3, 36], [2, 37]]],
            default => ['ec' => 26, 'groups' => [[4, 43], [1, 44]]],
        };
    }

    /**
     * @param list<int> $bytes
     * @return list<int>
     */
    private static function encodeBits(array $bytes, int $version, int $capacity): array
    {
        $bits = [0, 1, 0, 0];
        $countBits = $version >= 10 ? 16 : 8;
        $n = count($bytes);
        for ($i = $countBits - 1; $i >= 0; $i--) {
            $bits[] = ($n >> $i) & 1;
        }
        foreach ($bytes as $b) {
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = ($b >> $i) & 1;
            }
        }
        $term = min(4, $capacity - count($bits));
        for ($i = 0; $i < $term; $i++) {
            $bits[] = 0;
        }
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }
        $pads = [0xEC, 0x11];
        $p = 0;
        while (count($bits) < $capacity) {
            $byte = $pads[$p % 2];
            $p++;
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = ($byte >> $i) & 1;
            }
        }

        return array_slice($bits, 0, $capacity);
    }

    /**
     * @param list<int> $bits
     * @return list<int>
     */
    private static function bitsToBytes(array $bits): array
    {
        $out = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $byte = 0;
            for ($j = 0; $j < 8; $j++) {
                $byte = ($byte << 1) | ($bits[$i + $j] ?? 0);
            }
            $out[] = $byte;
        }

        return $out;
    }

    /**
     * @param list<list<int>> $dataBlocks
     * @param list<list<int>> $ecBlocks
     * @return list<int>
     */
    private static function interleave(array $dataBlocks, array $ecBlocks): array
    {
        $out = [];
        $max = 0;
        foreach ($dataBlocks as $block) {
            $max = max($max, count($block));
        }
        for ($i = 0; $i < $max; $i++) {
            foreach ($dataBlocks as $block) {
                if (isset($block[$i])) {
                    $out[] = $block[$i];
                }
            }
        }
        $max = 0;
        foreach ($ecBlocks as $block) {
            $max = max($max, count($block));
        }
        for ($i = 0; $i < $max; $i++) {
            foreach ($ecBlocks as $block) {
                if (isset($block[$i])) {
                    $out[] = $block[$i];
                }
            }
        }

        return $out;
    }

    /**
     * @param list<int> $data
     * @return list<int>
     */
    private static function rsEncode(array $data, int $ecCount): array
    {
        self::initGf();
        $gen = [1];
        for ($i = 0; $i < $ecCount; $i++) {
            $next = array_fill(0, count($gen) + 1, 0);
            $factor = self::$exp[$i];
            for ($j = 0; $j < count($gen); $j++) {
                $next[$j] ^= $gen[$j];
                $next[$j + 1] ^= self::gfMul($gen[$j], $factor);
            }
            $gen = $next;
        }

        $out = array_merge($data, array_fill(0, $ecCount, 0));
        $dataLen = count($data);
        for ($i = 0; $i < $dataLen; $i++) {
            $coef = $out[$i];
            if ($coef === 0) {
                continue;
            }
            for ($j = 0; $j < count($gen); $j++) {
                $out[$i + $j] ^= self::gfMul($gen[$j], $coef);
            }
        }

        return array_slice($out, $dataLen);
    }

    private static function initGf(): void
    {
        if (self::$exp !== []) {
            return;
        }
        self::$exp = array_fill(0, 512, 0);
        self::$log = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$exp[$i] = $x;
            self::$log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$exp[$i] = self::$exp[$i - 255];
        }
    }

    private static function gfMul(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }

        return self::$exp[self::$log[$a] + self::$log[$b]];
    }

    /**
     * @return list<list<bool>>
     */
    private static function reservedMap(int $version, int $size): array
    {
        $reserved = array_fill(0, $size, array_fill(0, $size, false));
        $mark = static function (int $x, int $y) use (&$reserved, $size): void {
            if ($x >= 0 && $y >= 0 && $x < $size && $y < $size) {
                $reserved[$y][$x] = true;
            }
        };

        foreach ([[0, 0], [$size - 8, 0], [0, $size - 8]] as [$ox, $oy]) {
            for ($y = 0; $y < 9; $y++) {
                for ($x = 0; $x < 9; $x++) {
                    $mark($ox + $x, $oy + $y);
                }
            }
        }
        for ($i = 0; $i < $size; $i++) {
            $mark(6, $i);
            $mark($i, 6);
        }
        foreach (self::alignmentCenters($version) as $cx) {
            foreach (self::alignmentCenters($version) as $cy) {
                if (self::alignmentBlocked($cx, $cy, $size)) {
                    continue;
                }
                for ($y = $cy - 2; $y <= $cy + 2; $y++) {
                    for ($x = $cx - 2; $x <= $cx + 2; $x++) {
                        $mark($x, $y);
                    }
                }
            }
        }
        if ($version >= 7) {
            for ($i = 0; $i < 6; $i++) {
                for ($j = 0; $j < 3; $j++) {
                    $mark($size - 11 + $j, $i);
                    $mark($i, $size - 11 + $j);
                }
            }
        }

        return $reserved;
    }

    /**
     * @return list<int>
     */
    private static function alignmentCenters(int $version): array
    {
        return match ($version) {
            1 => [],
            2 => [6, 18],
            3 => [6, 22],
            4 => [6, 26],
            5 => [6, 30],
            6 => [6, 34],
            7 => [6, 22, 38],
            8 => [6, 24, 42],
            9 => [6, 26, 46],
            default => [6, 28, 50],
        };
    }

    private static function alignmentBlocked(int $cx, int $cy, int $size): bool
    {
        $corners = [[6, 6], [6, $size - 7], [$size - 7, 6]];
        foreach ($corners as [$x, $y]) {
            if (abs($cx - $x) <= 3 && abs($cy - $y) <= 3) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<list<int>> $grid
     * @param list<list<bool>> $reserved
     * @param list<int> $data
     */
    private static function placeData(array &$grid, array $reserved, array $data, int $version): void
    {
        $size = count($grid);
        $bits = [];
        foreach ($data as $byte) {
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = ($byte >> $i) & 1;
            }
        }
        $remain = match (true) {
            $version >= 2 && $version <= 6 => 7,
            default => 0,
        };
        for ($i = 0; $i < $remain; $i++) {
            $bits[] = 0;
        }

        $bit = 0;
        $up = true;
        for ($col = $size - 1; $col > 0; $col -= 2) {
            if ($col === 6) {
                $col--;
            }
            for ($n = 0; $n < $size; $n++) {
                $row = $up ? $size - 1 - $n : $n;
                foreach ([$col, $col - 1] as $x) {
                    if ($reserved[$row][$x]) {
                        continue;
                    }
                    $grid[$row][$x] = $bits[$bit] ?? 0;
                    $bit++;
                }
            }
            $up = !$up;
        }
    }

    /**
     * @param list<list<int>> $grid
     * @param list<list<bool>> $reserved
     */
    private static function bestMask(array $grid, array $reserved, int $version, int $size): int
    {
        $best = 0;
        $bestScore = PHP_INT_MAX;
        for ($mask = 0; $mask < 8; $mask++) {
            $trial = $grid;
            self::applyMask($trial, $reserved, $mask);
            self::drawFunctionPatterns($trial, $version, $size);
            self::drawFormat($trial, $size, $mask);
            if ($version >= 7) {
                self::drawVersion($trial, $size, $version);
            }
            $score = self::maskScore($trial);
            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $mask;
            }
        }

        return $best;
    }

    /**
     * @param list<list<int>> $grid
     * @param list<list<bool>> $reserved
     */
    private static function applyMask(array &$grid, array $reserved, int $mask): void
    {
        $size = count($grid);
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($reserved[$y][$x]) {
                    continue;
                }
                if (self::maskBit($mask, $y, $x)) {
                    $grid[$y][$x] ^= 1;
                }
            }
        }
    }

    private static function maskBit(int $mask, int $y, int $x): bool
    {
        return match ($mask) {
            0 => ($y + $x) % 2 === 0,
            1 => $y % 2 === 0,
            2 => $x % 3 === 0,
            3 => ($y + $x) % 3 === 0,
            4 => ((int) floor($y / 2) + (int) floor($x / 3)) % 2 === 0,
            5 => (($y * $x) % 2) + (($y * $x) % 3) === 0,
            6 => ((($y * $x) % 2) + (($y * $x) % 3)) % 2 === 0,
            default => ((($y + $x) % 2) + (($y * $x) % 3)) % 2 === 0,
        };
    }

    /**
     * @param list<list<int>> $grid
     */
    private static function maskScore(array $grid): int
    {
        $size = count($grid);
        $score = 0;
        foreach ([true, false] as $horizontal) {
            for ($i = 0; $i < $size; $i++) {
                $run = 1;
                $prev = $horizontal ? $grid[$i][0] : $grid[0][$i];
                for ($j = 1; $j < $size; $j++) {
                    $bit = $horizontal ? $grid[$i][$j] : $grid[$j][$i];
                    if ($bit === $prev) {
                        $run++;
                    } else {
                        if ($run >= 5) {
                            $score += 3 + ($run - 5);
                        }
                        $run = 1;
                        $prev = $bit;
                    }
                }
                if ($run >= 5) {
                    $score += 3 + ($run - 5);
                }
            }
        }
        for ($y = 0; $y < $size - 1; $y++) {
            for ($x = 0; $x < $size - 1; $x++) {
                $v = $grid[$y][$x];
                if ($v === $grid[$y][$x + 1] && $v === $grid[$y + 1][$x] && $v === $grid[$y + 1][$x + 1]) {
                    $score += 3;
                }
            }
        }
        $finder = [1, 0, 1, 1, 1, 0, 1];
        foreach ([true, false] as $horizontal) {
            for ($i = 0; $i < $size; $i++) {
                $line = [];
                for ($j = 0; $j < $size; $j++) {
                    $line[] = $horizontal ? $grid[$i][$j] : $grid[$j][$i];
                }
                $joined = implode('', $line);
                $pattern = implode('', $finder);
                $score += 40 * substr_count($joined, '00001011101');
                $score += 40 * substr_count($joined, '10111010000');
                $score += 40 * substr_count($joined, $pattern);
            }
        }
        $dark = 0;
        foreach ($grid as $row) {
            foreach ($row as $bit) {
                $dark += $bit;
            }
        }
        $percent = (int) (($dark * 100) / ($size * $size));
        $score += (int) (abs($percent - 50) / 5) * 10;

        return $score;
    }

    /**
     * @param list<list<int>> $grid
     */
    private static function drawFunctionPatterns(array &$grid, int $version, int $size): void
    {
        foreach ([[0, 0], [$size - 7, 0], [0, $size - 7]] as [$ox, $oy]) {
            for ($y = -1; $y <= 7; $y++) {
                for ($x = -1; $x <= 7; $x++) {
                    $xx = $ox + $x;
                    $yy = $oy + $y;
                    if ($xx < 0 || $yy < 0 || $xx >= $size || $yy >= $size) {
                        continue;
                    }
                    $on = $x === -1 || $y === -1 || $x === 7 || $y === 7
                        ? 0
                        : (($x === 0 || $x === 6 || $y === 0 || $y === 6) || ($x >= 2 && $x <= 4 && $y >= 2 && $y <= 4) ? 1 : 0);
                    $grid[$yy][$xx] = $on;
                }
            }
        }
        for ($i = 8; $i < $size - 8; $i++) {
            $grid[6][$i] = $i % 2 === 0 ? 1 : 0;
            $grid[$i][6] = $i % 2 === 0 ? 1 : 0;
        }
        foreach (self::alignmentCenters($version) as $cx) {
            foreach (self::alignmentCenters($version) as $cy) {
                if (self::alignmentBlocked($cx, $cy, $size)) {
                    continue;
                }
                for ($y = -2; $y <= 2; $y++) {
                    for ($x = -2; $x <= 2; $x++) {
                        $grid[$cy + $y][$cx + $x] = (abs($x) === 2 || abs($y) === 2 || ($x === 0 && $y === 0)) ? 1 : 0;
                    }
                }
            }
        }
        $grid[(4 * $version) + 9][8] = 1;
    }

    /**
     * @param list<list<int>> $grid
     */
    private static function drawFormat(array &$grid, int $size, int $mask): void
    {
        $bits = self::formatBits($mask);
        $coords = [];
        for ($i = 0; $i < 6; $i++) {
            $coords[] = [$i, 8];
        }
        $coords[] = [7, 8];
        $coords[] = [8, 8];
        $coords[] = [8, 7];
        for ($i = 5; $i >= 0; $i--) {
            $coords[] = [8, $i];
        }
        $other = [];
        for ($i = 0; $i < 8; $i++) {
            $other[] = [$size - 1 - $i, 8];
        }
        for ($i = 0; $i < 7; $i++) {
            $other[] = [8, $size - 7 + $i];
        }
        for ($i = 0; $i < 15; $i++) {
            $bit = ($bits >> (14 - $i)) & 1;
            $grid[$coords[$i][1]][$coords[$i][0]] = $bit;
            $grid[$other[$i][1]][$other[$i][0]] = $bit;
        }
    }

    /**
     * @param list<list<int>> $grid
     */
    private static function drawVersion(array &$grid, int $size, int $version): void
    {
        $bits = self::versionBits($version);
        $i = 0;
        for ($a = 0; $a < 6; $a++) {
            for ($b = 0; $b < 3; $b++) {
                $bit = ($bits >> $i) & 1;
                $grid[$a][$size - 11 + $b] = $bit;
                $grid[$size - 11 + $b][$a] = $bit;
                $i++;
            }
        }
    }

    private static function formatBits(int $mask): int
    {
        $data = (self::EC_M << 3) | $mask;
        $d = $data << 10;
        for ($i = 14; $i >= 10; $i--) {
            if (($d >> $i) & 1) {
                $d ^= 0x537 << ($i - 10);
            }
        }

        return (($data << 10) | $d) ^ 0x5412;
    }

    private static function versionBits(int $version): int
    {
        $d = $version << 12;
        for ($i = 17; $i >= 12; $i--) {
            if (($d >> $i) & 1) {
                $d ^= 0x1F25 << ($i - 12);
            }
        }

        return ($version << 12) | $d;
    }
}
