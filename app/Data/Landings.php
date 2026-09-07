<?php

declare(strict_types=1);

namespace Adl\Data;

use Adl\Core\Auth;
use Adl\Core\Request;
use Adl\Models\Article;
use Adl\Models\User;

final class Landings
{
    public const PREFIX = '/besoin';

    public const CLIENT_PROOFS = [
        'Travail humain uniquement : aucun livrable produit par une IA générative.',
        'Prestations à prix affiché, ou appel d’offres pour comparer des devis.',
        'Suivi à jalons. Le règlement se fait hors plateforme, entre vous.',
        'Aucun abonnement. Inscription gratuite.',
    ];

    public const OFFERER_PROOFS = [
        'Première mission réalisée offerte, puis 8 % hors taxes (6 % pour les 100 premiers inscrits).',
        'Vous fixez vos prix. Candidater à une recherche est gratuit.',
        'La plateforme n’encaisse pas les missions : le client vous règle directement.',
        'Moratoire IA générative : ni texte, ni image, ni voix de synthèse.',
    ];

    public const CLIENT_STEPS = [
        ['num' => '01', 'title' => 'Créez un compte', 'body' => 'Gratuit, en une minute. Vous cherchez un prestataire, vous en êtes un, ou les deux.'],
        ['num' => '02', 'title' => 'Choisissez ou publiez', 'body' => 'Une prestation cadrée, un profil, ou une recherche ouverte aux devis.'],
        ['num' => '03', 'title' => 'Suivez jusqu’à la livraison', 'body' => 'Devis, jalons, validation. Vous réglez le prestataire hors site.'],
    ];

    public const OFFERER_STEPS = [
        ['num' => '01', 'title' => 'Créez votre vitrine', 'body' => 'Métier, présentation, tarifs. Visible dans l’annuaire dès qu’elle est complète.'],
        ['num' => '02', 'title' => 'Proposez ou candidatez', 'body' => 'Fiches à prix affiché, ou réponses aux recherches ouvertes — sans frais de candidature.'],
        ['num' => '03', 'title' => 'Livrez, le client note', 'body' => 'La première mission est offerte. Ensuite la commission est le dernier jalon.'],
    ];

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $out = [];
        foreach (self::definitions() as $row) {
            $out[] = self::normalize($row);
        }
        return $out;
    }

    /** @return list<array<string, mixed>> */
    public static function forAudience(string $audience): array
    {
        return array_values(array_filter(
            self::all(),
            static fn (array $row): bool => ($row['audience'] ?? '') === $audience
        ));
    }

    public static function find(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }
        foreach (self::all() as $row) {
            if ($row['slug'] === $slug) {
                return $row;
            }
        }
        return null;
    }

    public static function path(string $slug): string
    {
        return self::PREFIX . '/' . ltrim($slug, '/');
    }

    public static function hubPath(): string
    {
        return self::PREFIX;
    }

    /**
     * @param array<string, string> $utm
     */
    public static function campaignUrl(string $slug, array $utm = []): string
    {
        $url = Share::absolute(self::path($slug));
        $query = [];
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $key) {
            $value = trim((string) ($utm[$key] ?? ''));
            if ($value !== '') {
                $query[$key] = $value;
            }
        }
        if ($query === []) {
            return $url;
        }
        return $url . '?' . http_build_query($query);
    }

    public static function rememberVisit(Request $request, string $slug): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $_SESSION['_landing'] = $slug;
        $source = self::utmValue($request, 'utm_source');
        $medium = self::utmValue($request, 'utm_medium');
        $campaign = self::utmValue($request, 'utm_campaign');
        if ($source === '' && $request->string('gclid') !== '') {
            $source = 'google';
            $medium = $medium !== '' ? $medium : 'cpc';
        }
        if ($source === '' && $request->string('fbclid') !== '') {
            $source = 'meta';
            $medium = $medium !== '' ? $medium : 'cpc';
        }
        if ($source !== '' || $medium !== '' || $campaign !== '') {
            $_SESSION['_utm'] = [
                'source' => $source,
                'medium' => $medium,
                'campaign' => $campaign !== '' ? $campaign : $slug,
                'content' => self::utmValue($request, 'utm_content'),
            ];
        }
        if (!isset($_SESSION['_intended'])) {
            $next = $request->string('next');
            $safe = $next !== '' ? safe_internal_path($next) : null;
            if ($safe !== null) {
                $_SESSION['_intended'] = $safe;
            }
        }
    }

    public static function fromSession(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        $slug = trim((string) ($_SESSION['_landing'] ?? ''));
        return $slug !== '' ? self::find($slug) : null;
    }

    public static function sessionUtm(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return [];
        }
        $utm = $_SESSION['_utm'] ?? [];
        return is_array($utm) ? $utm : [];
    }

    /** @param array<string, mixed>|null $user */
    public static function applySignupDefaults(Request $request, ?array $user = null): void
    {
        $slug = $request->string('besoin');
        $landing = $slug !== '' ? self::find($slug) : self::fromSession();
        if ($landing === null) {
            return;
        }
        self::rememberVisit($request, (string) $landing['slug']);
        if ($user !== null || !empty($_SESSION['_old'])) {
            return;
        }
        $offers = ($landing['audience'] ?? '') === 'prestataire';
        $_SESSION['_old'] = [
            'seeks_services' => $offers ? '' : '1',
            'offers_services' => $offers ? '1' : '',
        ];
    }

    /**
     * @param array<string, mixed> $landing
     * @param array<string, mixed>|null $user
     * @return array<string, mixed>
     */
    public static function present(array $landing, ?array $user = null): array
    {
        $user = $user ?? Auth::user();
        $logged = $user !== null;
        $seeks = User::seeksServices($user);
        $offers = User::offersServices($user);
        $trade = (string) ($landing['trade'] ?? '');
        $audience = (string) ($landing['audience'] ?? 'client');
        $slug = (string) $landing['slug'];
        $tradePath = $trade !== '' ? Catalog::tradePath($trade) : '/prestataires';
        $catalogQ = $trade !== '' ? '?cat=' . rawurlencode($trade) : '';

        if ($audience === 'prestataire') {
            $primaryHref = $logged
                ? ($offers ? '/espace/vitrine' : '/espace')
                : '/inscription?besoin=' . rawurlencode($slug) . '&offers_services=1';
            $secondaryHref = '/missions';
            $primaryLabel = $logged ? 'Ouvrir mon espace' : (string) $landing['cta_primary'];
            $secondaryLabel = (string) $landing['cta_secondary'];
        } else {
            $primaryHref = $logged
                ? ($seeks ? '/espace/publier' : '/inscription')
                : '/inscription?besoin=' . rawurlencode($slug);
            $secondaryHref = $trade !== '' ? '/prestataires' . $catalogQ : '/prestataires';
            $primaryLabel = $logged && $seeks ? 'Publier une recherche' : (string) $landing['cta_primary'];
            $secondaryLabel = (string) $landing['cta_secondary'];
        }

        $journal = null;
        $journalSlug = (string) ($landing['journal'] ?? '');
        if ($journalSlug !== '') {
            try {
                $article = Article::findBySlug($journalSlug);
            } catch (\Throwable) {
                $article = null;
            }
            if ($article && !empty($article['published'])) {
                $journal = [
                    'title' => (string) ($article['title'] ?? ''),
                    'excerpt' => (string) ($article['excerpt'] ?? ''),
                    'href' => (string) ($article['href'] ?? ('/journal/' . $journalSlug)),
                ];
            }
        }

        $others = [];
        foreach (self::all() as $other) {
            if ($other['slug'] === $slug || ($other['audience'] ?? '') !== $audience) {
                continue;
            }
            $others[] = [
                'slug' => $other['slug'],
                'need' => $other['need'],
                'kicker' => $other['kicker'],
                'href' => self::path((string) $other['slug']),
            ];
            if (count($others) >= 8) {
                break;
            }
        }

        return array_merge($landing, [
            'path' => self::path($slug),
            'trade_path' => $tradePath,
            'prestations_href' => '/prestations' . $catalogQ,
            'prestataires_href' => '/prestataires' . $catalogQ,
            'missions_href' => $trade !== '' ? '/missions?metier=' . rawurlencode($trade) : '/missions',
            'primary_href' => $primaryHref,
            'secondary_href' => $secondaryHref,
            'primary_label' => $primaryLabel,
            'secondary_label' => $secondaryLabel,
            'journal_page' => $journal,
            'others' => $others,
            'hero_img' => photo(abs(crc32($slug)) % 6),
        ]);
    }

    /**
     * @return list<array{loc: string, priority: string}>
     */
    public static function sitemapUrls(): array
    {
        $urls = [['loc' => self::hubPath(), 'priority' => '0.7']];
        foreach (self::all() as $row) {
            $urls[] = ['loc' => self::path((string) $row['slug']), 'priority' => '0.7'];
        }
        return $urls;
    }

    /** @param array<string, mixed> $row */
    private static function normalize(array $row): array
    {
        $audience = ($row['audience'] ?? 'client') === 'prestataire' ? 'prestataire' : 'client';
        $slug = (string) ($row['slug'] ?? '');
        return array_merge([
            'slug' => $slug,
            'audience' => $audience,
            'audience_label' => $audience === 'prestataire' ? 'Prestataire' : 'Porteur de projet',
            'trade' => '',
            'need' => '',
            'kicker' => '',
            'h1' => '',
            'lead' => '',
            'meta_title' => '',
            'meta_description' => '',
            'price' => '',
            'price_note' => 'Fourchettes du marché français 2026, pas des statistiques de la plateforme (pré-ouverture). Un devis sur votre fichier dit le prix réel.',
            'includes' => [],
            'excludes' => [],
            'steps' => $audience === 'prestataire' ? self::OFFERER_STEPS : self::CLIENT_STEPS,
            'proofs' => $audience === 'prestataire' ? self::OFFERER_PROOFS : self::CLIENT_PROOFS,
            'faq' => [],
            'journal' => '',
            'cta_primary' => $audience === 'prestataire' ? 'Créer ma vitrine' : 'Créer un compte',
            'cta_secondary' => $audience === 'prestataire' ? 'Voir les recherches ouvertes' : 'Voir les prestataires',
            'ad_headlines' => [],
            'ad_descriptions' => [],
            'ad_keywords' => [],
            'campaign' => $slug,
        ], $row);
    }

    private static function utmValue(Request $request, string $key): string
    {
        $value = trim($request->string($key));
        $value = preg_replace('/[\r\n<>"\']+/', '', $value) ?? '';
        $value = trim($value);
        if ($value === '' || mb_strlen($value) > 80) {
            return '';
        }
        return $value;
    }

    /** @return list<array<string, mixed>> */
    private static function definitions(): array
    {
        return [
            [
                'slug' => 'corriger-un-manuscrit',
                'trade' => 'Correction',
                'need' => 'Corriger un manuscrit',
                'kicker' => 'Correction',
                'h1' => 'Faire corriger un manuscrit',
                'lead' => 'Trouvez un correcteur pour une passe orthotypographique, une relecture sur épreuves, ou une préparation de copie. Prix affichés ou devis, travail humain uniquement.',
                'meta_title' => 'Faire corriger un manuscrit — correcteurs du livre',
                'meta_description' => 'Correcteurs pour roman, essai ou jeunesse. 620 à 1 100 € pour un roman courant. Sans IA générative, devis comparables.',
                'price' => '620 à 1 100 €',
                'price_detail' => 'Roman d’environ 520 000 signes, passe orthotypographique. Une préparation de copie du même volume se situe plutôt entre 1 200 et 2 400 € — autre métier, autre brief.',
                'includes' => [
                    'Orthographe, typographie, cohérence des graphies.',
                    'Un fichier annoté, souvent un rapport de passe.',
                    'Un délai et un volume (signes ou feuillets) écrits avant de commencer.',
                ],
                'excludes' => [
                    'La réécriture, le coaching, la bêta-lecture.',
                    'Une « correction IA » relue à la va-vite.',
                    'La mise en pages : c’est la maquette, après le texte stabilisé.',
                ],
                'faq' => [
                    ['q' => 'Correction ou préparation de copie ?', 'a' => 'La correction chasse les fautes sur un texte que vous assumez. La préparation de copie recoud la syntaxe, les répétitions, le rythme. Ce n’est pas le même tarif, ni le même brief. En cas de doute, publiez une recherche : les devis le diront.'],
                    ['q' => 'Faut-il un compte pour demander un devis ?', 'a' => 'Oui, l’inscription est gratuite. Vous publiez une recherche ou vous commandez une prestation cadrée. Le prestataire vous règle hors plateforme ; la plateforme suit les jalons.'],
                    ['q' => 'Travaillez-vous avec l’IA ?', 'a' => 'Non, pas pour les livrables. Les outils de métier (correcteur orthographique, mémoire) restent autorisés. Un texte « corrigé » par une IA générative n’a pas sa place ici.'],
                ],
                'journal' => 'cout-correction-manuscrit-2026',
                'cta_secondary' => 'Voir les correcteurs',
                'ad_headlines' => ['Corriger un manuscrit', 'Correcteurs du livre', 'Sans IA générative'],
                'ad_descriptions' => [
                    'Trouvez un correcteur pour votre roman. Travail humain, devis clairs, sans IA.',
                    '620 à 1 100 € pour un roman courant. Inscription gratuite, règlement hors site.',
                ],
                'ad_keywords' => ['correction manuscrit', 'correcteur roman', 'relecture manuscrit', 'orthotypographie', 'faire corriger un livre'],
            ],
            [
                'slug' => 'preparation-de-copie',
                'trade' => 'Correction',
                'need' => 'Préparation de copie',
                'kicker' => 'Correction',
                'h1' => 'Faire préparer un manuscrit',
                'lead' => 'La préparation de copie recoud le texte : syntaxe, répétitions, rythme, avant la passe orthotypographique. Ce n’est pas une correction, ce n’est pas une réécriture.',
                'meta_title' => 'Préparation de copie — manuscrit prêt pour la correction',
                'meta_description' => 'Préparation de copie pour roman ou essai : 1 200 à 2 400 € pour un volume courant. Distinct de la correction. Sans IA générative.',
                'price' => '1 200 à 2 400 €',
                'price_detail' => 'Même volume qu’un roman courant (~520 000 signes). Moins cher, ce n’est plus de la prépa ; plus cher, on bascule souvent vers une réécriture.',
                'includes' => [
                    'Lissage syntaxique, redondances, phrases bancales.',
                    'Un fichier travaillé, avec un périmètre écrit (ce qui se touche, ce qui ne se touche pas).',
                    'Un texte que vous pourrez ensuite faire corriger.',
                ],
                'excludes' => [
                    'La chasse aux fautes seule (c’est la correction).',
                    'Une réécriture de structure, de personnages ou d’intrigue.',
                    'La mise en pages ou la couverture.',
                ],
                'faq' => [
                    ['q' => 'Puis-je enchaîner prépa puis correction ?', 'a' => 'Oui, c’est l’ordre utile. Corriger un texte encore instable, c’est payer deux fois : chaque coupe déplace les fautes. Deux missions, deux devis, ou un prestataire qui fait les deux — écrit noir sur blanc.'],
                    ['q' => 'Le préparateur peut-il « juste » corriger au passage ?', 'a' => 'Il peut signaler. Au tarif d’une prépa, il ne livre pas une chasse orthotypographique complète. Si le besoin change en cours de route, on reclasse le devis.'],
                ],
                'journal' => 'preparation-copie-ou-correction',
                'cta_secondary' => 'Voir les correcteurs',
                'ad_headlines' => ['Préparation de copie', 'Manuscrit à lisser', 'Avant la correction'],
                'ad_descriptions' => [
                    'Faites préparer votre manuscrit avant la correction. Périmètre écrit, travail humain.',
                    '1 200 à 2 400 € pour un roman courant. Distinct de la chasse aux fautes.',
                ],
                'ad_keywords' => ['préparation de copie', 'preparateur de copie', 'lisser un manuscrit', 'stylistique manuscrit'],
            ],
            [
                'slug' => 'beta-lecture',
                'trade' => 'Bêta-lecture',
                'need' => 'Bêta-lecture',
                'kicker' => 'Bêta-lecture',
                'h1' => 'Faire bêta-lire un manuscrit',
                'lead' => 'Un rapport sur la cohérence, le rythme et les personnages — pas une chasse aux fautes, pas une réécriture. Un avis de lecteur professionnel avant la correction.',
                'meta_title' => 'Bêta-lecture de manuscrit — rapport avant correction',
                'meta_description' => 'Bêta-lecteurs pour roman : 180 à 450 €. Rapport sur le rythme et les personnages, distinct de la correction. Sans IA.',
                'price' => '180 à 450 €',
                'price_detail' => 'Roman, selon volume, profondeur du rapport et délai. Le livrable type est un rapport structuré, parfois des annotations — pas un fichier « corrigé ».',
                'includes' => [
                    'Un avis argumenté : trou, rythme, personnages, public visé.',
                    'Une grille ou un format de rapport annoncé à l’avance.',
                    'Un délai de lecture réaliste, pas « pour demain ».',
                ],
                'excludes' => [
                    'La correction orthotypographique.',
                    'La lecture éditoriale d’un comité de maison.',
                    'Le commentaire d’un ami qui « a bien aimé ».',
                ],
                'faq' => [
                    ['q' => 'Bêta-lecture ou lecture éditoriale ?', 'a' => 'La bêta-lecture parle au manuscrit comme un lecteur exigeant. La lecture éditoriale évalue une ligne, un public, un potentiel de publication. Ce n’est pas le même destinataire, ni le même rapport.'],
                    ['q' => 'Faut-il corriger avant ?', 'a' => 'Non : la bêta précède la correction. Un texte encore plein de fautes fatigue le lecteur et brouille le diagnostic. Une passe légère suffit ; la chasse complète vient après, une fois le texte assumé.'],
                ],
                'journal' => 'beta-lecture-rapport-avant-correction',
                'cta_secondary' => 'Voir les bêta-lecteurs',
                'ad_headlines' => ['Bêta-lecture de roman', 'Rapport avant correction', 'Avis de lecteur pro'],
                'ad_descriptions' => [
                    'Faites lire votre manuscrit avant de le corriger. Rapport clair, 180 à 450 €.',
                    'Cohérence, rythme, personnages. Pas une chasse aux fautes. Sans IA générative.',
                ],
                'ad_keywords' => ['bêta lecture', 'beta lecteur', 'rapport de lecture manuscrit', 'faire lire mon roman'],
            ],
            [
                'slug' => 'couverture-de-livre',
                'trade' => 'Illustration',
                'need' => 'Couverture de livre',
                'kicker' => 'Illustration',
                'h1' => 'Faire illustrer une couverture',
                'lead' => 'Illustrateurs pour couverture, intérieur ou album. Style, format et droits précisés avant commande. Aucune image générée par IA.',
                'meta_title' => 'Couverture de livre illustrée — illustrateurs',
                'meta_description' => 'Illustration de couverture : 300 à 900 € hors cession large. Typo seule : 250 à 500 €. Droits cadrés, sans IA.',
                'price' => '300 à 900 €',
                'price_detail' => 'Illustration originale, cession limitée au livre. Une couverture typographique soignée (composition 1re, 4e, dos) se situe plutôt entre 250 et 500 € — souvent un maquettiste, pas un illustrateur.',
                'includes' => [
                    'Un visuel original, un nombre de propositions annoncé.',
                    'Les usages cédés : livre, e-pub, parfois affiche de salon.',
                    'Un fichier imprimable, fonds perdus, dos si le contrat le dit.',
                ],
                'excludes' => [
                    'Une image générée par IA « retravaillée ».',
                    'La cession mondiale, tous supports, durée des droits — sauf si c’est écrit et tarifé.',
                    'La maquette intérieure du livre.',
                ],
                'faq' => [
                    ['q' => 'Illustrateur ou maquettiste ?', 'a' => 'L’illustrateur livre une image. Le maquettiste compose la couverture (typo, dos, 4e) et l’intérieur. Beaucoup de livres ont besoin des deux. Un forfait « couverture comprise » mélange souvent les deux métiers : exigez le détail.'],
                    ['q' => 'Qui détient les droits ?', 'a' => 'Vous achetez un usage, pas « le fichier pour toujours partout ». Cession limitée au livre, ou plus large : c’est un poste. Un juriste du livre recadre si la campagne dépasse l’ouvrage.'],
                ],
                'journal' => 'cout-couverture-roman-illustree',
                'cta_secondary' => 'Voir les illustrateurs',
                'ad_headlines' => ['Couverture de livre', 'Illustration originale', 'Sans image IA'],
                'ad_descriptions' => [
                    'Faites illustrer votre couverture. Style, format et droits cadrés. Sans IA.',
                    '300 à 900 € pour une illustration originale. Inscription gratuite.',
                ],
                'ad_keywords' => ['illustration couverture roman', 'illustrateur livre', 'couverture de livre', 'faire illustrer un livre'],
            ],
            [
                'slug' => 'maquette-de-livre',
                'trade' => 'Maquette',
                'need' => 'Maquette de livre',
                'kicker' => 'Maquette',
                'h1' => 'Faire maquetter un livre',
                'lead' => 'Maquettistes pour l’intérieur, la couverture graphique et le PDF d’impression. Marges, justification, dos calculé — pas un Word « mis en page ».',
                'meta_title' => 'Maquette intérieure de livre — graphistes éditoriaux',
                'meta_description' => 'Maquette roman : 700 à 1 400 € intérieur + couverture graphique. PDF d’impression, dos calculé. Sans IA.',
                'price' => '700 à 1 400 €',
                'price_detail' => 'Lot intérieur + couverture graphique, roman courant. La couverture typo seule : 250 à 500 €. L’illustration originale s’ajoute.',
                'includes' => [
                    'Une grille, des styles, un PDF aux normes d’impression.',
                    'Le calcul du dos une fois le papier connu.',
                    'Un nombre de passages d’épreuves annoncé.',
                ],
                'excludes' => [
                    'La correction du texte (elle précède la maquette).',
                    'L’illustration originale, sauf mention contraire.',
                    'Un fichier InDesign « offert » si ce n’est pas écrit.',
                ],
                'faq' => [
                    ['q' => 'Quand envoyer le texte au maquettiste ?', 'a' => 'Quand il est stabilisé et corrigé. Corriger après mise en pages coûte plus cher : chaque coupe déplace veuves, folios, parfois le dos.'],
                    ['q' => 'Graphiste ou illustrateur ?', 'a' => 'Le graphiste / maquettiste compose. L’illustrateur dessine. Un « graphiste » trouvé hors métier du livre livre souvent une couverture écran, pas un PDF/X.'],
                ],
                'journal' => 'maquette-interieure-livre',
                'cta_secondary' => 'Voir les maquettistes',
                'ad_headlines' => ['Maquette de livre', 'PDF d’impression', 'Intérieur et couverture'],
                'ad_descriptions' => [
                    'Faites maquetter votre livre : intérieur, dos, PDF d’impression. 700 à 1 400 €.',
                    'Maquettistes du livre, pas un Word mis en page. Inscription gratuite.',
                ],
                'ad_keywords' => ['maquette livre', 'mise en page roman', 'graphiste éditorial', 'pdf impression livre'],
            ],
            [
                'slug' => 'imprimer-un-livre',
                'trade' => 'Impression',
                'need' => 'Imprimer un livre',
                'kicker' => 'Impression',
                'h1' => 'Faire imprimer un livre',
                'lead' => 'Imprimeurs pour tirage court, numérique ou offset : format, papier, façonnage et suivi de production. Un devis sur votre PDF, pas un tarif magique au kilo.',
                'meta_title' => 'Imprimer un livre — tirage court, numérique, offset',
                'meta_description' => 'Impression 300 romans brochés : 1 100 à 1 600 €. POD 4 à 8 € l’exemplaire. Comparez les devis, sans abonnement.',
                'price' => '1 100 à 1 600 €',
                'price_detail' => '300 romans brochés en numérique court, hors port. POD : 4 à 8 € l’unité, pertinent sous 50 à 80 exemplaires. L’offset devient intéressant vers 500 à 800 exemplaires.',
                'includes' => [
                    'Un devis nommé : papier, grammage, façonnage, quantité.',
                    'Un BAT ou une épreuve selon le procédé.',
                    'Un délai de fabrication, pas une date de salon inventée.',
                ],
                'excludes' => [
                    'La maquette et le PDF aux normes — en amont.',
                    'Le dépôt légal, l’ISBN, la diffusion en librairie.',
                    'Le port, souvent un poste à part.',
                ],
                'faq' => [
                    ['q' => 'POD, numérique ou offset ?', 'a' => 'POD pour tester ou dépanner. Numérique court vers 100 à 400 exemplaires. Offset quand le tirage justifie le calage. Le papier et le façonnage pèsent autant que le procédé.'],
                    ['q' => 'Dois-je fournir un PDF/X ?', 'a' => 'Oui, c’est le livrable du maquettiste. Un PDF écran, polices non enrobées, dos au hasard : l’imprimeur rattrape ou refuse. Prévoyez la maquette avant le devis d’impression.'],
                ],
                'journal' => 'impression-pod-numerique-offset',
                'cta_secondary' => 'Voir les imprimeurs',
                'ad_headlines' => ['Imprimer un livre', 'Tirage court & offset', 'Devis comparables'],
                'ad_descriptions' => [
                    'Faites imprimer votre livre : 300 ex. brochés, 1 100 à 1 600 € hors port.',
                    'Imprimeurs du livre, papier et façonnage nommés. Inscription gratuite.',
                ],
                'ad_keywords' => ['imprimer un livre', 'imprimeur roman', 'tirage livre 300 exemplaires', 'impression à la demande livre'],
            ],
            [
                'slug' => 'traduire-un-livre',
                'trade' => 'Traduction',
                'need' => 'Traduire un livre',
                'kicker' => 'Traduction',
                'h1' => 'Faire traduire un livre',
                'lead' => 'Traducteurs pour roman, essai, jeunesse ou document. Un devis au signe ou au mot, un couple de langues, un public — pas une « traduction IA relue ».',
                'meta_title' => 'Traduction littéraire — devis au signe ou au mot',
                'meta_description' => 'Traduction littéraire : 0,12 à 0,22 € le mot source, ou 18 à 28 € le feuillet. Sans IA générative.',
                'price' => '0,12 à 0,22 € / mot',
                'price_detail' => 'Mot source, marché français 2026. Autre unité courante : 18 à 28 € le feuillet de 1 500 signes. Convertissez avant de comparer deux devis.',
                'includes' => [
                    'Une traduction humaine, un nombre de relectures annoncé.',
                    'Le couple de langues et le genre, écrits.',
                    'Les droits : édition papier, numérique, parfois audio — s’ils sont cédés.',
                ],
                'excludes' => [
                    'Une sortie machine « juste à relire ».',
                    'La révision par un tiers, sauf si le devis la prévoit.',
                    'La cession mondiale tous supports par défaut.',
                ],
                'faq' => [
                    ['q' => 'Pourquoi au signe plutôt qu’à la page ?', 'a' => 'Une page Word n’est pas une unité. Le signe (ou le mot source) permet de comparer. Un devis « au forfait roman » sans volume est illisible.'],
                    ['q' => 'Faut-il un juriste pour la cession ?', 'a' => 'Pour un roman simple, un contrat clair suffit souvent. Dès qu’il y a plusieurs langues, un enchérisseur ou une maison en face, un conseil aide. L’agent n’est pas obligatoire.'],
                ],
                'journal' => 'devis-traduction-au-signe',
                'cta_secondary' => 'Voir les traducteurs',
                'ad_headlines' => ['Traduire un livre', 'Traduction littéraire', 'Devis au mot source'],
                'ad_descriptions' => [
                    'Faites traduire votre roman. 0,12 à 0,22 € le mot, travail humain.',
                    'Traducteurs du livre, sans IA générative. Inscription gratuite.',
                ],
                'ad_keywords' => ['traduction littéraire', 'traduire un roman', 'traducteur anglais français livre', 'devis traduction au signe'],
            ],
            [
                'slug' => 'prete-plume',
                'trade' => 'Écriture',
                'need' => 'Prête-plume',
                'kicker' => 'Écriture',
                'h1' => 'Trouver un prête-plume',
                'lead' => 'Un auteur pour écrire ou réécrire à votre place : roman, récit de vie, essai. Volume, signature et cession doivent être écrits avant le premier chapitre.',
                'meta_title' => 'Prête-plume et ghostwriting — cadrer la mission',
                'meta_description' => 'Prête-plume : 4 000 à 15 000 € pour un roman de 250 à 350 pages. Confidentialité, signature et droits cadrés. Sans IA.',
                'price' => '4 000 à 15 000 €',
                'price_detail' => 'Roman de 250 à 350 pages, selon volume en signes, genre, documentation et nombre de versions. Un récit de vie déjà structuré n’est pas un thriller à intrigues multiples.',
                'includes' => [
                    'Un volume livré (signes, pas pages Word).',
                    'Qui signe, qui reste invisible, ce qui est cédé.',
                    'Un nombre de versions et un calendrier.',
                ],
                'excludes' => [
                    'Un livre « écrit par une IA » que l’auteur recoud.',
                    'Un coaching d’écriture (autre métier, autre tarif).',
                    'La publication et la diffusion : la plateforme ne les fait pas.',
                ],
                'faq' => [
                    ['q' => 'Le prête-plume peut-il signer ?', 'a' => 'Seulement si le contrat le dit. Par défaut, le commanditaire signe. L’inverse, ou un « avec la collaboration de », se négocie. Sans clause, c’est un malentendu.'],
                    ['q' => 'Coach ou prête-plume ?', 'a' => 'Le coach vous fait écrire. Le prête-plume écrit. Au-delà d’un cycle de coaching (souvent 400 à 1 500 €), vous approchez le prix d’une réécriture. Ne mélangez pas les briefs.'],
                ],
                'journal' => 'prete-plume-ghostwriting-cadrer-mission',
                'cta_secondary' => 'Voir les auteurs',
                'ad_headlines' => ['Trouver un prête-plume', 'Ghostwriting littéraire', 'Contrat et droits clairs'],
                'ad_descriptions' => [
                    'Faites écrire votre livre : 4 000 à 15 000 € pour un roman. Sans IA.',
                    'Volume, signature et cession écrits avant le premier chapitre.',
                ],
                'ad_keywords' => ['prête-plume', 'ghostwriter roman', 'faire écrire un livre', 'écrivain public récit de vie'],
            ],
            [
                'slug' => 'livre-audio',
                'trade' => 'Audio',
                'need' => 'Livre audio',
                'kicker' => 'Audio',
                'h1' => 'Faire narrer un livre audio',
                'lead' => 'Voix humaine, studio, mastering : un roman de 300 pages, ce n’est pas « quelques heures de micro ». Pas de synthèse vocale.',
                'meta_title' => 'Narration de livre audio — voix humaine',
                'meta_description' => 'Livre audio, roman 300 pages : 1 200 à 3 500 € de narration, hors mastering. Voix humaine, pas de synthèse.',
                'price' => '1 200 à 3 500 €',
                'price_detail' => 'Narration d’un roman d’environ 300 pages (8 à 14 heures de texte lu, trois à cinq fois ce temps en studio). Hors mastering et hors droits étendus.',
                'includes' => [
                    'Une voix humaine, un ton et un public cadrés.',
                    'Un temps de studio et un livrable (fichiers, pistes).',
                    'Les usages : plateforme, site d’auteur, SP — s’ils sont cédés.',
                ],
                'excludes' => [
                    'Une voix de synthèse, même « clone » de l’auteur.',
                    'La production phonographique complète par défaut.',
                    'Le mastering, souvent un poste distinct.',
                ],
                'faq' => [
                    ['q' => 'Combien d’heures de studio ?', 'a' => 'Comptez trois à cinq fois la durée du texte lu. 10 heures de roman, ce n’est pas 10 heures de cabine. La préparation et les reprises pèsent.'],
                    ['q' => 'Puis-je narrer moi-même ?', 'a' => 'Oui, si vous tenez la cabine et le rythme. Ce n’est pas « moins cher » dès que l’on compte le temps, les reprises et le mastering. Un narrateur livre un métier.'],
                ],
                'journal' => 'livre-audio-temps-studio-300-pages',
                'cta_secondary' => 'Voir les narrateurs',
                'ad_headlines' => ['Livre audio narré', 'Voix humaine', 'Pas de synthèse vocale'],
                'ad_descriptions' => [
                    'Faites narrer votre roman : 1 200 à 3 500 €, voix humaine.',
                    'Studio, délai et droits cadrés. Aucune voix de synthèse.',
                ],
                'ad_keywords' => ['livre audio narration', 'narrateur roman', 'faire un livre audio', 'voix off littéraire'],
            ],
            [
                'slug' => 'attache-de-presse',
                'trade' => 'Presse & com',
                'need' => 'Attaché de presse',
                'kicker' => 'Presse & com',
                'h1' => 'Trouver un attaché de presse livre',
                'lead' => 'Un fichier, des envois, des relances, un compte rendu. Un mois de SP n’est pas une campagne de pub. Prestataires spécialisés livre.',
                'meta_title' => 'Attaché de presse livre — service de presse',
                'meta_description' => 'Attaché de presse : 800 à 2 500 € le mois, hors exemplaires. Fichier, relances, retombées. Pas une campagne d’influence.',
                'price' => '800 à 2 500 €',
                'price_detail' => 'Un mois d’attaché de presse, hors exemplaires et hors port. Ce n’est pas le prix d’une « visibilité » achetée. Une campagne publicitaire (affichage, sponsorisé) est un autre métier.',
                'includes' => [
                    'Cadrage de l’angle, liste d’envois, relances, point de fin de mois.',
                    'Un nombre d’exemplaires ou de PDF à prévoir de votre côté.',
                    'Un compte rendu : pièces, pas « impressions ».',
                ],
                'excludes' => [
                    'L’achat d’espace, l’influence rémunérée, les goodies — sauf devis.',
                    'La garantie d’un article dans un national.',
                    'La community au quotidien, si ce n’est pas le brief.',
                ],
                'faq' => [
                    ['q' => 'Combien d’exemplaires ?', 'a' => 'Vingt, c’est un SP local. Quatre-vingts, une campagne déjà large. Au-delà, pour un premier roman sans angle, le taux de lecture s’effondre. L’AP honnête le dit.'],
                    ['q' => 'Presse ou réseaux ?', 'a' => 'Ce sont deux briefs. BookTok n’est pas un mois d’attaché. Un prestataire presse peut gérer une liste ; ce n’est pas « faire du TikTok » au quotidien.'],
                ],
                'journal' => 'attache-de-presse-livre',
                'cta_secondary' => 'Voir la presse & com',
                'ad_headlines' => ['Attaché de presse livre', 'Service de presse', 'Relances et retombées'],
                'ad_descriptions' => [
                    'Un mois d’AP livre : 800 à 2 500 € hors exemplaires. Fichier tenu.',
                    'Pas une campagne de pub. Prestataires spécialisés livre.',
                ],
                'ad_keywords' => ['attaché de presse livre', 'service de presse roman', 'promotion livre', 'faire connaître mon livre'],
            ],
            [
                'slug' => 'coach-ecriture',
                'trade' => 'Coach littéraire',
                'need' => 'Coach d’écriture',
                'kicker' => 'Coach littéraire',
                'h1' => 'Trouver un coach d’écriture',
                'lead' => 'Séances, cycle, mentorat ou atelier : trois mots, trois livrables. Un accompagnement pour faire avancer un texte, sans IA générative.',
                'meta_title' => 'Coach littéraire — accompagnement d’écriture',
                'meta_description' => 'Coach d’écriture : 60 à 120 € de l’heure, cycle 400 à 1 500 €. Distinct du prête-plume. Sans IA générative.',
                'price' => '60 à 120 € / h',
                'price_detail' => 'Séance d’accompagnement. Un cycle de quelques semaines se négocie plutôt entre 400 et 1 500 €. Au-delà, vous approchez une réécriture, voire un prête-plume.',
                'includes' => [
                    'Un format : séance, cycle, mentorat, atelier — nommé.',
                    'Ce qui se lit entre deux rendez-vous, et ce qui ne se lit pas.',
                    'Un texte que vous continuez d’écrire.',
                ],
                'excludes' => [
                    'Le ghostwriting (l’écriture à votre place).',
                    'La correction orthotypographique.',
                    'La garantie d’être publié.',
                ],
                'faq' => [
                    ['q' => 'Coach, mentorat, atelier ?', 'a' => 'La séance est un rendez-vous. Le cycle a un calendrier et des livrables. L’atelier est souvent un groupe. Comparer une heure et un forfait de huit semaines n’a pas de sens.'],
                    ['q' => 'Le coach corrige-t-il ?', 'a' => 'Il peut signaler. Il ne livre pas une passe de correction au tarif d’une séance. Deux métiers, deux devis si vous voulez les deux.'],
                ],
                'journal' => 'accompagnement-ecriture-coach-mentorat',
                'cta_secondary' => 'Voir les coachs',
                'ad_headlines' => ['Coach d’écriture', 'Accompagner un roman', 'Séance ou cycle'],
                'ad_descriptions' => [
                    'Trouvez un coach littéraire : 60 à 120 € de l’heure, sans IA.',
                    'Séance, cycle ou atelier : le livrable est écrit avant de commencer.',
                ],
                'ad_keywords' => ['coach littéraire', 'coach écriture roman', 'accompagnement manuscrit', 'atelier d’écriture'],
            ],
            [
                'slug' => 'lecture-editoriale',
                'trade' => 'Lecture éditoriale',
                'need' => 'Lecture éditoriale',
                'kicker' => 'Lecture éditoriale',
                'h1' => 'Faire évaluer un manuscrit',
                'lead' => 'Un avis de lecteur éditorial : ligne, public, potentiel. Distinct de la bêta-lecture. Utile avant d’envoyer à une maison, ou pour décider de retravailler.',
                'meta_title' => 'Lecture éditoriale et comité de lecture',
                'meta_description' => 'Faites évaluer un manuscrit : ligne, public, potentiel. Distinct de la bêta-lecture. Lecteurs éditoriaux du livre.',
                'price' => 'Sur devis',
                'price_detail' => 'Selon volume, profondeur du rapport et délai. Ce n’est pas le tarif d’une bêta-lecture (180 à 450 €) : le destinataire n’est pas le même.',
                'includes' => [
                    'Un rapport : forces, faiblesses, public, positionnement.',
                    'Un avis de publication possible — pas une promesse de contrat.',
                    'Un volume et un délai écrits.',
                ],
                'excludes' => [
                    'La bêta-lecture « lecteur complice ».',
                    'La correction, la réécriture.',
                    'L’envoi du manuscrit aux maisons à votre place (plutôt l’agent).',
                ],
                'faq' => [
                    ['q' => 'À quoi ça sert si je m’autoédite ?', 'a' => 'À savoir si le texte tient, pour qui, et ce qui cloche avant d’investir maquette et impression. Ce n’est pas obligatoire. C’est un regard froid, payé.'],
                    ['q' => 'Le lecteur peut-il « faire passer » en maison ?', 'a' => 'Non. Un rapport n’est pas un sésame. Un agent, une collection, un réseau : d’autres métiers. Ici on évalue le texte, on ne le place pas.'],
                ],
                'journal' => 'lecture-editoriale-comite-de-lecture',
                'cta_secondary' => 'Voir les lecteurs éditoriaux',
                'ad_headlines' => ['Lecture éditoriale', 'Évaluer un manuscrit', 'Avant d’envoyer'],
                'ad_descriptions' => [
                    'Faites évaluer votre manuscrit : ligne, public, potentiel.',
                    'Distinct de la bêta-lecture. Lecteurs éditoriaux, sans IA.',
                ],
                'ad_keywords' => ['lecture éditoriale', 'comité de lecture manuscrit', 'évaluation manuscrit', 'rapport éditorial'],
            ],
            [
                'slug' => 'iconographie',
                'trade' => 'Iconographie',
                'need' => 'Iconographie',
                'kicker' => 'Iconographie',
                'h1' => 'Trouver les images d’un ouvrage',
                'lead' => 'Iconographes pour rechercher, légender et négocier les visuels. Ce n’est ni un photographe de commande, ni un illustrateur.',
                'meta_title' => 'Iconographe — images et droits pour un livre',
                'meta_description' => 'Iconographie d’ouvrage : recherche, légendes, droits. Sources et usages précisés dès le brief. Sans images IA.',
                'price' => 'Sur devis',
                'price_detail' => 'Selon le nombre de visuels, les sources (archives, presse, collections) et la négociation des droits. Un reportage de commande est une mission photo, pas de l’iconographie.',
                'includes' => [
                    'La recherche, la vérif des droits, les légendes et crédits.',
                    'Un dossier exploitable par le maquettiste.',
                    'Le périmètre : nombre d’images, usages, territoires.',
                ],
                'excludes' => [
                    'La photographie de commande (portraits, objets).',
                    'L’illustration originale.',
                    'Les images générées par IA.',
                ],
                'faq' => [
                    ['q' => 'Un roman a-t-il besoin d’un iconographe ?', 'a' => 'Rarement, sauf essai illustré, documentaire, beau livre. Un roman illustré, c’est plutôt un illustrateur. Les images d’archives, c’est l’iconographe.'],
                    ['q' => 'Qui paie les droits ?', 'a' => 'Souvent vous, en plus de la mission. L’iconographe négocie ou oriente. Un forfait « images comprises » sans liste de droits est un piège.'],
                ],
                'journal' => 'iconographie-images-ouvrage',
                'cta_secondary' => 'Voir les iconographes',
                'ad_headlines' => ['Iconographe livre', 'Images et droits', 'Légendes & crédits'],
                'ad_descriptions' => [
                    'Trouvez, légendez et négociez les visuels d’un ouvrage.',
                    'Iconographes du livre. Sources et usages cadrés. Sans IA.',
                ],
                'ad_keywords' => ['iconographe', 'iconographie livre', 'droits d’images ouvrage', 'légendes illustrations'],
            ],
            [
                'slug' => 'photographe-auteur',
                'trade' => 'Photographie',
                'need' => 'Photographe auteur',
                'kicker' => 'Photographie',
                'h1' => 'Faire photographier un auteur ou un ouvrage',
                'lead' => 'Portraits, photos de livres, reportage de salon. Usage et cession de droits cadrés avant la séance. Pas une banque d’images IA.',
                'meta_title' => 'Photographe du livre — portraits d’auteurs',
                'meta_description' => 'Portraits d’auteurs, photos d’ouvrages, reportage. Cession de droits écrite. Photographes du livre, sans IA.',
                'price' => 'Sur devis',
                'price_detail' => 'Selon la séance, le nombre de fichiers livrés et la largeur de la cession (livre, site, presse, campagne). Un portrait « tous supports, tous territoires » se tarifie.',
                'includes' => [
                    'Une séance, un nombre de fichiers, un usage nommé.',
                    'La retouche prévue — et celle qui se facture en plus.',
                    'Un contrat de cession, même court.',
                ],
                'excludes' => [
                    'L’iconographie d’archives.',
                    'L’illustration de couverture dessinée.',
                    'Une cession illimitée « comprise » si ce n’est pas écrit.',
                ],
                'faq' => [
                    ['q' => 'Portrait pour le rabat, c’est suffisant ?', 'a' => 'Pour un rabat, souvent oui. Pour une campagne, une exclusivité ou une cession à une maison, relisez la cession. Un juriste du livre le fait en une heure.'],
                    ['q' => 'Studio obligatoire ?', 'a' => 'Non. Un visage net, de face, en lumière simple, fait l’affaire pour une fiche. Le studio se justifie pour une campagne ou un beau livre.'],
                ],
                'journal' => 'portrait-auteur-photos-ouvrage-cession',
                'cta_secondary' => 'Voir les photographes',
                'ad_headlines' => ['Portrait d’auteur', 'Photo d’ouvrage', 'Cession de droits claire'],
                'ad_descriptions' => [
                    'Faites photographier l’auteur ou le livre. Usages et droits écrits.',
                    'Photographes du livre. Pas une image générée. Inscription gratuite.',
                ],
                'ad_keywords' => ['photographe auteur', 'portrait écrivain', 'photo de livre', 'cession photo ouvrage'],
            ],
            [
                'slug' => 'reliure-artisanale',
                'trade' => 'Reliure',
                'need' => 'Reliure artisanale',
                'kicker' => 'Reliure',
                'h1' => 'Faire relier un livre',
                'lead' => 'Reliure d’art, restauration ou petits tirages soignés. Le dos et le carton pèsent autant que le papier. Matériaux et quantité précisés.',
                'meta_title' => 'Reliure d’art et façonnage de livres',
                'meta_description' => 'Reliure artisanale : 40 à 180 € l’ouvrage. Cartonnage de série : 0,80 à 2,50 € par exemplaire. Relieurs du livre.',
                'price' => '40 à 180 € / ouvrage',
                'price_detail' => 'Reliure artisanale à la pièce. Un cartonnage de série ajoute 0,80 à 2,50 € par exemplaire au devis d’impression. Broché collé, cousu, cartonnage : ce n’est pas le même métier.',
                'includes' => [
                    'Une technique nommée, des matériaux, une quantité.',
                    'Un délai — la reliure d’art n’est pas un façonnage de chaîne.',
                    'Le calage du dos avec le maquettiste ou l’imprimeur.',
                ],
                'excludes' => [
                    'L’impression du bloc, sauf si l’atelier le propose.',
                    'La restauration patrimoniale lourde, si ce n’est pas le brief.',
                    'Un « effet livre ancien » en série industrielle.',
                ],
                'faq' => [
                    ['q' => 'Relieur ou imprimeur ?', 'a' => 'L’imprimeur façonne souvent le broché. Le relieur intervient pour le cartonnage soigné, la pièce unique, la restauration. Un devis d’impression « reliure comprise » précise rarement le carton.'],
                    ['q' => 'Combien d’exemplaires ?', 'a' => 'La pièce se tarifie à l’ouvrage. Au-delà de quelques dizaines, discutez un petit tirage. La série appartient plutôt à l’imprimeur.'],
                ],
                'journal' => 'reliure-faconnage-prix',
                'cta_secondary' => 'Voir les relieurs',
                'ad_headlines' => ['Reliure artisanale', 'Façonnage de livre', 'Dos et carton'],
                'ad_descriptions' => [
                    'Faites relier votre livre : 40 à 180 € l’ouvrage artisanal.',
                    'Relieurs du livre. Matériaux et quantité précisés dès le brief.',
                ],
                'ad_keywords' => ['reliure artisanale', 'relieur livre', 'cartonnage livre', 'faire relier un manuscrit'],
            ],
            [
                'slug' => 'contrat-edition',
                'trade' => 'Juridique',
                'need' => 'Contrat d’édition',
                'kicker' => 'Juridique',
                'h1' => 'Faire relire un contrat d’édition',
                'lead' => 'Cessions, à-valoir, durée, numérique, audio : un juriste du livre lit ce qu’un malentendu coûte en six mois. La plateforme ne rédige pas à votre place un contrat type magique.',
                'meta_title' => 'Juriste du livre — contrats, cessions, droits',
                'meta_description' => 'Contrats d’édition, cessions, copyright. Juristes spécialisés livre. Un conseil, pas un modèle téléchargé.',
                'price' => 'Sur devis',
                'price_detail' => 'Selon l’acte (relecture, rédaction, litige) et l’urgence. Une heure de relecture de contrat n’est pas un contentieux. Demandez le format : note, avenant, rendez-vous.',
                'includes' => [
                    'Un acte nommé : relecture, rédaction, mise en demeure, etc.',
                    'Un spécialiste du livre, pas un généraliste « tous contrats ».',
                    'Une restitution écrite, pas seulement un coup de fil.',
                ],
                'excludes' => [
                    'Un modèle Word « contrat d’édition 2026 » vendu comme talisman.',
                    'La garantie de gagner un procès.',
                    'L’agent littéraire (autre mandat).',
                ],
                'faq' => [
                    ['q' => 'Puis-je signer sans juriste ?', 'a' => 'Oui, beaucoup le font. Lisez au moins cession, durée, numérique, à-valoir, reddition. Un juriste devient utile dès qu’une clause vous échappe ou qu’une maison accélère.'],
                    ['q' => 'La plateforme édite-t-elle ?', 'a' => 'Non. acteursdulivre.fr met en relation. Les contrats sont conclus entre vous et le prestataire, ou vous et une maison. Pas de droits sur les ouvrages.'],
                ],
                'journal' => 'contrat-edition-clauses-a-lire',
                'cta_secondary' => 'Voir les juristes',
                'ad_headlines' => ['Contrat d’édition', 'Juriste du livre', 'Cessions et droits'],
                'ad_descriptions' => [
                    'Faites relire un contrat d’édition, une cession, un avenant.',
                    'Juristes spécialisés livre. Un conseil, pas un modèle générique.',
                ],
                'ad_keywords' => ['contrat d’édition', 'juriste droits d’auteur', 'cession droits livre', 'relire contrat maison'],
            ],
            [
                'slug' => 'agent-litteraire',
                'trade' => 'Agent littéraire',
                'need' => 'Agent littéraire',
                'kicker' => 'Agent littéraire',
                'h1' => 'Trouver un agent littéraire',
                'lead' => 'Placer un manuscrit, négocier un contrat, suivre une carrière. Un premier roman n’a pas toujours besoin d’un agent — et ce n’est pas un échec.',
                'meta_title' => 'Agent littéraire — mandat et périmètre',
                'meta_description' => 'Agents littéraires : mandat écrit, commission, périmètre. Pas une garantie d’être publié. Pour un projet qui en a besoin.',
                'price' => 'Commission sur contrats',
                'price_detail' => 'L’usage est un pourcentage sur les contrats négociés, pas un forfait « je vous fais publier ». Un mandat sans périmètre (titres, territoires, durée) est dangereux.',
                'includes' => [
                    'Un mandat : quels titres, quelles langues, quelle durée.',
                    'Une commission écrite, sur quoi elle porte — et sur quoi elle ne porte pas.',
                    'Un suivi, pas une promesse de contrat dans le mois.',
                ],
                'excludes' => [
                    'La garantie d’une publication.',
                    '15 % sur des ventes de salon que vous avez faites seul, sauf clause.',
                    'L’édition de votre livre par la plateforme.',
                ],
                'faq' => [
                    ['q' => 'A-t-on besoin d’un agent en 2026 ?', 'a' => 'Si le projet a besoin d’un intermédiaire pour atteindre des maisons, négocier, ou suivre plusieurs contrats : oui. Pour un premier roman en envoi spontané ou en autoédition, souvent non. Les agents le disent eux-mêmes.'],
                    ['q' => 'L’autoédition interdit-elle un agent plus tard ?', 'a' => 'Non. Elle le rend souvent inutile sur le titre autoédité, sauf mandat de cession à un tiers.'],
                ],
                'journal' => 'a-t-on-besoin-agent-litteraire-2026',
                'cta_secondary' => 'Voir les agents',
                'ad_headlines' => ['Agent littéraire', 'Mandat et commission', 'Placer un manuscrit'],
                'ad_descriptions' => [
                    'Trouvez un agent : mandat écrit, périmètre clair, pas de promesse.',
                    'Pour un projet qui en a besoin. Inscription gratuite.',
                ],
                'ad_keywords' => ['agent littéraire', 'trouver un agent', 'placer un manuscrit', 'mandat agent livre'],
            ],
            [
                'slug' => 'accompagnement-editorial',
                'trade' => 'Édition',
                'need' => 'Accompagnement éditorial',
                'kicker' => 'Édition',
                'h1' => 'Se faire accompagner pour éditer',
                'lead' => 'Ligne, calendrier, assistant d’édition. La plateforme met en relation : elle n’édite pas les ouvrages, elle ne prend pas de droits.',
                'meta_title' => 'Accompagnement éditorial — direction de collection',
                'meta_description' => 'Éditeurs et assistants d’édition pour cadrer un ouvrage. La plateforme n’est pas une maison d’édition.',
                'price' => 'Sur devis',
                'price_detail' => 'Selon l’accompagnement (ligne, planning, suivi de fabrication, collection). Ce n’est ni un contrat d’édition maison, ni un forfait « je vous publie ».',
                'includes' => [
                    'Un rôle nommé : conseil, direction, assistance.',
                    'Un calendrier et un périmètre (jusqu’où va la mission).',
                    'La clarté : vous restez porteur du projet, sauf contrat d’édition ailleurs.',
                ],
                'excludes' => [
                    'Un contrat d’édition signé avec acteursdulivre.fr — ça n’existe pas.',
                    'La prise de droits sur votre texte.',
                    'L’impression, la diffusion, la presse — sauf missions distinctes.',
                ],
                'faq' => [
                    ['q' => 'Vous êtes une maison d’édition ?', 'a' => 'Non. EDITIONS TESSERACT édite la plateforme, pas vos ouvrages. Les contrats d’édition se concluent avec une maison, ou vous assumez l’autoédition et vous embauchez des prestataires.'],
                    ['q' => 'À quoi sert un « éditeur » ici ?', 'a' => 'À un accompagnement : ligne, planning, regard. Pas à « être publié par la plateforme ». Si vous cherchez un contrat maison, un agent ou des envois ciblés sont d’autres chemins.'],
                ],
                'journal' => 'isbn-depot-legal-afnil-france',
                'cta_secondary' => 'Voir les éditeurs',
                'ad_headlines' => ['Accompagnement éditorial', 'Pas une maison d’édition', 'Ligne et calendrier'],
                'ad_descriptions' => [
                    'Faites accompagner votre projet : ligne, planning, assistance.',
                    'La plateforme met en relation, elle n’édite pas vos livres.',
                ],
                'ad_keywords' => ['accompagnement éditorial', 'assistant d’édition', 'direction de collection', 'aide à l’autoédition'],
            ],
            [
                'slug' => 'diffusion-en-librairie',
                'trade' => 'Librairie',
                'need' => 'Diffusion en librairie',
                'kicker' => 'Librairie',
                'h1' => 'Déposer un livre en librairie',
                'lead' => 'Dépôt, diffusion, e-commerce, événements. Un libraire n’est pas un distributeur national. Zone, titre, conditions : à écrire.',
                'meta_title' => 'Libraires — dépôt et diffusion d’un livre',
                'meta_description' => 'Déposer un livre en librairie : conditions, office, événement. Libraires, pas un réseau national magique.',
                'price' => 'Selon accord',
                'price_detail' => 'Dépôt, office, achat ferme, animation : chaque libraire a ses règles. Ce n’est pas un tarif unique. Un diffuseur national est un autre métier, un autre contrat.',
                'includes' => [
                    'Une zone, un titre, un nombre d’exemplaires, un taux.',
                    'Les retours, s’ils existent — écrits.',
                    'Un éventuel événement (dédicace, table).',
                ],
                'excludes' => [
                    'Une mise en place nationale automatique.',
                    'La garantie d’être en tête de gondole.',
                    'La fabrication du livre (impression, ISBN).',
                ],
                'faq' => [
                    ['q' => 'Un libraire prendra-t-il mon autoédité ?', 'a' => 'Parfois, en dépôt, si le titre a un ancrage local ou un angle. Le libraire n’est pas un service après-vente de l’autoédition. Présentez l’ouvrage, les conditions, un exemplaire soigné.'],
                    ['q' => 'Faut-il un diffuseur ?', 'a' => 'Pour un réseau, souvent oui. Pour trois librairies autour de chez vous, un dépôt direct peut suffire. Ne payez pas une diffusion nationale pour vingt exemplaires.'],
                ],
                'journal' => 'depot-librairie-ce-que-le-libraire-attend',
                'cta_secondary' => 'Voir les libraires',
                'ad_headlines' => ['Dépôt en librairie', 'Diffusion locale', 'Parler au libraire'],
                'ad_descriptions' => [
                    'Déposez votre livre en librairie : conditions écrites, zone claire.',
                    'Libraires, pas un distributeur magique. Inscription gratuite.',
                ],
                'ad_keywords' => ['dépôt librairie autoédition', 'diffuser un livre', 'libraire dépôt vente', 'faire entrer son livre en librairie'],
            ],
            [
                'slug' => 'proposer-mes-services',
                'audience' => 'prestataire',
                'trade' => '',
                'need' => 'Proposer mes services',
                'kicker' => 'Prestataires',
                'h1' => 'Trouver des missions dans les métiers du livre',
                'lead' => 'Créez votre vitrine, publiez des prestations à prix affiché, répondez aux recherches. Aucun abonnement. La première mission réalisée est offerte, puis 8 % hors taxes.',
                'meta_title' => 'Prestataires du livre — vitrine et missions',
                'meta_description' => 'Correcteurs, illustrateurs, imprimeurs : créez votre vitrine. 1re mission offerte, puis 8 %. Sans IA générative, sans abonnement.',
                'price' => '1ʳᵉ mission offerte, puis 8 %',
                'price_detail' => 'Commission hors taxes, facturée au prestataire lorsque le client valide et note. 6 % pour les 100 premiers inscrits, et dès 12 missions réalisées. Le client vous règle hors plateforme.',
                'includes' => [
                    'Une vitrine publique, des fiches, les appels d’offres.',
                    'Candidatures illimitées, sans frais.',
                    'Un suivi à jalons. Vous encaissez le prix de la mission.',
                ],
                'excludes' => [
                    'Un abonnement mensuel.',
                    'L’achat de mise en avant publicitaire interne.',
                    'Les livrables générés par IA.',
                ],
                'faq' => [
                    ['q' => 'Qui paie la commission ?', 'a' => 'Vous, prestataire, après la première mission offerte. Le client n’a pas de frais de plateforme. La commission se calcule sur le hors-taxe, dernier jalon, payable sous 15 jours.'],
                    ['q' => 'Comment arrivent les clients ?', 'a' => 'Annuaire, prestations, recherches, journal, newsletter, et des pages par besoin pour les campagnes. Nous ne vendons pas un volume de commandes. Les deux côtés de la place de marché se construisent.'],
                    ['q' => 'Quand ouvre-t-on aux clients ?', 'a' => 'Les auteurs et professionnels s’inscrivent déjà. L’ouverture aux clients est annoncée pour octobre 2026. Les auteurs déjà présents sont souvent les premiers à commander.'],
                ],
                'journal' => '',
                'cta_primary' => 'Créer ma vitrine',
                'cta_secondary' => 'Voir les recherches ouvertes',
                'ad_headlines' => ['Missions du livre', '1re mission offerte', 'Sans abonnement'],
                'ad_descriptions' => [
                    'Correcteurs, illustrateurs, imprimeurs : créez votre vitrine.',
                    'Première mission offerte, puis 8 %. Sans IA générative.',
                ],
                'ad_keywords' => ['missions correction livre', 'trouver des clients illustrateur', 'freelance métiers du livre', 'vitrine correcteur'],
            ],
        ];
    }
}
