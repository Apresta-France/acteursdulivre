<?php

declare(strict_types=1);

namespace Adl\Data;

use Adl\Core\QrCode;
use Adl\Models\Profile;
use Adl\Models\User;

final class ProfileShare
{
    public const TAGLINE = 'Retrouvez mes prestations sur Acteurs du Livre';

    /**
     * @param array<string, mixed> $profile
     * @return array<string, mixed>
     */
    public static function kit(array $profile, ?array $user = null): array
    {
        $name = Profile::displayName($profile);
        $title = trim((string) ($profile['title'] ?? ''));
        $city = trim((string) ($profile['city'] ?? ''));
        $slug = trim((string) ($profile['slug'] ?? ''));
        $path = $slug !== '' ? '/prestataires/' . $slug : '';
        $url = $path !== '' ? Share::absolute($path) : '';
        $hostUrl = $url !== '' ? preg_replace('#^https?://#i', '', $url) : '';
        $initials = Profile::initials($profile);
        $trades = array_values(array_filter(array_map('strval', $profile['trades'] ?? [])));
        $subtitle = trim($title . ($city !== '' ? ($title !== '' ? ' · ' : '') . $city : ''));
        if ($subtitle === '' && $trades !== []) {
            $subtitle = implode(' · ', array_slice($trades, 0, 2));
        }

        $visible = $slug !== '' && User::isPublicOfferer($user ?? $profile);
        $qrSvg = $url !== '' ? QrCode::svg($url, 220, 2, '#15212F', '#FFFFFF') : '';

        $visuals = [];
        if ($url !== '') {
            $visuals = [
                self::visual('instagram', $name, $subtitle, $hostUrl, $initials, $url),
                self::visual('story', $name, $subtitle, $hostUrl, $initials, $url),
                self::visual('social', $name, $subtitle, $hostUrl, $initials, $url),
            ];
        }

        return [
            'tagline' => self::TAGLINE,
            'name' => $name,
            'title' => $title,
            'city' => $city,
            'subtitle' => $subtitle,
            'initials' => $initials,
            'url' => $url,
            'path' => $path,
            'host_url' => $hostUrl,
            'visible' => $visible,
            'ready' => $url !== '',
            'qr_svg' => $qrSvg,
            'visuals' => $visuals,
            'signature_html' => $url !== '' ? self::signatureHtml($name, $subtitle, $url) : '',
            'badge_html' => $url !== '' ? self::badgeHtml($url) : '',
            'badge_svg' => $url !== '' ? self::badgeSvg($url) : '',
            'share_title' => $name . ($title !== '' ? ' — ' . $title : '') . ' · Acteurs du Livre',
            'share_text' => self::TAGLINE,
        ];
    }

    /**
     * @return array{id: string, label: string, hint: string, file: string, width: int, height: int, svg: string}
     */
    private static function visual(string $id, string $name, string $subtitle, string $hostUrl, string $initials, string $url): array
    {
        $meta = match ($id) {
            'instagram' => [
                'label' => 'Visuel Instagram',
                'hint' => 'Carré 1080 × 1080, pour un post ou un reel figé.',
                'file' => 'acteursdulivre-instagram',
                'width' => 1080,
                'height' => 1080,
            ],
            'story' => [
                'label' => 'Story Instagram',
                'hint' => 'Format vertical 1080 × 1920, à poster en story.',
                'file' => 'acteursdulivre-story',
                'width' => 1080,
                'height' => 1920,
            ],
            default => [
                'label' => 'Visuel LinkedIn / Facebook',
                'hint' => 'Format paysage 1200 × 627, pour un post ou une couverture.',
                'file' => 'acteursdulivre-linkedin-facebook',
                'width' => 1200,
                'height' => 627,
            ],
        };

        $meta['id'] = $id;
        $meta['svg'] = match ($id) {
            'instagram' => self::squareSvg($name, $subtitle, $hostUrl, $initials, $url, $meta['width'], $meta['height']),
            'story' => self::storySvg($name, $subtitle, $hostUrl, $initials, $url, $meta['width'], $meta['height']),
            default => self::landscapeSvg($name, $subtitle, $hostUrl, $initials, $url, $meta['width'], $meta['height']),
        };

        return $meta;
    }

    private static function squareSvg(string $name, string $subtitle, string $hostUrl, string $initials, string $url, int $w, int $h): string
    {
        $nameLines = self::textLines($name, 18);
        $subLines = self::textLines($subtitle, 32);

        return self::svgRoot($w, $h, self::navyBg($w, $h) . self::wordmark(80, 88) . self::avatar($w / 2, 250, 78, $initials)
            . self::textBlock($nameLines, 400, 52, '#FFFFFF', 700)
            . self::textBlock($subLines, 400 + 68 + ((count($nameLines) - 1) * 58), 26, '#EFDFCE', 400)
            . '<rect x="500" y="560" width="80" height="6" rx="3" fill="#EB963B"/>'
            . self::taglineBlock(610, 26)
            . self::qrGroup($url, 430, 760, 220)
            . '<text x="540" y="1018" text-anchor="middle" fill="#F0B372" font-family="Space Grotesk, Helvetica, Arial, sans-serif" font-size="18">' . self::xml($hostUrl) . '</text>');
    }

    private static function storySvg(string $name, string $subtitle, string $hostUrl, string $initials, string $url, int $w, int $h): string
    {
        $qr = self::qrGroup($url, 390, 1320, 300);
        $nameLines = self::textLines($name, 16);
        $subLines = self::textLines($subtitle, 28);

        return self::svgRoot($w, $h, self::navyBg($w, $h) . self::wordmark(80, 120) . self::avatar($w / 2, 420, 120, $initials)
            . self::textBlock($nameLines, 640, 92, '#FFFFFF', 700)
            . self::textBlock($subLines, 780, 32, '#EFDFCE', 400)
            . '<rect x="500" y="900" width="80" height="6" rx="3" fill="#EB963B"/>'
            . self::taglineBlock(960, 30, 540, 'middle', 24)
            . $qr
            . '<text x="540" y="1750" text-anchor="middle" fill="#EFDFCE" font-family="Space Grotesk, Helvetica, Arial, sans-serif" font-size="22">' . self::xml($hostUrl) . '</text>');
    }

    private static function landscapeSvg(string $name, string $subtitle, string $hostUrl, string $initials, string $url, int $w, int $h): string
    {
        $qr = self::qrGroup($url, 868, 168, 220);
        $nameLines = self::textLines($name, 18);
        $subLines = self::textLines($subtitle, 36);

        return self::svgRoot($w, $h, self::navyBg($w, $h) . self::wordmark(64, 72)
            . self::avatar(132, 250, 68, $initials)
            . self::textBlock($nameLines, 250, 48, '#FFFFFF', 700, 228, 'start')
            . self::textBlock($subLines, 250 + 58 + ((count($nameLines) - 1) * 52), 22, '#EFDFCE', 400, 228, 'start')
            . '<rect x="228" y="430" width="64" height="5" rx="2.5" fill="#EB963B"/>'
            . self::taglineBlock(478, 22, 228, 'start', 32)
            . '<text x="228" y="560" text-anchor="start" fill="#F0B372" font-family="Space Grotesk, Helvetica, Arial, sans-serif" font-size="18">' . self::xml($hostUrl) . '</text>'
            . $qr);
    }

    private static function navyBg(int $w, int $h): string
    {
        return '<rect width="' . $w . '" height="' . $h . '" fill="#15212F"/>'
            . '<circle cx="' . (int) ($w * 0.92) . '" cy="' . (int) ($h * 0.08) . '" r="' . (int) ($w * 0.38) . '" fill="#1C2C3E"/>'
            . '<circle cx="' . (int) ($w * 0.08) . '" cy="' . (int) ($h * 0.92) . '" r="' . (int) ($w * 0.28) . '" fill="#1A2838"/>';
    }

    private static function wordmark(int $x, int $y): string
    {
        return '<text x="' . $x . '" y="' . $y . '" fill="#EB963B" font-family="Space Grotesk, Helvetica, Arial, sans-serif" font-size="18" font-weight="700" letter-spacing="2.4">ACTEURS DU LIVRE</text>';
    }

    private static function avatar(float $cx, float $cy, float $r, string $initials): string
    {
        $size = (int) round($r * 0.72);

        return '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="#EB963B"/>'
            . '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . ($r - 7) . '" fill="#15212F"/>'
            . '<text x="' . $cx . '" y="' . ($cy + ($size * 0.36)) . '" text-anchor="middle" fill="#FFFFFF" font-family="Space Grotesk, Helvetica, Arial, sans-serif" font-size="' . $size . '" font-weight="700">' . self::xml($initials) . '</text>';
    }

    private static function taglineBlock(int $y, int $size, int $x = 540, string $anchor = 'middle', int $maxChars = 28): string
    {
        return self::textBlock(self::textLines(self::TAGLINE, $maxChars), $y, $size, '#FFFFFF', 500, $x, $anchor);
    }

    /**
     * @param list<string> $lines
     */
    private static function textBlock(array $lines, int $y, int $size, string $fill, int $weight, int $x = 540, string $anchor = 'middle'): string
    {
        $out = '';
        foreach ($lines as $i => $line) {
            $out .= '<text x="' . $x . '" y="' . ($y + ($i * (int) round($size * 1.2))) . '" text-anchor="' . $anchor . '" fill="' . $fill . '" font-family="Space Grotesk, Helvetica, Arial, sans-serif" font-size="' . $size . '" font-weight="' . $weight . '">' . self::xml($line) . '</text>';
        }

        return $out;
    }

    private static function qrGroup(string $url, int $x, int $y, int $size): string
    {
        $matrix = QrCode::matrix($url);
        $n = count($matrix);
        $margin = 2;
        $dim = $n + (2 * $margin);
        $cell = $size / $dim;
        $rects = '';
        foreach ($matrix as $row => $cols) {
            foreach ($cols as $col => $bit) {
                if ($bit !== 1) {
                    continue;
                }
                $rects .= '<rect x="' . sprintf('%.2f', ($col + $margin) * $cell) . '" y="' . sprintf('%.2f', ($row + $margin) * $cell) . '" width="' . sprintf('%.2f', $cell) . '" height="' . sprintf('%.2f', $cell) . '"/>';
            }
        }

        return '<g transform="translate(' . $x . ' ' . $y . ')">'
            . '<rect width="' . $size . '" height="' . $size . '" rx="18" fill="#FFFFFF"/>'
            . '<g fill="#15212F">' . $rects . '</g>'
            . '</g>';
    }

    private static function svgRoot(int $w, int $h, string $inner): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $w . ' ' . $h . '" width="' . $w . '" height="' . $h . '" role="img">'
            . $inner
            . '</svg>';
    }

    public static function signatureHtml(string $name, string $subtitle, string $url): string
    {
        $safeName = e($name);
        $safeSub = e($subtitle);
        $safeUrl = e($url);
        $safeLabel = e(preg_replace('#^https?://#i', '', $url) ?? $url);
        $tag = e(self::TAGLINE);
        $subLine = $safeSub !== '' ? '<div style="color:#4A5A6B;font-size:13px;line-height:1.4;margin:2px 0 0;">' . $safeSub . '</div>' : '';

        return '<table cellpadding="0" cellspacing="0" role="presentation" style="font-family:Helvetica,Arial,sans-serif;color:#15212F;">'
            . '<tr><td style="padding:0 16px 0 0;vertical-align:top;border-right:3px solid #EB963B;">'
            . '<div style="font-weight:700;font-size:15px;line-height:1.3;">' . $safeName . '</div>'
            . $subLine
            . '</td><td style="padding:0 0 0 16px;vertical-align:top;">'
            . '<div style="font-size:13px;line-height:1.4;color:#15212F;">' . $tag . '</div>'
            . '<a href="' . $safeUrl . '" style="color:#EB963B;font-size:13px;text-decoration:none;">' . $safeLabel . '</a>'
            . '</td></tr></table>';
    }

    public static function badgeHtml(string $url): string
    {
        $safeUrl = e($url);
        $label = e(self::TAGLINE);

        return '<a href="' . $safeUrl . '" style="display:inline-flex;align-items:center;gap:10px;background:#15212F;color:#FFFFFF;text-decoration:none;font-family:Helvetica,Arial,sans-serif;font-size:13px;font-weight:700;line-height:1.2;padding:10px 14px;border-radius:999px;">'
            . '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#EB963B;"></span>'
            . $label
            . '</a>';
    }

    public static function badgeSvg(string $url): string
    {
        $label = self::TAGLINE;
        $w = 420;
        $h = 44;

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '" role="img">'
            . '<a href="' . self::xml($url) . '">'
            . '<rect width="' . $w . '" height="' . $h . '" rx="22" fill="#15212F"/>'
            . '<circle cx="22" cy="22" r="5" fill="#EB963B"/>'
            . '<text x="36" y="28" fill="#FFFFFF" font-family="Space Grotesk, Helvetica, Arial, sans-serif" font-size="13" font-weight="700">' . self::xml($label) . '</text>'
            . '</a></svg>';
    }

    /**
     * @return list<string>
     */
    private static function textLines(string $text, int $max): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }
        $words = preg_split('/\s+/u', $text) ?: [$text];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $next = $current === '' ? $word : $current . ' ' . $word;
            if (mb_strlen($next) > $max && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $next;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return array_slice($lines, 0, 3);
    }

    private static function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
