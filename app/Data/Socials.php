<?php

declare(strict_types=1);

namespace Adl\Data;

final class Socials
{
    public const FACEBOOK = 'https://www.facebook.com/acteursdulivre/';
    public const INSTAGRAM = 'https://www.instagram.com/acteursdulivre.fr/';

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
        ];
    }

    /** @return list<string> */
    public static function sameAs(): array
    {
        return [
            'https://editions-tesseract.fr/',
            self::FACEBOOK,
            self::INSTAGRAM,
        ];
    }
}
