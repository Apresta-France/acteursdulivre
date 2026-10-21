<?php

declare(strict_types=1);

namespace Adl\Data;

/** Retire les mentions de pré-ouverture devenues fausses depuis le 6 octobre 2026. */
final class FreshnessCopy
{
    public static function replace(string $text): string
    {
        return str_replace(
            [
                '— la plateforme est en pré-ouverture et n’acquiert aucun droit sur vos œuvres',
                '— la plateforme est en pré-ouverture et n\'acquiert aucun droit sur vos œuvres',
                'La plateforme, en pré-ouverture,',
                '— la plateforme est en pré-ouverture',
                '— pré-ouverture',
                ', en pré-ouverture,',
                '(pré-ouverture)',
                'La plateforme est en pré-ouverture',
                'la plateforme est en pré-ouverture',
                'le site est en pré-ouverture',
                ' et la pré-ouverture',
                'pré-ouverture, ',
            ],
            [
                '— la plateforme n’acquiert aucun droit sur vos œuvres. Fourchettes du marché, octobre 2026',
                '— la plateforme n\'acquiert aucun droit sur vos œuvres. Fourchettes du marché, octobre 2026',
                'La plateforme',
                '(fourchettes du marché, octobre 2026)',
                '— fourchettes du marché, octobre 2026',
                ',',
                '(fourchettes du marché, octobre 2026)',
                'Les fourchettes sont celles du marché en octobre 2026',
                'les fourchettes sont celles du marché en octobre 2026',
                'ces repères datent d\'octobre 2026',
                '',
                '',
            ],
            $text
        );
    }
}
