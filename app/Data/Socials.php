<?php

declare(strict_types=1);

namespace Adl\Data;

final class Socials
{
    public const FACEBOOK = 'https://www.facebook.com/acteursdulivre/';
    public const INSTAGRAM = 'https://www.instagram.com/acteursdulivre.fr/';
    public const LINKEDIN = 'https://www.linkedin.com/showcase/acteurs-du-livre/';

    /**
     * Comptes officiels (suivre), distincts des boutons de partage d'une page.
     *
     * @return list<array{id: string, short: string, label: string, href: string}>
     */
    public static function profiles(): array
    {
        return [
            [
                'id' => 'facebook',
                'short' => 'FB',
                'label' => 'Nous suivre sur Facebook',
                'href' => self::FACEBOOK,
            ],
            [
                'id' => 'instagram',
                'short' => 'IG',
                'label' => 'Nous suivre sur Instagram',
                'href' => self::INSTAGRAM,
            ],
            [
                'id' => 'linkedin',
                'short' => 'IN',
                'label' => 'Nous suivre sur LinkedIn',
                'href' => self::LINKEDIN,
            ],
        ];
    }

    public static function idFromHref(string $href): string
    {
        $href = strtolower($href);
        foreach (['facebook', 'instagram', 'linkedin'] as $id) {
            if (str_contains($href, $id . '.com')) {
                return $id;
            }
        }

        return '';
    }

    public static function iconUrl(string $id, bool $onDark = false): string
    {
        if (!in_array($id, ['facebook', 'instagram', 'linkedin'], true)) {
            return '';
        }

        return asset('img/social/' . $id . ($onDark ? '-blanc' : '') . '.png');
    }

    /** Icône + libellé, en tableau pour les clients mail. */
    public static function iconLabel(string $id, string $label, bool $onDark = false, int $size = 13): string
    {
        $src = self::iconUrl($id, $onDark);
        if ($src === '') {
            return e($label);
        }
        $color = $onDark ? '#ffffff' : '#022746';
        $size = max(12, min(18, $size));

        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;"><tr>'
            . '<td style="padding:0 6px 0 0;vertical-align:middle;line-height:0;font-size:0;">'
            . '<img src="' . e($src) . '" width="16" height="16" alt="" style="display:block;border:0;width:16px;height:16px;">'
            . '</td>'
            . '<td style="vertical-align:middle;color:' . $color . ';font-weight:600;font-family:Helvetica,Arial,sans-serif;font-size:' . $size . 'px;line-height:16px;">'
            . e($label)
            . '</td></tr></table>';
    }

    /** @return list<string> */
    public static function sameAs(): array
    {
        return [
            'https://editions-tesseract.fr/',
            self::FACEBOOK,
            self::INSTAGRAM,
            self::LINKEDIN,
        ];
    }
}
