<?php

declare(strict_types=1);

namespace Adl\Core;

use Adl\Data\ProfanityDictionary;
use RuntimeException;

/**
 * Filtre d'insultes pour le forum.
 *
 * Principes anti-faux-positifs :
 * - match sur des jetons (mots), pas sur n'importe quelle sous-chaîne du texte brut ;
 * - insultes courtes (≤ 4 lettres) : égalité stricte uniquement ;
 * - insultes plus longues : égalité, ou inclusion dans un jeton soudé (ex. grosconnard) ;
 * - formes camouflées (p*te, f.d.p, c0nnard) via normalisation + motifs.
 */
final class ProfanityFilter
{
    private const MESSAGE = 'Votre message contient des termes non autorisés (insultes ou propos abusifs, même déguisés). Merci de reformuler.';

    /** @var array<string, true>|null */
    private static ?array $words = null;

    /** @var array<string, true>|null */
    private static ?array $allow = null;

    /** @var list<string>|null */
    private static ?array $phrases = null;

    /** @var list<string>|null */
    private static ?array $longWords = null;

    public static function assertClean(string ...$parts): void
    {
        foreach ($parts as $part) {
            if (self::findHit($part) !== null) {
                throw new RuntimeException(self::MESSAGE);
            }
        }
    }

    public static function isClean(string $text): bool
    {
        return self::findHit($text) === null;
    }

    /** @return string|null Terme détecté (debug / tests), null si OK */
    public static function findHit(string $text): ?string
    {
        $raw = trim($text);
        if ($raw === '') {
            return null;
        }

        $plain = plain_text($raw);
        if ($plain === '') {
            $plain = strip_tags($raw);
        }

        $norm = self::normalize($plain);
        if ($norm === '') {
            return null;
        }

        self::boot();

        foreach (self::$phrases ?? [] as $phrase) {
            if ($phrase !== '' && str_contains($norm, $phrase)) {
                return $phrase;
            }
        }

        $compact = preg_replace('/\s+/', '', $norm) ?? $norm;
        foreach (self::$phrases ?? [] as $phrase) {
            $joined = str_replace(' ', '', $phrase);
            if ($joined !== '' && str_contains($compact, $joined)) {
                return $phrase;
            }
        }

        $tokens = self::tokens($norm);
        $letterTokens = [];
        foreach ($tokens as $token) {
            $hit = self::checkToken($token);
            if ($hit !== null) {
                return $hit;
            }
            $letters = self::lettersOnly($token);
            if ($letters !== '') {
                $letterTokens[] = $letters;
            }
        }

        // Lettres isolées / ponctuées : "p u t e", "f.d.p"
        $merged = self::mergeSingleChars($letterTokens);
        foreach ($merged as $token) {
            $hit = self::checkLetters($token);
            if ($hit !== null) {
                return $hit;
            }
        }

        return null;
    }

    private static function boot(): void
    {
        if (self::$words !== null) {
            return;
        }

        $words = [];
        foreach (ProfanityDictionary::WORDS as $word) {
            $n = self::normalize((string) $word);
            $n = self::lettersOnly($n);
            if ($n !== '') {
                $words[$n] = true;
            }
        }
        self::$words = $words;

        $allow = [];
        foreach (ProfanityDictionary::ALLOW as $word) {
            $n = self::normalize((string) $word);
            $n = self::lettersOnly($n);
            if ($n !== '') {
                $allow[$n] = true;
            }
        }
        self::$allow = $allow;

        $phrases = [];
        foreach (ProfanityDictionary::PHRASES as $phrase) {
            $n = self::normalize((string) $phrase);
            $n = trim(preg_replace('/\s+/', ' ', $n) ?? $n);
            if ($n !== '') {
                $phrases[] = $n;
            }
        }
        usort($phrases, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        self::$phrases = $phrases;

        $long = [];
        foreach (array_keys($words) as $word) {
            if (mb_strlen($word) >= 5) {
                $long[] = $word;
            }
        }
        usort($long, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        self::$longWords = $long;
    }

    private static function normalize(string $text): string
    {
        $text = search_norm($text);
        $leet = [
            '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's',
            '7' => 't', '@' => 'a', '$' => 's', '€' => 'e',
        ];
        $text = strtr($text, $leet);
        $text = str_replace(['’', '`', '´'], "'", $text);
        $text = (string) preg_replace("/'+/", ' ', $text);
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /** @return list<string> */
    private static function tokens(string $norm): array
    {
        $parts = preg_split('/[^a-z0-9*#._\-]+/u', $norm) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $part = trim($part, '._-');
            if ($part === '') {
                continue;
            }
            $out[] = $part;
        }
        return $out;
    }

    private static function checkToken(string $token): ?string
    {
        if (self::isMasked($token)) {
            $hit = self::matchMasked($token);
            if ($hit !== null) {
                return $hit;
            }
        }

        $letters = self::lettersOnly($token);
        return self::checkLetters($letters);
    }

    private static function checkLetters(string $letters): ?string
    {
        if ($letters === '') {
            return null;
        }

        $collapsed = self::collapseRepeats($letters);
        foreach ([$letters, $collapsed, self::collapseRepeats($collapsed, 1)] as $candidate) {
            if ($candidate === '' || isset(self::$allow[$candidate])) {
                continue;
            }
            if (isset(self::$words[$candidate])) {
                return $candidate;
            }
        }

        if (isset(self::$allow[$letters]) || isset(self::$allow[$collapsed])) {
            return null;
        }

        // Composés type "grosconnard" : sous-chaîne uniquement pour insultes ≥ 5 lettres
        $hay = $collapsed !== '' ? $collapsed : $letters;
        if (mb_strlen($hay) >= 6) {
            foreach (self::$longWords ?? [] as $word) {
                if (str_contains($hay, $word) && !isset(self::$allow[$hay])) {
                    return $word;
                }
            }
        }

        return null;
    }

    private static function isMasked(string $token): bool
    {
        return (bool) preg_match('/[*#]/', $token)
            || (bool) preg_match('/[a-z][._\-][a-z]/', $token);
    }

    private static function matchMasked(string $token): ?string
    {
        $pattern = '';
        $len = strlen($token);
        for ($i = 0; $i < $len; $i++) {
            $ch = $token[$i];
            if ($ch >= 'a' && $ch <= 'z') {
                $pattern .= $ch;
            } elseif ($ch >= '0' && $ch <= '9') {
                // déjà normalisé en lettres la plupart du temps
                $pattern .= $ch;
            } elseif (str_contains('*#._-', $ch)) {
                $pattern .= '[a-z]?';
            }
        }
        if ($pattern === '' || $pattern === '[a-z]?') {
            return null;
        }

        $regex = '/^' . $pattern . '$/';
        foreach (array_keys(self::$words ?? []) as $word) {
            if (isset(self::$allow[$word])) {
                continue;
            }
            if (preg_match($regex, $word) === 1) {
                return $word;
            }
        }
        return null;
    }

    private static function lettersOnly(string $token): string
    {
        return (string) preg_replace('/[^a-z]/', '', $token);
    }

    private static function collapseRepeats(string $word, int $max = 2): string
    {
        $max = max(1, $max);
        return (string) preg_replace('/(.)\1{' . $max . ',}/u', str_repeat('$1', $max), $word);
    }

    /**
     * Fusionne les suites de jetons d'1 lettre : p+u+t+e → pute, f+d+p → fdp
     *
     * @param list<string> $letterTokens
     * @return list<string>
     */
    private static function mergeSingleChars(array $letterTokens): array
    {
        $out = [];
        $buf = '';
        foreach ($letterTokens as $token) {
            if (mb_strlen($token) === 1) {
                $buf .= $token;
                continue;
            }
            if (mb_strlen($buf) >= 2) {
                $out[] = $buf;
            }
            $buf = '';
        }
        if (mb_strlen($buf) >= 2) {
            $out[] = $buf;
        }
        return $out;
    }
}
