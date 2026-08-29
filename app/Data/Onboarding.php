<?php

declare(strict_types=1);

namespace Adl\Data;

use Adl\Models\Mission;
use Adl\Models\Profile;
use Adl\Models\User;

final class Onboarding
{
    public const TITLE_HINTS = [
        'Correction' => 'Correcteur·rice, romans et essais',
        'Bêta-lecture' => 'Bêta-lecture, romans contemporains',
        'Illustration' => 'Illustration de couverture et intérieur',
        'Traduction' => 'Traduction littéraire EN → FR',
        'Maquette' => 'Maquette intérieure et mise en page',
        'Édition' => 'Accompagnement éditorial',
        'Impression' => 'Impression de livres, petits et moyens tirages',
        'Presse & com' => 'Attaché·e de presse et visibilité du livre',
        'Librairie' => 'Librairie indépendante, conseil et diffusion',
        'Audio' => 'Narration de livres audio',
        'Iconographie' => 'Iconographe, essais et documentaires',
        'Lecture éditoriale' => 'Lecture éditoriale, romans et essais',
        'Photographie' => 'Photographie d\'auteurs et d\'ouvrages',
        'Reliure' => 'Reliure d\'art et petits tirages',
        'Juridique' => 'Contrats d\'édition et droits d\'auteur',
    ];

    public const PRESENTATION_HINTS = [
        'Correction' => 'Précisez les genres que vous corrigez, les maisons ou auteurs avec qui vous avez travaillé, et votre délai habituel.',
        'Bêta-lecture' => 'Dites quel public vous aidez à viser, ce que vous relevez dans un manuscrit, et comment vous restituez.',
        'Illustration' => 'Décrivez votre trait, les techniques, et le type d’ouvrages (jeunesse, couverture, intérieur).',
        'Traduction' => 'Indiquez les couples de langues, les genres, et votre rapport aux contraintes éditoriales.',
        'Maquette' => 'Précisez les formats, les contraintes graphiques, et les types d’ouvrages que vous mettez en page.',
        'Édition' => 'Expliquez l’accompagnement que vous proposez, du manuscrit à la fabrication.',
        'Impression' => 'Donnez les procédés, les papiers, les tirages et les délais que vous tenez.',
        'Presse & com' => 'Dites quelles cibles vous savez atteindre, et comment vous travaillez avec l’auteur ou la maison.',
        'Librairie' => 'Présentez votre fonds, votre zone, et la façon dont vous accueillez les ouvrages.',
        'Audio' => 'Précisez votre timbre, les genres narrés, et les contraintes techniques que vous maîtrisez.',
        'Iconographie' => 'Précisez les domaines (histoire, sciences, jeunesse…), les sources que vous maîtrisez, et votre rapport aux droits.',
        'Lecture éditoriale' => 'Dites quels genres vous évaluez, ce que contient votre rapport, et pour qui vous lisez (maisons, auteurs, agents).',
        'Photographie' => 'Décrivez vos usages (portrait, ouvrage, reportage), votre lumière, et les cessions que vous proposez.',
        'Reliure' => 'Indiquez les techniques, les matériaux, et les volumes que vous acceptez.',
        'Juridique' => 'Précisez vos domaines (contrats, cessions, image, litiges) et le type de clients que vous accompagnez.',
    ];

    /** @param array<string, mixed> $user */
    public static function isPending(array $user): bool
    {
        return empty($user['onboarding_done_at']);
    }

    /** @param array<string, mixed> $user */
    public static function homePath(array $user): string
    {
        return self::isPending($user) ? '/espace/bienvenue' : '/espace';
    }

    /**
     * @param array<string, mixed> $user
     * @return list<array{id: string, label: string, short: string}>
     */
    public static function plan(array $user): array
    {
        $steps = [
            ['id' => 'identite', 'label' => 'Vous', 'short' => 'Photo et nom'],
        ];
        if (User::offersServices($user)) {
            $steps[] = ['id' => 'vitrine', 'label' => 'Votre fiche', 'short' => 'Métier et présentation'];
        }
        if (User::seeksServices($user)) {
            $steps[] = ['id' => 'mission', 'label' => 'Première recherche', 'short' => 'Décrire le besoin'];
        }
        $steps[] = ['id' => 'apercu', 'label' => 'Aperçu', 'short' => 'Voir le résultat'];
        return $steps;
    }

    /**
     * @param array<string, mixed> $user
     * @param list<array{id: string, label: string, short: string}> $plan
     */
    public static function resolveStep(array $user, string $requested, array $plan): string
    {
        $ids = array_column($plan, 'id');
        if ($requested !== '' && in_array($requested, $ids, true)) {
            return $requested;
        }
        return $ids[0] ?? 'identite';
    }

    /**
     * @param list<array{id: string, label: string, short: string}> $plan
     */
    public static function nextStep(string $current, array $plan): ?string
    {
        $ids = array_column($plan, 'id');
        $index = array_search($current, $ids, true);
        if ($index === false) {
            return $ids[0] ?? null;
        }
        return $ids[(int) $index + 1] ?? null;
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed>|null $profile
     * @return list<array{id: string, title: string, body: string, href: string, cta: string, weight: int}>
     */
    public static function priorities(array $user, ?array $profile, int $missionCount): array
    {
        $items = [];
        $offers = User::offersServices($user);
        $seeks = User::seeksServices($user);
        $avatar = user_avatar_src($user);

        if ($avatar === '') {
            $items[] = [
                'id' => 'avatar',
                'title' => 'Ajouter une photo',
                'body' => 'Une photo rend le compte immédiatement plus humain, dans l’espace comme sur une fiche.',
                'href' => '/espace/bienvenue?etape=identite',
                'cta' => 'Choisir une photo',
                'weight' => 40,
            ];
        }

        if ($seeks && $missionCount === 0) {
            $items[] = [
                'id' => 'mission',
                'title' => 'Publier une première recherche',
                'body' => 'C’est le plus court chemin vers des devis. Métier, titre et quelques lignes suffisent.',
                'href' => '/espace/bienvenue?etape=mission',
                'cta' => 'Décrire le besoin',
                'weight' => 95,
            ];
        }

        if ($offers) {
            $profile = $profile ?? [];
            $title = trim((string) ($profile['title'] ?? ''));
            $trades = $profile['trades'] ?? [];
            $presentation = trim((string) ($profile['presentation'] ?? ''));
            $city = trim((string) ($profile['city'] ?? ''));

            if ($trades === []) {
                $items[] = [
                    'id' => 'trades',
                    'title' => 'Choisir au moins un métier',
                    'body' => 'Sans métier, votre fiche n’apparaît pas dans l’annuaire. Trois maximum.',
                    'href' => '/espace/bienvenue?etape=vitrine',
                    'cta' => 'Choisir un métier',
                    'weight' => 100,
                ];
            }
            if ($title === '') {
                $items[] = [
                    'id' => 'title',
                    'title' => 'Donner un titre à votre fiche',
                    'body' => 'C’est la première ligne lue : métier, spécialité, ou positionnement en une phrase.',
                    'href' => '/espace/bienvenue?etape=vitrine',
                    'cta' => 'Écrire le titre',
                    'weight' => 90,
                ];
            }
            if (mb_strlen($presentation) < 80) {
                $items[] = [
                    'id' => 'presentation',
                    'title' => 'Écrire une présentation',
                    'body' => 'Deux ou trois phrases sur votre façon de travailler suffisent pour commencer.',
                    'href' => '/espace/bienvenue?etape=vitrine',
                    'cta' => 'Rédiger',
                    'weight' => 80,
                ];
            }
            if ($city === '') {
                $items[] = [
                    'id' => 'city',
                    'title' => 'Indiquer une ville',
                    'body' => 'Les porteurs de projet filtrent souvent par lieu, même pour un travail à distance.',
                    'href' => '/espace/bienvenue?etape=vitrine',
                    'cta' => 'Ajouter la ville',
                    'weight' => 55,
                ];
            }
            if (empty($profile['hourly_rate'])) {
                $items[] = [
                    'id' => 'rate',
                    'title' => 'Afficher un tarif',
                    'body' => 'Un ordre de grandeur évite les échanges trop vagues. Vous pourrez l’affiner plus tard.',
                    'href' => '/espace/vitrine',
                    'cta' => 'Compléter la vitrine',
                    'weight' => 35,
                ];
            }
            if (empty($profile['portfolio'])) {
                $items[] = [
                    'id' => 'portfolio',
                    'title' => 'Ajouter une création',
                    'body' => 'Un extrait, une couverture ou un ouvrage publié rassure plus qu’une longue bio.',
                    'href' => '/espace/vitrine',
                    'cta' => 'Ouvrir le portfolio',
                    'weight' => 30,
                ];
            }
        }

        usort($items, static fn (array $a, array $b): int => $b['weight'] <=> $a['weight']);
        return $items;
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed>|null $profile
     * @return array{title: string, body: string, tip: string}
     */
    public static function coach(string $step, array $user, ?array $profile, int $missionCount): array
    {
        $first = (string) ($user['first_name'] ?? '');
        $offers = User::offersServices($user);
        $seeks = User::seeksServices($user);

        if ($step === 'identite') {
            $body = $offers
                ? 'Cette photo et ce nom apparaîtront sur votre fiche publique. Vous pourrez les changer ensuite.'
                : 'Cette photo et ce nom vous identifient auprès des prestataires. Vous pourrez les changer ensuite.';
            return [
                'title' => $first !== '' ? 'Bonjour ' . $first : 'Bienvenue',
                'body' => $body,
                'tip' => 'Un visage reconnaissable suffit : pas besoin d’un studio. Un portrait net, de face, fait l’affaire.',
            ];
        }

        if ($step === 'vitrine') {
            $missing = [];
            if (empty($profile['trades'])) {
                $missing[] = 'un métier';
            }
            if (trim((string) ($profile['title'] ?? '')) === '') {
                $missing[] = 'un titre';
            }
            $tip = $missing !== []
                ? 'Pour exister dans l’annuaire, il faut au moins ' . self::joinFr($missing) . '.'
                : 'Votre fiche peut déjà apparaître. Une présentation de quelques lignes la rend beaucoup plus crédible.';
            return [
                'title' => 'Esquissez votre fiche',
                'body' => 'Quatre champs suffisent pour qu’un porteur de projet comprenne qui vous êtes. Le reste pourra attendre.',
                'tip' => $tip,
            ];
        }

        if ($step === 'mission') {
            return [
                'title' => 'Une première recherche',
                'body' => $missionCount > 0
                    ? 'Vous avez déjà une recherche. Vous pouvez en publier une autre, ou passer à l’aperçu.'
                    : 'Décrivez le besoin comme vous le feriez à un confrère : métier, ouvrage, ce que vous attendez.',
                'tip' => 'Plus le brief est précis, plus les devis sont justes. Le règlement se fait ensuite hors plateforme, jalon par jalon.',
            ];
        }

        $done = [];
        if ($offers && $profile && (trim((string) ($profile['title'] ?? '')) !== '' || !empty($profile['trades']))) {
            $done[] = 'une fiche visible';
        }
        if ($seeks && $missionCount > 0) {
            $done[] = 'une recherche publiée';
        }
        $summary = $done !== []
            ? 'Vous partez avec ' . self::joinFr($done) . '.'
            : 'Vous pourrez tout compléter depuis votre espace, à votre rythme.';

        return [
            'title' => 'Voici ce que l’on verra',
            'body' => $summary . ' Les prochaines étapes sont là pour aller plus loin, pas pour tout finir aujourd’hui.',
            'tip' => $offers
                ? 'Les vitrines précises reçoivent nettement plus de demandes. Un tarif et une création font souvent la différence.'
                : 'Parcourir l’annuaire et laisser une recherche ouverte sont deux façons complémentaires de avancer.',
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @return array{user: array<string, mixed>, profile: array<string, mixed>|null, missionCount: int, missions: list<array<string, mixed>>, plan: list<array{id: string, label: string, short: string}>, priorities: list<array<string, mixed>>}
     */
    public static function context(array $user): array
    {
        $profile = null;
        $missions = [];
        try {
            if (User::offersServices($user)) {
                $profile = Profile::findByUser((int) $user['id']);
            }
        } catch (\Throwable) {
        }
        try {
            if (User::seeksServices($user)) {
                $missions = Mission::forUser((int) $user['id']);
            }
        } catch (\Throwable) {
        }

        return [
            'user' => $user,
            'profile' => $profile,
            'missionCount' => count($missions),
            'missions' => $missions,
            'plan' => self::plan($user),
            'priorities' => self::priorities($user, $profile, count($missions)),
        ];
    }

    /** @param list<string> $parts */
    private static function joinFr(array $parts): string
    {
        $parts = array_values(array_filter($parts));
        $n = count($parts);
        if ($n === 0) {
            return '';
        }
        if ($n === 1) {
            return $parts[0];
        }
        return implode(', ', array_slice($parts, 0, -1)) . ' et ' . $parts[$n - 1];
    }
}
