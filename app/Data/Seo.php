<?php

declare(strict_types=1);

namespace Adl\Data;

final class Seo
{
    public const BRAND = 'Acteurs du Livre';
    public const BRAND_HOST = 'acteursdulivre.fr';
    public const DEFAULT_DESC = 'Place de marché des métiers du livre : correcteurs, illustrateurs, traducteurs, imprimeurs. Prestations à prix affiché, suivi à jalons, règlement hors plateforme. Sans IA générative.';
    public const ROBOTS_INDEX = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    public const ROBOTS_NONE = 'noindex, nofollow';
    public const OG_W = 1200;
    public const OG_H = 630;
    public const OG_ALT = 'Un livre, ça se fait à plusieurs — la place de marché des métiers du livre';

    private const PRIVATE_SCREENS = [
        'dashboard', 'publier', 'commande', 'suivi', 'commandes', 'mesmissions',
        'candidatures', 'mesprestations', 'creer', 'messagerie', 'notifications',
        'favoris', 'avis', 'vitrine', 'parametres', 'facturation', 'statistiques', 'bienvenue',
        'connexion', 'inscription-sso',
    ];

    /** @return array<string, array{title: string, description: string, path: string}> */
    public static function catalog(): array
    {
        return [
            'accueil' => [
                'title' => 'Acteurs du Livre — la place de marché des métiers du livre',
                'description' => self::DEFAULT_DESC,
                'path' => '/',
            ],
            'comment' => [
                'title' => 'Comment ça marche',
                'description' => 'Cherchez un profil, commandez une prestation ou publiez un appel d\'offres. Suivi à jalons, règlement hors plateforme. Première mission offerte, puis 8 % hors taxes au prestataire.',
                'path' => '/comment-ca-marche',
            ],
            'tarifs' => [
                'title' => 'Tarifs et commission 8 %',
                'description' => 'Aucun abonnement. Vitrine, fiches et appels d\'offres gratuits. Première mission offerte, puis 8 % hors taxes (6 % pour les 100 premiers inscrits, et dès 12 missions réalisées).',
                'path' => '/tarifs',
            ],
            'confiance' => [
                'title' => 'Confiance et sécurité',
                'description' => 'Profils vérifiés, suivi à jalons sans encaissement, avis liés à une mission réelle et médiation des litiges.',
                'path' => '/confiance',
            ],
            'apropos' => [
                'title' => 'À propos d\'Acteurs du Livre',
                'description' => 'EDITIONS TESSERACT édite acteursdulivre.fr, place de marché des métiers du livre. Mise en relation uniquement : pas d\'édition, pas de droits sur les ouvrages, pas d\'encaissement des missions.',
                'path' => '/a-propos',
            ],
            'journal' => [
                'title' => 'Le journal des métiers du livre',
                'description' => 'Prix observés, méthodes, contrats et retours d\'expérience sur la fabrication d\'un livre — du manuscrit à la librairie.',
                'path' => '/journal',
            ],
            'forum' => [
                'title' => 'Le forum des métiers du livre',
                'description' => 'Tarifs, contrats, papier, délais et cas concrets : les réponses viennent de gens qui font le métier — pas d\'une machine.',
                'path' => '/forum',
            ],
            'aide' => [
                'title' => 'Centre d\'aide',
                'description' => 'Jalons, règlement hors plateforme, commission, annulation, facturation, avis, litiges : les réponses utiles pour utiliser acteursdulivre.fr.',
                'path' => '/aide',
            ],
            'questions' => [
                'title' => 'Questions fréquentes',
                'description' => 'Quand la commission est prise, comment les clients arrivent, qui paie quoi : les réponses qu\'on nous pose le plus souvent.',
                'path' => '/questions',
            ],
            'contact' => [
                'title' => 'Contact',
                'description' => 'Écrire à l\'équipe d\'acteursdulivre.fr : question, signalement ou demande presse. Réponse en jours ouvrés.',
                'path' => '/contact',
            ],
            'missions' => [
                'title' => 'Appels d\'offres du livre',
                'description' => 'Missions ouvertes : correction, illustration, traduction, impression. Candidatez avec votre devis, sans commission sur la candidature.',
                'path' => '/missions',
            ],
            'resultats' => [
                'title' => 'Annuaire des métiers du livre',
                'description' => 'Parcourez les prestataires, prestations à prix affiché et recherches ouvertes des métiers du livre.',
                'path' => '/recherche',
            ],
            'prestations' => [
                'title' => 'Prestations des métiers du livre',
                'description' => 'Offres packagées : correction, illustration, traduction, maquette, impression. Prix, délai et périmètre affichés.',
                'path' => '/prestations',
            ],
            'prestataires' => [
                'title' => 'Prestataires des métiers du livre',
                'description' => 'Correcteurs, illustrateurs, traducteurs, maquettistes, imprimeurs : profils publics à parcourir.',
                'path' => '/prestataires',
            ],
            'inscription' => [
                'title' => 'Créer un compte professionnel',
                'description' => 'Inscrivez-vous sur acteursdulivre.fr : auteurs et professionnels du livre. Ouverture aux clients en octobre 2026. Sans IA générative.',
                'path' => '/inscription',
            ],
            'connexion' => [
                'title' => 'Connexion',
                'description' => 'Accédez à votre espace acteursdulivre.fr.',
                'path' => '/connexion',
            ],
        ];
    }

    /** @return array{h1: string, lead: string, description: string} */
    public static function tradeCopy(string $trade): array
    {
        $title = Catalog::tradeTitle($trade);
        $map = [
            'Écriture' => [
                'h1' => 'Auteurs, prête-plume et réécriture',
                'lead' => 'Trouvez un auteur ou un prête-plume pour un roman, un essai ou un récit. Prestations cadrées ou appel d\'offres, sans IA générative.',
            ],
            'Correction' => [
                'h1' => 'Correction et relecture de manuscrit',
                'lead' => 'Trouvez un correcteur pour une passe orthotypographique, une préparation de copie ou une relecture sur épreuves. Prix affichés ou devis, travail humain uniquement.',
            ],
            'Bêta-lecture' => [
                'h1' => 'Bêta-lecture et rapport de lecture',
                'lead' => 'Confiez votre manuscrit à un bêta-lecteur : cohérence, rythme, personnages et public visé. Un avis de lecteur professionnel avant la correction.',
            ],
            'Illustration' => [
                'h1' => 'Illustration et couverture de livre',
                'lead' => 'Illustrateurs pour couverture, intérieur ou album. Style, format et droits précisés avant commande. Aucune image générée par IA.',
            ],
            'Traduction' => [
                'h1' => 'Traduction littéraire et éditoriale',
                'lead' => 'Traducteurs pour roman, essai, jeunesse ou document. Langues, public et contraintes éditoriales cadrés dès le brief.',
            ],
            'Maquette' => [
                'h1' => 'Maquette intérieure et direction artistique',
                'lead' => 'Maquettistes pour la mise en pages, la typographie et le PDF d\'impression. Format, papier et contraintes graphiques annoncés.',
            ],
            'Édition' => [
                'h1' => 'Édition et direction de collection',
                'lead' => 'Accompagnement éditorial : ligne, calendrier, fabrication. La plateforme met en relation, elle n\'édite pas les ouvrages.',
            ],
            'Impression' => [
                'h1' => 'Impression de livres offset et numérique',
                'lead' => 'Imprimeurs pour tirage court ou offset : format, papier, façonnage et quantité. Devis comparables, sans abonnement.',
            ],
            'Presse & com' => [
                'h1' => 'Attachés de presse et communication du livre',
                'lead' => 'Porteurs de projet : service de presse, réseaux, communauté. Prestataires spécialisés livre, pas d\'agence généraliste imposée.',
            ],
            'Librairie' => [
                'h1' => 'Libraires et diffusion en librairie',
                'lead' => 'Diffusion, dépôt et événements en librairie. Mettez-vous d\'accord sur la zone, le titre et le calendrier.',
            ],
            'Audio' => [
                'h1' => 'Narration et livre audio',
                'lead' => 'Narrateurs pour livre audio : ton, durée, public. Voix humaine uniquement, pas de synthèse vocale générative.',
            ],
            'Agent littéraire' => [
                'h1' => 'Agents littéraires',
                'lead' => 'Accompagnement pour placer un manuscrit, négocier un contrat ou suivre une carrière. Mandat et périmètre écrits.',
            ],
            'Coach littéraire' => [
                'h1' => 'Coachs littéraires et accompagnement d\'écriture',
                'lead' => 'Trouvez un coach d\'écriture, un mentorat ou un atelier pour un roman, un essai ou un récit. Séances cadrées ou appel d\'offres, sans IA générative.',
            ],
            'Iconographie' => [
                'h1' => 'Iconographie et droits d\'images',
                'lead' => 'Iconographes pour rechercher, légender et négocier les visuels d\'un ouvrage. Sources, droits et usages précisés dès le brief.',
            ],
            'Lecture éditoriale' => [
                'h1' => 'Lecture éditoriale et comité de lecture',
                'lead' => 'Faites évaluer un manuscrit : ligne, public, potentiel. Un avis de lecteur éditorial, distinct de la bêta-lecture.',
            ],
            'Photographie' => [
                'h1' => 'Photographie du livre et portraits d\'auteurs',
                'lead' => 'Photographes pour portraits, photos d\'ouvrages ou reportage de salon. Usage et cession de droits cadrés.',
            ],
            'Reliure' => [
                'h1' => 'Reliure d\'art et façonnage',
                'lead' => 'Relieurs pour reliure artisanale, restauration ou petits tirages soignés. Matériaux et quantité précisés.',
            ],
            'Juridique' => [
                'h1' => 'Juristes du livre et droits d\'auteur',
                'lead' => 'Contrats d\'édition, cessions, image et litiges. Un conseil juridique spécialisé livre, pas un cabinet généraliste imposé.',
            ],
        ];

        $row = $map[$trade] ?? [
            'h1' => $title,
            'lead' => 'Prestataires, prestations à prix affiché et missions ouvertes pour le métier « ' . $trade . ' ».',
        ];
        $row['description'] = $row['lead'];

        return $row;
    }

    /** @return array{h1: string, lead: string, description: string, crumb_trade: string} */
    public static function tradeCityCopy(string $trade, string $cityName): array
    {
        $who = Catalog::tradeGeoLabel($trade);
        $article = Catalog::tradeGeoArticle($trade);
        $title = Catalog::tradeTitle($trade);
        $where = Cities::placeAt($cityName);
        $h1 = $who . ' ' . $where;
        $lead = 'Trouvez ' . $article . ' ' . mb_strtolower($who, 'UTF-8') . ' ' . $where
            . ' : profils publics et prestations à prix affiché. Travail humain, sans IA générative.';
        $national = self::tradeCopy($trade);

        return [
            'h1' => $h1,
            'lead' => $lead,
            'description' => self::clip($h1 . ' — ' . $lead, 160),
            'crumb_trade' => $national['h1'] ?? $title,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function tradeCityPage(string $trade, string $cityName, string $path, int $count): array
    {
        $copy = self::tradeCityCopy($trade, $cityName);
        $url = Share::absolute($path);
        return [
            '@type' => 'CollectionPage',
            '@id' => $url . '#page',
            'name' => $copy['h1'],
            'description' => $copy['description'],
            'url' => $url,
            'isPartOf' => ['@id' => Share::absolute('/') . '#website'],
            'about' => [
                '@type' => 'City',
                'name' => $cityName,
                'containedInPlace' => [
                    '@type' => 'Country',
                    'name' => 'France',
                ],
            ],
            'mainEntity' => [
                '@type' => 'ItemList',
                'name' => $copy['h1'],
                'numberOfItems' => max(0, $count),
                'itemListOrder' => 'https://schema.org/ItemListUnordered',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $profile
     */
    public static function profileTitle(array $profile): string
    {
        $name = trim((string) ($profile['name'] ?? ''));
        $role = self::profileRole($profile);
        if ($name !== '' && $role !== '') {
            return $name . ' — ' . $role;
        }
        $fallback = trim((string) ($profile['title'] ?? ''));
        if ($fallback === '' || mb_strtolower($fallback) === 'prestataire') {
            $fallback = 'Prestataire du livre';
        }
        $city = trim((string) ($profile['city'] ?? ''));
        if ($city !== '' && !str_contains(mb_strtolower($fallback), mb_strtolower($city))) {
            $fallback .= ' ' . Cities::placeAt($city);
        }
        if ($name !== '') {
            return $name . ' — ' . $fallback;
        }
        return $fallback;
    }

    /**
     * @param array<string, mixed> $profile
     */
    public static function profileDescription(array $profile): string
    {
        $pres = trim((string) ($profile['presentation'] ?? ''));
        $custom = trim((string) ($profile['title'] ?? ''));
        if ($custom !== '' && mb_strtolower($custom) === 'prestataire') {
            $custom = '';
        }
        $city = trim((string) ($profile['city'] ?? ''));
        if ($pres !== '') {
            if ($custom !== '' && !str_contains(mb_strtolower($pres), mb_strtolower($custom))) {
                return $custom . '. ' . $pres;
            }
            return $pres;
        }
        $role = self::profileRole($profile);
        return trim(implode(' · ', array_filter([
            $custom !== '' ? $custom : $role,
            $city !== '' ? $city : '',
            'prestataire sur acteursdulivre.fr',
        ], static fn (string $bit): bool => $bit !== '')));
    }

    /**
     * @param array<string, mixed> $profile
     */
    public static function profileRole(array $profile): string
    {
        $labels = [];
        foreach ($profile['trades'] ?? [] as $trade) {
            $resolved = Catalog::resolveTrade((string) $trade) ?? trim((string) $trade);
            if ($resolved === '') {
                continue;
            }
            $label = Catalog::tradeGeoLabel($resolved);
            if ($label !== '' && !in_array($label, $labels, true)) {
                $labels[] = $label;
            }
        }
        $who = '';
        if ($labels !== []) {
            $last = array_pop($labels);
            $who = $labels === [] ? $last : implode(', ', $labels) . ' et ' . $last;
        }
        $city = trim((string) ($profile['city'] ?? ''));
        if ($who !== '' && $city !== '') {
            return $who . ' ' . Cities::placeAt($city);
        }
        return $who;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function forScreen(string $screen, array $data = []): array
    {
        $page = self::catalog()[$screen] ?? null;
        $title = (string) ($data['title'] ?? ($page['title'] ?? self::BRAND));
        $description = (string) ($data['description'] ?? ($page['description'] ?? self::DEFAULT_DESC));
        $path = (string) ($page['path'] ?? Share::current());
        $private = in_array($screen, self::PRIVATE_SCREENS, true) || !empty($data['inEspace']);

        $meta = self::build($title, $description, $path, 'website', null, [
            'robots' => $private ? self::ROBOTS_NONE : self::ROBOTS_INDEX,
            'json_ld' => $private ? [] : self::webPageGraph($title, $description, $path, $screen, $data),
        ]);

        if ($private) {
            $meta['robots'] = self::ROBOTS_NONE;
        }

        return $meta;
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    public static function build(
        string $title,
        string $description,
        ?string $url = null,
        string $type = 'website',
        ?string $image = null,
        array $extra = []
    ): array {
        $type = $type === 'profile' ? 'website' : $type;
        $usingDefaultImage = $image === null || $image === '';
        $image = $image ?: asset('img/og-default.jpg') . '?v=2';
        $url = $url ?? Share::current();
        $fullTitle = self::documentTitle($title);

        return array_merge([
            'title' => $fullTitle,
            'description' => self::clip($description, 160),
            'url' => Share::absolute($url),
            'type' => $type,
            'image' => Share::absolute($image),
            'image_type' => (string) ($extra['image_type'] ?? self::imageMime($image)),
            'image_width' => (int) ($extra['image_width'] ?? self::OG_W),
            'image_height' => (int) ($extra['image_height'] ?? self::OG_H),
            'image_alt' => (string) ($extra['image_alt'] ?? ($usingDefaultImage ? self::OG_ALT : $fullTitle)),
            'robots' => (string) ($extra['robots'] ?? self::ROBOTS_INDEX),
            'json_ld' => $extra['json_ld'] ?? [],
            'published_time' => $extra['published_time'] ?? null,
            'modified_time' => $extra['modified_time'] ?? null,
        ], $extra);
    }

    public static function imageMime(string $url): string
    {
        $path = strtolower((string) (parse_url($url, PHP_URL_PATH) ?: $url));
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        return match ($ext) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/jpeg',
        };
    }

    public static function documentTitle(string $title): string
    {
        $title = trim($title);
        if ($title === '') {
            return self::BRAND . ' — ' . self::BRAND_HOST;
        }
        $lower = mb_strtolower($title);
        if (str_contains($lower, 'acteursdulivre') || str_contains($lower, 'acteurs du livre')) {
            return $title;
        }
        return $title . ' — ' . self::BRAND_HOST;
    }

    public static function defaultImage(): string
    {
        return Share::absolute(asset('img/og-default.jpg') . '?v=2');
    }

    /**
     * @param array<string, mixed> $data
     * @return list<array<string, mixed>>
     */
    public static function webPageGraph(string $title, string $description, string $path, string $screen, array $data = []): array
    {
        $graph = [self::organization(), self::website()];
        $crumbs = $data['breadcrumbs'] ?? self::defaultCrumbs($screen, $title, $path);
        if ($crumbs !== []) {
            $graph[] = self::breadcrumb($crumbs);
        }
        $faqs = $data['faqs'] ?? self::faqsFor($screen);
        if ($faqs !== []) {
            $graph[] = self::faqPage($faqs);
        }
        return $graph;
    }

    /** @return list<array{q: string, a: string}> */
    public static function faqsFor(string $screen): array
    {
        return match ($screen) {
            'accueil', 'aide' => self::homeFaqs(),
            'comment' => self::commentFaqs(),
            'tarifs' => self::tarifsFaqs(),
            'questions' => self::questionsFaqs(),
            default => [],
        };
    }

    /** @return list<array{q: string, a: string}> */
    public static function homeFaqs(): array
    {
        return [
            ['q' => 'Quand la commission est-elle facturée ?', 'a' => 'Lorsque le client confirme que la mission est finalisée et note la prestation. La facture est alors émise au prestataire — dernier jalon — payable sous 15 jours.'],
            ['q' => 'La première mission est-elle payante ?', 'a' => 'Non. La première mission réalisée via la plateforme est gratuite. À partir de la deuxième, la commission est de 8 % sur le montant hors taxes (hors TVA), ou 6 % pour les membres fondateurs (100 premiers inscrits) et dès 12 missions réalisées.'],
            ['q' => 'L\'IA générative est-elle autorisée ?', 'a' => 'Non, pour les missions entre acteurs du livre : aucun livrable ne peut être produit par une IA générative. Le moratoire ne s\'applique pas à la fabrication de la plateforme. Le détail figure dans les règles IA.'],
            ['q' => 'Qui facture le client final ?', 'a' => 'Le prestataire facture directement son client, hors plateforme. La plateforme n\'encaisse pas le prix de la mission ; elle facture uniquement sa commission au prestataire, à la validation.'],
            ['q' => 'Faut-il un abonnement ?', 'a' => 'Non. Créer un compte, une vitrine, des fiches ou un appel d\'offres est gratuit. Aucun abonnement.'],
        ];
    }

    /** @return list<array{q: string, a: string}> */
    public static function commentFaqs(): array
    {
        return [
            ['q' => 'Comment trouver un prestataire du livre ?', 'a' => 'Vous pouvez parcourir l\'annuaire, commander une prestation à prix affiché, ou publier une recherche pour recevoir des devis.'],
            ['q' => 'Combien coûte la mise en relation ?', 'a' => 'Gratuit pour le porteur de projet. Côté prestataire, la première mission est offerte, puis 8 % de commission hors taxes (6 % pour les 100 premiers inscrits, et dès 12 missions réalisées).'],
            ['q' => 'Comment se passe une mission ?', 'a' => 'Brief, devis accepté dans le suivi (cela vaut accord), jalons de facture et de règlement hors plateforme, livraison, puis validation et notation. Pas de contrat type : la commission prestataire est le dernier jalon.'],
        ];
    }

    /** @return list<array{q: string, a: string}> */
    public static function tarifsFaqs(): array
    {
        return [
            ['q' => 'Quel est le tarif d\'acteursdulivre.fr ?', 'a' => 'Aucun abonnement. La première mission réalisée est gratuite pour le prestataire. Ensuite, 8 % HT de commission, ou 6 % HT pour les 100 premiers inscrits et dès 12 missions réalisées. La plateforme facture ce montant HT plus la TVA à 20 %. Le taux personnel s\'affiche dans l\'espace, à Facturation.'],
            ['q' => 'La commission est-elle calculée TTC ou hors taxes ?', 'a' => 'Hors taxes. Le taux (8 % ou 6 %) s\'applique au montant HT de la mission. La plateforme facture ensuite cette commission HT, plus la TVA à 20 %. Si le prestataire est en franchise en base, le prix facturé au client vaut montant HT.'],
            ['q' => 'Qui paie la commission ?', 'a' => 'Le prestataire. Le client paie le prestataire hors plateforme ; la plateforme facture sa commission au prestataire lorsque le client confirme et note.'],
            ['q' => 'Que reste-t-il gratuit ?', 'a' => 'Le compte, la vitrine, les fiches, la publication d\'une recherche, les candidatures et la messagerie. La première mission aussi.'],
        ];
    }

    /** @return list<array{q: string, a: string}> */
    public static function questionsFaqs(): array
    {
        return [
            [
                'q' => 'Quand la commission est-elle prise ?',
                'a' => "Jamais à l'inscription, jamais à la publication d'une fiche, jamais à l'envoi d'un devis. La commission n'existe que lorsqu'une mission a été réalisée et validée.\n\nLe client confirme que le travail est terminé et note la prestation : c'est à ce moment que la plateforme facture sa commission au prestataire — dernier jalon, payable sous 15 jours.\n\nLa première mission réalisée via la plateforme est offerte. À partir de la deuxième, le taux est de 8 % du montant hors taxes (hors TVA), ou 6 % pour les 100 premiers inscrits et dès 12 missions réalisées.",
            ],
            [
                'q' => 'Faut-il un abonnement ?',
                'a' => 'Non. Créer un compte, une vitrine, des fiches ou un appel d\'offres est gratuit. Publier, candidater, échanger et suivre les jalons aussi. Seule la commission sur les missions réalisées — à partir de la deuxième — est facturée au prestataire.',
            ],
            [
                'q' => 'Qui paie la commission ?',
                'a' => 'Le prestataire. Le client paie le prestataire hors plateforme, par le moyen qu\'ils conviennent. La plateforme n\'encaisse pas le prix de la mission : elle facture uniquement sa commission au prestataire, à la validation.',
            ],
            [
                'q' => 'Comment la plateforme va-t-elle trouver des clients ?',
                'a' => "Nous ne vendons pas un forfait de clients et nous ne promettons pas un volume. Une place de marché tient si les deux côtés sont là : des prestataires visibles, et des porteurs de projet qui ont un besoin concret.\n\nAujourd'hui la plateforme est en pré-ouverture : les auteurs et les professionnels du livre s'inscrivent déjà ; l'ouverture aux clients est prévue en octobre 2026. Les auteurs déjà présents sont souvent les premiers à commander — une correction, une couverture, une impression.\n\nPour que ces besoins vous trouvent, la plateforme s'appuie sur ce qui est public et indexable : un annuaire par métier, des prestations à prix affiché, des appels d'offres, des pages métier, un journal sur la fabrication du livre, une newsletter qui relaie les recherches ouvertes et les nouveaux profils, et les réseaux sociaux.\n\nNous ne poussons aucun profil contre rémunération publicitaire : le classement dépend des avis et des délais tenus. Une vitrine précise reçoit plus de demandes qu'une fiche vide ; vous pouvez aussi candidater aux recherches publiées. La plateforme met en relation. Elle ne garantit pas un carnet de commandes.",
            ],
            [
                'q' => 'Comment ça marche, concrètement ?',
                'a' => 'Trois chemins : parcourir l\'annuaire, commander une prestation à prix affiché, ou publier une recherche pour recevoir des devis. Le devis accepté dans le suivi vaut accord. Les jalons cadrent le travail (devis, factures, déclarations de règlement, livraison). Le règlement se fait entre vous, hors plateforme. À la validation et à la notation, la commission prestataire est facturée.',
            ],
            [
                'q' => 'La plateforme encaisse-t-elle l\'argent des missions ?',
                'a' => 'Non. Aucune carte bancaire n\'est collectée. Client et prestataire se règlent hors site — virement SEPA, chèque, facture entreprise, notamment. Les déclarations de règlement dans le suivi valent engagement sur l\'honneur.',
            ],
            [
                'q' => 'Qui peut s\'inscrire aujourd\'hui ?',
                'a' => 'Les auteurs et les professionnels du livre. Un même compte peut chercher des prestataires et proposer des services. L\'ouverture aux clients est prévue en octobre 2026. Il n\'y a pas de liste d\'attente séparée : les comptes inscrits sont les vrais comptes.',
            ],
            [
                'q' => 'Comment apparaître et recevoir des demandes ?',
                'a' => 'Complétez votre vitrine — métier, titre, présentation, tarif — et publiez au moins une fiche. Les vitrines précises reçoivent plus de demandes. Dans l\'espace, Statistiques indique le nombre de vues de votre fiche et de chaque prestation, sans identifier les visiteurs. Vous pouvez aussi candidater aux recherches ouvertes. Le classement dépend des avis et des délais tenus, pas d\'un budget publicitaire. Un justificatif d\'activité et une référence professionnelle permettent d\'obtenir le badge vérifié.',
            ],
            [
                'q' => 'L\'IA générative est-elle autorisée ?',
                'a' => 'Non, pour les missions entre acteurs du livre : aucun livrable ne peut être produit par une IA générative — texte, illustration, voix, traduction ou maquette. Le moratoire ne s\'applique pas à la fabrication de la plateforme. Le détail figure dans les règles IA.',
            ],
            [
                'q' => 'Y a-t-il un contrat type ?',
                'a' => 'Non. L\'acceptation du devis dans le suivi vaut accord entre les parties. La plateforme ne génère ni contrat type ni NDA. Le devis peut rappeler le périmètre, les délais et, le cas échéant, l\'usage des livrables.',
            ],
            [
                'q' => 'Que se passe-t-il si je ne règle pas la commission ?',
                'a' => 'Tant que la facture n\'est pas réglée à l\'échéance, les fiches disparaissent de l\'annuaire et le prestataire ne peut plus proposer de nouvelles offres. Le délai de règlement est de 15 jours.',
            ],
            [
                'q' => 'La plateforme prend-elle des droits sur les livres ?',
                'a' => 'Non. Nous ne sommes ni éditeur, ni employeur, ni agent. Nous ne prenons aucun droit sur les ouvrages. Les textes, visuels et manuscrits restent la propriété de ceux qui les ont déposés.',
            ],
            [
                'q' => 'Comment vous écrire ?',
                'a' => 'Par le formulaire de contact, ou à guillaume@editions-tesseract.fr. L\'équipe répond en jours ouvrés, du lundi au vendredi, 9 h – 18 h. Pour un litige : mediation@acteursdulivre.fr.',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function organization(): array
    {
        $home = Share::absolute('/');
        return [
            '@type' => 'Organization',
            '@id' => $home . '#organization',
            'name' => self::BRAND,
            'alternateName' => self::BRAND_HOST,
            'url' => $home,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => Share::absolute(asset('img/logo.png')),
                'width' => 1024,
                'height' => 280,
            ],
            'image' => self::defaultImage(),
            'description' => self::DEFAULT_DESC,
            'foundingDate' => '2026',
            'email' => 'guillaume@editions-tesseract.fr',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => '486 rue Sadi Carnot',
                'postalCode' => '59184',
                'addressLocality' => 'Sainghin-en-Weppes',
                'addressCountry' => 'FR',
            ],
            'parentOrganization' => [
                '@type' => 'Organization',
                'name' => 'EDITIONS TESSERACT',
                'legalName' => 'EDITIONS TESSERACT',
                'identifier' => '980005292',
                'url' => 'https://editions-tesseract.fr/',
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => 'guillaume@editions-tesseract.fr',
                'url' => Share::absolute('/contact'),
                'availableLanguage' => 'French',
            ],
            'sameAs' => Socials::sameAs(),
        ];
    }

    /** @return array<string, mixed> */
    public static function website(): array
    {
        $home = Share::absolute('/');
        return [
            '@type' => 'WebSite',
            '@id' => $home . '#website',
            'url' => $home,
            'name' => self::BRAND,
            'alternateName' => self::BRAND_HOST,
            'description' => self::DEFAULT_DESC,
            'inLanguage' => 'fr-FR',
            'publisher' => ['@id' => $home . '#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => Share::absolute('/recherche') . '?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @param list<array{name: string, url: string}> $crumbs
     * @return array<string, mixed>
     */
    public static function breadcrumb(array $crumbs): array
    {
        $items = [];
        foreach ($crumbs as $i => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb['name'],
                'item' => Share::absolute($crumb['url']),
            ];
        }
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * @param list<array{q: string, a: string}> $faqs
     * @return array<string, mixed>
     */
    public static function faqPage(array $faqs): array
    {
        $entities = [];
        foreach ($faqs as $faq) {
            $entities[] = [
                '@type' => 'Question',
                'name' => $faq['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['a'],
                ],
            ];
        }
        return [
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    /**
     * @param array<string, mixed> $article
     * @return array<string, mixed>
     */
    public static function article(array $article): array
    {
        $url = Share::absolute((string) ($article['href'] ?? '/journal'));
        $published = (string) ($article['published_at'] ?? '');
        $imageUrl = trim((string) ($article['img'] ?? ''));
        $imageUrl = $imageUrl !== '' ? Share::absolute($imageUrl) : self::defaultImage();
        $words = (int) ($article['word_count'] ?? str_word_count(strip_tags((string) ($article['body'] ?? ''))));
        $body = [
            '@type' => 'Article',
            '@id' => $url . '#article',
            'headline' => (string) ($article['title'] ?? ''),
            'description' => (string) ($article['excerpt'] ?: ($article['chapo'] ?? '')),
            'url' => $url,
            'inLanguage' => 'fr-FR',
            'isAccessibleForFree' => true,
            'articleSection' => (string) ($article['cat'] ?? $article['category'] ?? 'Journal'),
            'wordCount' => max(1, $words),
            'keywords' => (string) ($article['keywords'] ?? 'autoédition, fabrication livre, coût impression, correction, maquette, couverture'),
            'author' => [
                '@type' => 'Organization',
                'name' => self::BRAND,
                'url' => Share::absolute('/'),
            ],
            'publisher' => ['@id' => Share::absolute('/') . '#organization'],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $url,
            ],
            'image' => [
                '@type' => 'ImageObject',
                'url' => $imageUrl,
                'caption' => (string) ($article['image_alt'] ?? $article['title'] ?? ''),
            ],
            'speakable' => [
                '@type' => 'SpeakableSpecification',
                'cssSelector' => ['.article-chapo', '.article-body h2', '#essentiel + ul'],
            ],
        ];
        if ($published !== '') {
            $iso = date('c', strtotime($published) ?: time());
            $body['datePublished'] = $iso;
            $body['dateModified'] = $iso;
        }
        return $body;
    }

    /**
     * @param array<string, mixed> $profile
     * @return array<string, mixed>
     */
    public static function person(array $profile): array
    {
        $url = Share::absolute((string) ($profile['href'] ?? '/'));
        $job = trim((string) ($profile['title'] ?? ''));
        if ($job === '' || mb_strtolower($job) === 'prestataire') {
            $job = self::profileRole($profile);
        }
        if ($job === '') {
            $job = 'Prestataire des métiers du livre';
        }
        $out = [
            '@type' => 'Person',
            '@id' => $url . '#person',
            'name' => (string) ($profile['name'] ?? ''),
            'url' => $url,
            'jobTitle' => $job,
            'description' => self::clip(self::profileDescription($profile), 200),
        ];
        if (!empty($profile['city'])) {
            $place = [
                '@type' => 'Place',
                'name' => (string) $profile['city'],
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => (string) $profile['city'],
                    'addressCountry' => 'FR',
                ],
            ];
            $out['homeLocation'] = $place;
        }
        $sameAs = [];
        $website = trim((string) ($profile['website'] ?? ''));
        if ($website !== '') {
            $sameAs[] = $website;
        }
        foreach ($profile['socials'] ?? [] as $social) {
            $href = trim((string) ($social['url'] ?? ''));
            if ($href !== '') {
                $sameAs[] = $href;
            }
        }
        if ($sameAs !== []) {
            $out['sameAs'] = array_values(array_unique($sameAs));
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $service
     * @return array<string, mixed>
     */
    public static function offer(array $service): array
    {
        $url = Share::absolute((string) ($service['href'] ?? '/'));
        $price = self::priceAmount((string) ($service['price'] ?? $service['price_from'] ?? ''));
        $out = [
            '@type' => 'Service',
            '@id' => $url . '#service',
            'name' => (string) ($service['title'] ?? ''),
            'description' => self::clip((string) ($service['excerpt'] ?? ''), 200),
            'url' => $url,
            'provider' => [
                '@type' => 'Person',
                'name' => (string) ($service['by'] ?? ''),
            ],
            'areaServed' => 'FR',
            'serviceType' => (string) ($service['cat'] ?? 'Métier du livre'),
        ];
        if (!empty($service['has_image']) && trim((string) ($service['img'] ?? '')) !== '') {
            $out['image'] = Share::absolute((string) $service['img']);
        }
        if ($price !== null) {
            $out['offers'] = [
                '@type' => 'Offer',
                'priceCurrency' => 'EUR',
                'price' => $price,
                'url' => $url,
                'availability' => 'https://schema.org/InStock',
                'priceSpecification' => [
                    '@type' => 'PriceSpecification',
                    'price' => $price,
                    'priceCurrency' => 'EUR',
                    'valueAddedTaxIncluded' => true,
                ],
            ];
        }
        return $out;
    }

    /**
     * @return list<array{name: string, url: string}>
     */
    public static function defaultCrumbs(string $screen, string $title, string $path): array
    {
        if ($screen === 'accueil') {
            return [];
        }
        $name = match ($screen) {
            'comment' => 'Comment ça marche',
            'tarifs' => 'Tarifs',
            'confiance' => 'Confiance',
            'apropos' => 'À propos',
            'journal' => 'Le journal',
            'forum' => 'Forum',
            'aide' => 'Centre d\'aide',
            'questions' => 'Questions fréquentes',
            'contact' => 'Contact',
            'missions' => 'Appels d\'offres',
            'resultats' => 'Annuaire',
            'inscription' => 'Inscription',
            'legal' => $title,
            default => $title,
        };
        return [
            ['name' => self::BRAND, 'url' => '/'],
            ['name' => $name, 'url' => $path],
        ];
    }

    public static function robotsTxt(): string
    {
        $sitemap = Share::absolute('/sitemap.xml');
        return <<<TXT
User-agent: *
Allow: /
Disallow: /espace
Disallow: /espace/
Disallow: /admin
Disallow: /admin/
Disallow: /install
Disallow: /install/
Disallow: /api/
Disallow: /cron
Disallow: /cron/
Disallow: /connexion
Disallow: /inscription/sso

User-agent: Google-Extended
Allow: /

User-agent: GPTBot
Allow: /

User-agent: ChatGPT-User
Allow: /

User-agent: PerplexityBot
Allow: /

User-agent: ClaudeBot
Allow: /

User-agent: anthropic-ai
Allow: /

User-agent: Applebot-Extended
Allow: /

Sitemap: {$sitemap}

TXT;
    }

    public static function llmsTxt(): string
    {
        $home = Share::absolute('/');
        return <<<MD
# Acteurs du Livre

> Place de marché francophone des métiers du livre, éditée par EDITIONS TESSERACT (SAS, Sainghin-en-Weppes, France).

Site : [acteursdulivre.fr]({$home})

## En une phrase
acteursdulivre.fr met en relation des porteurs de projet (auteurs, éditeurs, collectifs) et des prestataires du livre (correcteurs, bêta-lecteurs, lecteurs éditoriaux, illustrateurs, iconographes, photographes, traducteurs, maquettistes, imprimeurs, relieurs, attachés de presse, libraires, narrateurs, agents, juristes, coachs littéraires).

## Faits
- Pas d'éditeur des ouvrages des utilisateurs : contrats conclus entre le porteur de projet et le prestataire.
- Pas d'abonnement. Compte, vitrine, fiches et appels d'offres gratuits.
- Première mission réalisée offerte ; ensuite 8 % de commission hors taxes (hors TVA), ou 6 % pour les 100 premiers inscrits et dès 12 missions réalisées, facturés au prestataire lorsque le client confirme et note.
- Le prix de la mission se règle hors plateforme, entre client et prestataire. La plateforme suit les jalons et n'encaisse rien.
- Moratoire IA générative pour les prestations livrées aux acteurs du livre : ni texte, ni illustration, ni voix. Les manuscrits ne servent pas à entraîner un modèle. La fabrication de la plateforme elle-même n'est pas couverte par ce moratoire ; le détail est public.
- Pré-ouverture : inscriptions ouvertes aux auteurs et professionnels. Ouverture clients annoncée pour octobre 2026.
- Langue : français. Devise : EUR.

## Pages utiles
- [Accueil]({$home}) : page d'entrée de la place de marché
- [Comment ça marche]({$home}comment-ca-marche) : fonctionnement de la mise en relation
- [Questions fréquentes]({$home}questions) : commission, clients, règlement
- [Tarifs]({$home}tarifs) : commission et conditions tarifaires
- [Confiance]({$home}confiance) : garanties et cadre de la plateforme
- [Règles IA]({$home}regles-ia) : moratoire sur l'IA générative
- [À propos]({$home}a-propos) : l'éditeur et le projet
- [Annuaire]({$home}recherche) : rechercher un prestataire ou une prestation
- [Prestataires]({$home}prestataires) : vitrines des professionnels
- [Prestations]({$home}prestations) : offres à prix affiché
- [Appels d'offres]({$home}missions) : recherches publiées par les porteurs de projet
- [Métiers]({$home}metiers/correction) : pages métiers, exemple correction
- [Journal]({$home}journal) : articles sur les métiers, tarifs, contrats et diffusion
- [Contact]({$home}contact) : écrire à l'équipe
- [Mentions légales]({$home}mentions-legales) : informations légales

Les pages locales (exemple : [correctrice à Paris]({$home}correctrice/paris)) redirigent vers l'annuaire filtré.

## Contact
- [guillaume@editions-tesseract.fr](mailto:guillaume@editions-tesseract.fr)
- [mediation@acteursdulivre.fr](mailto:mediation@acteursdulivre.fr)
- [presse@acteursdulivre.fr](mailto:presse@acteursdulivre.fr)
- [Facebook](https://www.facebook.com/acteursdulivre/)
- [Instagram](https://www.instagram.com/acteursdulivre.fr/)
MD;
    }

    public static function clip(string $text, int $max = 160): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? $text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $cut = mb_substr($text, 0, $max - 1);
        $space = mb_strrpos($cut, ' ');
        if ($space !== false && $space > (int) ($max * 0.6)) {
            $cut = mb_substr($cut, 0, $space);
        }
        return rtrim($cut, " \t\n\r\0\x0B.,;:") . '…';
    }

    private static function priceAmount(string $raw): ?string
    {
        if (preg_match('/(\d+(?:[.,]\d+)?)/', str_replace("\u{00A0}", ' ', $raw), $m) !== 1) {
            return null;
        }
        return str_replace(',', '.', $m[1]);
    }
}
