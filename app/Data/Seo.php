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
        'favoris', 'avis', 'vitrine', 'parametres', 'facturation', 'bienvenue',
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
                'description' => 'Cherchez un profil, commandez une prestation ou publiez un appel d\'offres. Suivi à jalons, règlement hors plateforme. Première mission offerte, puis 8 % au prestataire.',
                'path' => '/comment-ca-marche',
            ],
            'tarifs' => [
                'title' => 'Tarifs et commission 8 %',
                'description' => 'Aucun abonnement. Vitrine, fiches et appels d\'offres gratuits. Première mission offerte, puis 8 % de commission sur les missions réalisées.',
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
            'aide' => [
                'title' => 'Centre d\'aide',
                'description' => 'Jalons, règlement hors plateforme, commission, annulation, facturation, avis, litiges : les réponses utiles pour utiliser acteursdulivre.fr.',
                'path' => '/aide',
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
                'h1' => 'Auteurs, prête-plume et accompagnement d\'écriture',
                'lead' => 'Trouvez un auteur, un prête-plume ou un coach d\'écriture pour un roman, un essai ou un récit. Prestations cadrées ou appel d\'offres, sans IA générative.',
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
            'Salons' => [
                'h1' => 'Salons du livre et événements',
                'lead' => 'Organisation de rencontres, dédicaces et salons. Dates, lieu et public précisés dans l\'appel d\'offres ou la fiche.',
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
            'image_width' => (int) ($extra['image_width'] ?? self::OG_W),
            'image_height' => (int) ($extra['image_height'] ?? self::OG_H),
            'image_alt' => (string) ($extra['image_alt'] ?? ($usingDefaultImage ? self::OG_ALT : $fullTitle)),
            'robots' => (string) ($extra['robots'] ?? self::ROBOTS_INDEX),
            'json_ld' => $extra['json_ld'] ?? [],
            'published_time' => $extra['published_time'] ?? null,
            'modified_time' => $extra['modified_time'] ?? null,
        ], $extra);
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
            default => [],
        };
    }

    /** @return list<array{q: string, a: string}> */
    public static function homeFaqs(): array
    {
        return [
            ['q' => 'Quand la commission est-elle facturée ?', 'a' => 'Lorsque le client confirme que la mission est finalisée et note la prestation. La facture est alors émise au prestataire — dernier jalon — payable sous 15 jours.'],
            ['q' => 'La première mission est-elle payante ?', 'a' => 'Non. La première mission réalisée via la plateforme est gratuite. À partir de la deuxième, la commission est de 8 %.'],
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
            ['q' => 'Combien coûte la mise en relation ?', 'a' => 'Gratuit pour le porteur de projet. Côté prestataire, la première mission est offerte, puis 8 % de commission sur les missions suivantes.'],
            ['q' => 'Comment se passe une mission ?', 'a' => 'Brief, devis accepté dans le suivi (cela vaut accord), jalons de facture et de règlement hors plateforme, livraison, puis validation et notation. Pas de contrat type : la commission prestataire est le dernier jalon.'],
        ];
    }

    /** @return list<array{q: string, a: string}> */
    public static function tarifsFaqs(): array
    {
        return [
            ['q' => 'Quel est le tarif d\'acteursdulivre.fr ?', 'a' => 'Aucun abonnement. La première mission réalisée est gratuite pour le prestataire. Ensuite, 8 % de commission sur le montant de la mission.'],
            ['q' => 'Qui paie la commission ?', 'a' => 'Le prestataire. Le client paie le prestataire hors plateforme ; la plateforme facture sa commission au prestataire lorsque le client confirme et note.'],
            ['q' => 'Que reste-t-il gratuit ?', 'a' => 'Le compte, la vitrine, les fiches, la publication d\'une recherche, les candidatures et la messagerie. La première mission aussi.'],
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
            'email' => 'bonjour@acteursdulivre.fr',
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
                'email' => 'bonjour@acteursdulivre.fr',
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
        $job = trim((string) ($profile['title'] ?? 'Prestataire des métiers du livre'));
        $out = [
            '@type' => 'Person',
            '@id' => $url . '#person',
            'name' => (string) ($profile['name'] ?? ''),
            'url' => $url,
            'jobTitle' => $job !== '' ? $job : 'Prestataire des métiers du livre',
            'description' => self::clip((string) ($profile['presentation'] ?? $job), 200),
        ];
        if (!empty($profile['city'])) {
            $out['homeLocation'] = [
                '@type' => 'Place',
                'name' => (string) $profile['city'],
            ];
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
        if ($price !== null) {
            $out['offers'] = [
                '@type' => 'Offer',
                'priceCurrency' => 'EUR',
                'price' => $price,
                'url' => $url,
                'availability' => 'https://schema.org/InStock',
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
            'aide' => 'Centre d\'aide',
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

> Place de marché francophone des métiers du livre, éditée par EDITIONS TESSERACT (SAS, Sainghin-en-Weppes, France). Site : {$home}

## En une phrase
acteursdulivre.fr met en relation des porteurs de projet (auteurs, éditeurs, collectifs) et des prestataires du livre (correcteurs, bêta-lecteurs, lecteurs éditoriaux, illustrateurs, iconographes, photographes, traducteurs, maquettistes, imprimeurs, relieurs, attachés de presse, libraires, narrateurs, agents, juristes, salons).

## Faits
- Pas d'éditeur des ouvrages des utilisateurs : contrats conclus entre le porteur de projet et le prestataire.
- Pas d'abonnement. Compte, vitrine, fiches et appels d'offres gratuits.
- Première mission réalisée offerte ; ensuite 8 % de commission facturés au prestataire lorsque le client confirme et note.
- Le prix de la mission se règle hors plateforme, entre client et prestataire. La plateforme suit les jalons et n'encaisse rien.
- Moratoire IA générative pour les prestations livrées aux acteurs du livre : ni texte, ni illustration, ni voix. Les manuscrits ne servent pas à entraîner un modèle. La fabrication de la plateforme elle-même n'est pas couverte par ce moratoire ; le détail est public.
- Pré-ouverture : inscriptions ouvertes aux auteurs et professionnels. Ouverture clients annoncée pour octobre 2026.
- Langue : français. Devise : EUR.

## Pages utiles
- Accueil : {$home}
- Comment ça marche : {$home}comment-ca-marche
- Tarifs : {$home}tarifs
- Confiance : {$home}confiance
- Règles IA : {$home}regles-ia
- À propos : {$home}a-propos
- Annuaire : {$home}recherche
- Prestataires : {$home}prestataires
- Prestations : {$home}prestations
- Appels d'offres : {$home}missions
- Métiers : {$home}metiers/correction
- Journal (30 articles — métiers, tarifs, contrats, diffusion) : {$home}journal
- Contact : {$home}contact
- Mentions légales : {$home}mentions-legales

## Contact
bonjour@acteursdulivre.fr — médiation@acteursdulivre.fr — presse@acteursdulivre.fr
Facebook : https://www.facebook.com/acteursdulivre/
Instagram : https://www.instagram.com/acteursdulivre.fr/
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
