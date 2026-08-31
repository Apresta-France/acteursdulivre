<?php

declare(strict_types=1);

namespace Adl\Data;

use Adl\Core\Auth;
use Adl\Core\DcEngine;
use Adl\Models\Application;
use Adl\Models\Invoice;
use Adl\Models\Mission;
use Adl\Models\Notification;
use Adl\Models\OrderMilestone;
use Adl\Models\Service;
use Adl\Models\User;

final class Prototype
{
    public const NAVY = '#15212f';
    public const ORANGE = '#D85D3F';

    public static function forScreen(string $screen, array $extra = []): array
    {
        try {
            $user = Auth::user();
        } catch (\Throwable) {
            $user = null;
        }
        $logged = $user !== null;
        $data = array_merge(self::shared($user, $logged, $screen), self::content($screen), $extra);
        $data['seeksServices'] = User::seeksServices($user);
        $data['offersServices'] = User::offersServices($user);
        $data['screen'] = $screen;
        $data['logged'] = $logged;
        $data['visitor'] = !$logged;
        $data['is' . ucfirst($screen)] = true;

        foreach ([
            'Accueil', 'Resultats', 'Fiche', 'Commande', 'Profil', 'Publier', 'Messagerie', 'Dashboard',
            'Missions', 'Mission', 'Suivi', 'Commandes', 'Creer', 'Inscription', 'Comment', 'Tarifs',
            'Confiance', 'Aide', 'Metier', 'Apropos', 'Journal', 'Article', 'Contact', 'Legal',
            'Connexion', 'Notifications', 'MesPrestations', 'MesMissions', 'Candidatures', 'Favoris',
            'Avis', 'Vitrine', 'Parametres', 'Facturation', 'Bienvenue',
        ] as $name) {
            $key = 'is' . $name;
            if (!isset($data[$key])) {
                $data[$key] = false;
            }
        }

        $map = [
            'accueil' => 'isAccueil', 'resultats' => 'isResultats', 'fiche' => 'isFiche',
            'commande' => 'isCommande', 'profil' => 'isProfil', 'publier' => 'isPublier',
            'messagerie' => 'isMessagerie', 'dashboard' => 'isDashboard', 'missions' => 'isMissions',
            'mission' => 'isMission', 'suivi' => 'isSuivi', 'commandes' => 'isCommandes',
            'creer' => 'isCreer', 'inscription' => 'isInscription', 'inscription-sso' => 'isInscription', 'comment' => 'isComment',
            'tarifs' => 'isTarifs', 'confiance' => 'isConfiance', 'aide' => 'isAide',
            'metier' => 'isMetier', 'apropos' => 'isApropos', 'journal' => 'isJournal',
            'article' => 'isArticle', 'contact' => 'isContact', 'legal' => 'isLegal',
            'connexion' => 'isConnexion', 'notifications' => 'isNotifications',
            'mesprestations' => 'isMesPrestations', 'mesmissions' => 'isMesMissions',
            'candidatures' => 'isCandidatures', 'favoris' => 'isFavoris', 'avis' => 'isAvis',
            'vitrine' => 'isVitrine', 'parametres' => 'isParametres', 'facturation' => 'isFacturation',
            'bienvenue' => 'isBienvenue',
        ];
        if (isset($map[$screen])) {
            $data[$map[$screen]] = true;
        }

        $data['vitCompetences'] = ($extra['vitTab'] ?? 0) === 0;
        $data['vitCV'] = ($extra['vitTab'] ?? 0) === 1;
        $data['vitPortfolio'] = ($extra['vitTab'] ?? 0) === 2;
        $data['vitHistorique'] = ($extra['vitTab'] ?? 0) === 3;
        $data['vitAvis'] = ($extra['vitTab'] ?? 0) === 4;

        if (empty($data['meta']) || !is_array($data['meta'])) {
            $data['meta'] = Seo::forScreen($screen, $data);
        } elseif (empty($data['meta']['robots']) && (!empty($data['inEspace']) || in_array($screen, ['connexion', 'bienvenue', 'inscription-sso'], true))) {
            $data['meta']['robots'] = Seo::ROBOTS_NONE;
        }

        return $data;
    }

    private static function shared(?array $user, bool $logged, string $screen): array
    {
        $navy = self::NAVY;
        $initials = $user ? User::initials($user) : 'AD';
        $first = is_array($user) ? ($user['first_name'] ?? '') : '';

        $railNames = Catalog::trades();
        $rail = [];
        foreach ($railNames as $name) {
            $rail[] = [
                'name' => $name,
                'href' => Catalog::tradePath($name),
                'style' => 'padding: 16px 0; font-size: 14px; cursor: pointer; white-space: nowrap; color: #56677A; font-weight: 400;',
            ];
        }

        $mega = Catalog::megaGroups();
        $seeks = User::seeksServices($user);
        $offers = User::offersServices($user);
        $unreadMessages = self::liveUnreadMessages($user);
        $unreadAlerts = self::liveUnreadAlerts($user);

        $footerCols = [
            ['title' => 'Porteurs de projet', 'links' => [
                ['label' => 'Chercher un prestataire', 'href' => '/prestataires'],
                ['label' => 'Parcourir les prestations', 'href' => '/prestations'],
                ['label' => 'Publier une recherche', 'href' => '/espace/publier'],
                ['label' => 'Mes commandes', 'href' => '/espace/commandes'],
                ['label' => 'Devis & jalons', 'href' => '/comment-ca-marche'],
                ['label' => 'Suivi de commande', 'href' => '/espace/suivi'],
            ]],
            ['title' => 'Prestataires', 'links' => [
                ['label' => 'Créer ma vitrine', 'href' => '/inscription'],
                ['label' => 'Proposer une prestation', 'href' => '/espace/prestations/creer'],
                ['label' => 'Appels d\'offres', 'href' => '/missions'],
                ['label' => 'Commission', 'href' => '/tarifs'],
                ['label' => 'Ma vitrine & mon CV', 'href' => '/espace/vitrine'],
            ]],
            ['title' => 'La plateforme', 'links' => [
                ['label' => 'Comment ça marche', 'href' => '/comment-ca-marche'],
                ['label' => 'À propos', 'href' => '/a-propos'],
                ['label' => 'Le journal', 'href' => '/journal'],
                ['label' => 'Charte qualité', 'href' => '/confiance'],
                ['label' => 'Règles IA', 'href' => '/regles-ia'],
                ['label' => 'Tarifs', 'href' => '/tarifs'],
            ]],
            ['title' => 'Aide', 'links' => [
                ['label' => 'Centre d\'aide', 'href' => '/aide'],
                ['label' => 'Nous écrire', 'href' => '/contact'],
                ['label' => 'Litiges & médiation', 'href' => '/confiance'],
                ['label' => 'Signaler un abus', 'href' => '/contact'],
                ['label' => 'Mentions & CGU', 'href' => '/mentions-legales'],
                ['label' => 'CGV', 'href' => '/cgv'],
            ]],
        ];

        return [
            'title' => 'Acteurs du Livre',
            'query' => '',
            'megaOpen' => false,
            'mega' => $mega,
            'megaBtnStyle' => 'padding: 16px 0; font-size: 14px; cursor: pointer; white-space: nowrap; color: ' . $navy . '; font-weight: 500;',
            'rail' => $rail,
            'socials' => Socials::profiles(),
            'footerCols' => $footerCols,
            'topbarStats' => self::topbarStats(),
            'footerMetiers' => Catalog::footerMetiers(),
            'userInitials' => $initials,
            'userAvatarUrl' => user_avatar_src($user),
            'userFirst' => $first,
            'userName' => $user ? User::displayName($user) : '',
            'isAdmin' => is_array($user) && ($user['role'] ?? '') === 'admin',
            'seeksServices' => $seeks,
            'offersServices' => $offers,
            'inEspace' => $logged && self::isEspaceScreen($screen),
            'espaceNav' => $logged ? self::espaceNav(
                $screen,
                $seeks,
                $offers,
                self::espaceNavBadges($user, $unreadMessages, $unreadAlerts, $seeks, $offers)
            ) : [],
            'headerCta' => self::headerCta($seeks, $offers),
            'routes' => DcEngine::routes(),
            'unreadMessages' => $unreadMessages,
            'unreadAlerts' => $unreadAlerts,
        ];
    }

    private static function content(string $screen): array
    {
        $navy = self::NAVY;
        $orange = self::ORANGE;
        $services = self::services();
        $heroImgs = home_hero_photos();

        $base = [
            'homeQuick' => Catalog::HOME_QUICK_PREFERRED,
            'homeHeroImgs' => $heroImgs,
            'homeImg1' => $heroImgs[0],
            'homeImg2' => $heroImgs[1],
            'homeImg3' => $heroImgs[2],
            'homeStats' => Catalog::homeStats(),
            'homeMetiers' => self::homeMetiers(),
            'homeFeatured' => array_map(static function (array $x, int $i): array {
                $x['img'] = photo($i);
                $x['homeSlotId'] = 'home-feat-' . $i;
                $x['avatar'] = avatar_style($x['initials'], 26);
                $x['href'] = '/prestations/' . slugify($x['title']);
                return $x;
            }, array_slice($services, 0, 3), array_keys(array_slice($services, 0, 3))),
            'homeEntry' => self::homeEntry(),
            'missionsBandStats' => Catalog::missionsBandStats(),
            'homeMissionFilters' => self::chips(['Toutes', 'Correction', 'Illustration', 'Traduction', 'Impression', 'Presse & com'], 0, true),
            'homeMissions' => self::homeMissions(),
            'homeTemoins' => [],
            'journal' => self::journalPreview(),
            'journalCats' => self::chips(['Tout', 'Tarifs', 'Contrats', 'Métier', 'Fabrication', 'Diffusion'], 0),
            'journalAll' => self::journalAll(),
            'services' => array_map(static function (array $x, int $i) use ($navy, $orange): array {
                $x['img'] = photo($i);
                $x['slotId'] = 'res-' . $i;
                $x['avatar'] = avatar_style($x['initials'], 24);
                $x['levelStyle'] = 'background: #F4F6F9; color: #4A5A6B; font-size: 11px; padding: 3px 7px; border-radius: 4px; font-family: \'Space Grotesk\', monospace;';
                $x['badges'] = $x['tag'] !== '' ? [['label' => $x['tag'], 'style' => 'background: ' . ($x['tag'] === 'Nouveau' ? $navy : $orange) . '; color: #FFF; font-family: \'Space Grotesk\', monospace; font-size: 10px; letter-spacing: .06em; text-transform: uppercase; padding: 5px 8px; border-radius: 4px;']] : [];
                $x['href'] = '/prestations/' . slugify($x['title']);
                return $x;
            }, $services, array_keys($services)),
            'activeFilters' => ['Roman', 'Profil vérifié'],
            'filters' => self::filters(),
            'pages' => self::pagination(),
            'gallery' => self::gallery(),
            'galleryImg' => photo(0),
            'gallerySlotId' => 'gal-main-0',
            'formules' => self::formules(1),
            'formuleTabs' => self::formuleTabs(1),
            'compare' => self::compare(1),
            'formuleName' => 'Standard',
            'formulePrice' => '780 €',
            'formuleDesc' => 'Formule essentielle, plus un rapport de lecture de 3 à 5 pages et un aller-retour.',
            'formuleDelay' => '3 semaines',
            'formuleAR' => '1 aller-retour inclus',
            'options' => self::options(),
            'selectedOptions' => [],
            'total' => '780',
            'ficheInclus' => [
                'Fichier Word ou InDesign corrigé, en mode révision',
                'Rapport de lecture (cohérence, rythme, répétitions)',
                'Une passe de vérification après vos arbitrages',
                'Feuille de style typographique du texte',
                'Confidentialité : aucun extrait sans autorisation écrite',
            ],
            'faq' => self::faq(),
            'distribution' => self::distribution(),
            'avis' => self::avisList(),
            'similaires' => [
                ['title' => 'Relecture typographique sur épreuves PDF', 'price' => '260 €', 'rating' => '4,8', 'img' => photo(5), 'slotId' => 'sim-0'],
                ['title' => 'Préparation de copie avant maquette', 'price' => '340 €', 'rating' => '4,7', 'img' => photo(4), 'slotId' => 'sim-1'],
                ['title' => 'Relecture jeunesse, album et premières lectures', 'price' => '190 €', 'rating' => '4,9', 'img' => photo(1), 'slotId' => 'sim-2'],
            ],
            'sellerMeta' => [
                ['k' => 'Délai de réponse', 'v' => 'moins de 24 h'],
                ['k' => 'Commandes en cours', 'v' => '12'],
                ['k' => 'Livrées dans les délais', 'v' => '98 %'],
                ['k' => 'Membre depuis', 'v' => '2022'],
            ],
        ];

        return array_merge($base, self::extra(), self::liveOverlay());
    }

    /** @return array<string, mixed> */
    private static function liveOverlay(): array
    {
        try {
            $stats = Catalog::homeStats();
            $featured = Catalog::featuredServices(3);
            $entry = Catalog::featuredServices(3, 3);
            $pros = User::countOfferers();
            $services = Service::countPublished();
            $openMissions = Mission::countOpen();
            $commission = \Adl\Models\Setting::get('commission_percent', '8') ?: '8';
            $journal = Catalog::journalPreview(3);
            $journalAll = \Adl\Models\Article::published();
            foreach ($journalAll as &$article) {
                $article['go'] = true;
            }
            unset($article);

            return [
                'homeQuick' => Catalog::homeQuick(),
                'homeStats' => $stats,
                'homeMetiers' => Catalog::tradeCards(),
                'homeFeatured' => $featured,
                'homeEntry' => $entry,
                'homeMissions' => Catalog::homeMissions(5),
                'homeTemoins' => Catalog::homeReviews(3),
                'equipe' => Catalog::equipe(),
                'missionsBandStats' => Catalog::missionsBandStats(),
                'journal' => $journal,
                'journalAll' => $journalAll,
                'services' => Catalog::services(),
                'missionsList' => Catalog::missions(),
                'notifications' => [],
                'mesPrestations' => [],
                'mesMissions' => [],
                'mesCandidatures' => [],
                'favoris' => [],
                'commandes' => [],
                'threads' => [],
                'messages' => [],
                'demandes' => [],
                'enCours' => [],
                'kpis' => [],
                'operations' => [],
                'suggestions' => Catalog::suggestionsForTrade('Correction'),
                'openMissionsLabel' => format_int($openMissions) . ' recherche' . ($openMissions > 1 ? 's' : '') . ' ouverte' . ($openMissions > 1 ? 's' : ''),
                'openMissionsCta' => $openMissions > 0 ? 'Voir les recherches' : 'Voir les appels d\'offres',
                'inscriptionProof' => [
                    ['v' => format_int($pros), 'k' => 'professionnels du livre inscrits'],
                    ['v' => format_int($openMissions), 'k' => 'recherches ouvertes'],
                    ['v' => $commission . ' %', 'k' => 'dès la 2ᵉ mission, sans abonnement'],
                ],
                'ways' => [
                    ['kicker' => 'Annuaire', 'title' => 'Chercher un profil', 'body' => 'Filtrez par métier, spécialité, ville et tarif, puis engagez la discussion.', 'points' => [format_int($pros) . ' profil' . ($pros > 1 ? 's' : ''), 'Avis après mission réelle', 'Messagerie intégrée'], 'cta' => 'Parcourir l\'annuaire', 'href' => '/recherche'],
                    ['kicker' => 'Prestations', 'title' => 'Acheter une prestation', 'body' => 'Des offres packagées à prix, délai et périmètre affichés. Vous ouvrez un suivi à jalons : le règlement se fait hors plateforme.', 'points' => [format_int($services) . ' prestation' . ($services > 1 ? 's' : ''), 'Options à la carte', 'Devis, jalons et facture'], 'cta' => 'Voir les prestations', 'href' => '/prestations'],
                    ['kicker' => 'Recherche', 'title' => 'Publier une recherche', 'body' => 'Décrivez le besoin et le budget : les prestataires qualifiés vous envoient leur devis.', 'points' => [format_int($openMissions) . ' recherches ouvertes', 'Gratuit pour tous', '1ʳᵉ mission offerte, puis 8 %'], 'cta' => 'Publier une recherche', 'href' => '/espace/publier'],
                ],
            ];
        } catch (\Throwable) {
            return [
                'homeFeatured' => [],
                'homeEntry' => [],
                'homeMissions' => [],
                'journal' => [],
                'journalAll' => [],
                'services' => [],
                'missionsList' => [],
                'notifications' => [],
                'mesPrestations' => [],
                'mesMissions' => [],
                'mesCandidatures' => [],
                'favoris' => [],
                'commandes' => [],
                'threads' => [],
                'messages' => [],
                'demandes' => [],
                'enCours' => [],
            ];
        }
    }

    private static function extra(): array
    {
        $navy = self::NAVY;
        $orange = self::ORANGE;

        return [
            'metierSpecs' => [
                ['title' => 'Correction orthotypo', 'body' => 'Orthographe, grammaire, typographie française, harmonisation.', 'prix' => '3,50 – 6 € / 1 000 signes'],
                ['title' => 'Préparation de copie', 'body' => 'Mise aux normes avant maquette, feuille de style, appareil de notes.', 'prix' => '250 – 500 € / ouvrage'],
                ['title' => 'Réécriture', 'body' => 'Lissage stylistique, resserrement, cohérence narrative.', 'prix' => '8 – 15 € / page'],
                ['title' => 'Relecture sur épreuves', 'body' => 'Dernière passe sur PDF maquetté, avant bon à tirer.', 'prix' => '180 – 400 € / ouvrage'],
            ],
            'metierPrix' => [
                ['k' => 'Nouvelle ou novella (80 000 signes)', 'v' => '280 – 480 €'],
                ['k' => 'Roman (520 000 signes)', 'v' => '620 – 1 100 €'],
                ['k' => 'Essai avec notes (400 000 signes)', 'v' => '700 – 1 300 €'],
                ['k' => 'Album jeunesse (12 000 signes)', 'v' => '120 – 220 €'],
            ],
            'metierConseils' => [
                'Donnez le volume en signes, espaces comprises : c\'est l\'unité du métier.',
                'Dites si le texte a déjà été relu, et par qui.',
                'Précisez le niveau attendu : correction seule ou avec rapport de lecture.',
                'Annoncez la date du bon à tirer plutôt que « dès que possible ».',
                'Fournissez votre norme maison si vous en avez une.',
            ],
            'valeurs' => [
                ['kicker' => 'Neutralité', 'title' => 'Ni éditeur, ni agence', 'body' => 'Nous ne prenons aucun droit sur les livres, nous ne poussons aucun prestataire contre rémunération. Le classement dépend des avis et des délais tenus, pas d\'un budget publicitaire.'],
                ['kicker' => 'Prix', 'title' => 'Des repères publics', 'body' => 'Les fourchettes observées sur la plateforme sont publiées métier par métier. Un auteur qui débute doit pouvoir savoir ce que coûte une correction.'],
                ['kicker' => 'Métier', 'title' => 'Le travail suivi', 'body' => 'Devis cadrés, jalons obligatoires, médiation : le travail livré se confirme étape par étape. Le règlement se fait entre les parties, hors plateforme.'],
            ],
            'equipe' => Catalog::equipe(),
            'articleAvatar' => avatar_style('LR', 40),
            'budgetType' => [
                ['k' => 'Correction complète, 520 000 signes', 'v' => '620 – 1 100 €'],
                ['k' => 'Maquette intérieure + couverture', 'v' => '700 – 1 400 €'],
                ['k' => 'Illustration de couverture', 'v' => '300 – 900 €'],
                ['k' => 'Impression 300 ex. broché', 'v' => '1 100 – 1 600 €'],
                ['k' => 'ISBN, dépôt légal, divers', 'v' => '80 – 200 €'],
            ],
            'contactMotifs' => self::chips(['Question générale', 'Problème sur une commande', 'Presse & partenariats', 'Signaler un abus'], 0, false, true),
            'contactCanaux' => [
                ['k' => 'E-mail', 'v' => 'guillaume@editions-tesseract.fr', 'href' => 'mailto:guillaume@editions-tesseract.fr'],
                ['k' => 'Litiges', 'v' => 'olivier@editions-tesseract.fr', 'href' => 'mailto:olivier@editions-tesseract.fr'],
                ['k' => 'Presse', 'v' => 'contact@editions-tesseract.fr', 'href' => 'mailto:contact@editions-tesseract.fr'],
                ['k' => 'Facebook', 'v' => 'acteursdulivre', 'href' => Socials::FACEBOOK],
                ['k' => 'Instagram', 'v' => '@acteursdulivre.fr', 'href' => Socials::INSTAGRAM],
                ['k' => 'Adresse', 'v' => '486 rue Sadi Carnot, Sainghin-en-Weppes'],
            ],
            'legalNav' => self::sideNav(['Mentions légales', 'CGU', 'CGV', 'Confidentialité', 'Cookies'], 0),
            'legalTitle' => 'Mentions légales',
            'legalBlocks' => [
                ['title' => 'Éditeur du site', 'body' => 'acteursdulivre.fr est édité par EDITIONS TESSERACT, SAS au capital de 6 100 €, RCS Lille Métropole 980 005 292, 486 rue Sadi Carnot, 59184 Sainghin-en-Weppes.'],
                ['title' => 'Hébergement', 'body' => 'Le site est hébergé en Suisse, en Europe, par Infomaniak Network SA, Rue Eugène Marziano 25, 1227 Les Acacias (GE).'],
                ['title' => 'Objet de la plateforme', 'body' => 'La plateforme met en relation des porteurs de projet et des prestataires des métiers du livre. Elle n\'encaisse pas le prix des missions et ne génère pas de contrat type. L\'acceptation du devis vaut accord ; la plateforme suit les jalons et facture sa commission au prestataire.'],
                ['title' => 'Propriété intellectuelle', 'body' => 'Les textes, visuels et manuscrits déposés par les utilisateurs restent leur propriété. Aucune réutilisation n\'est faite au-delà de ce qui est strictement nécessaire à l\'affichage des vitrines et au bon déroulement des missions.'],
            ],
            'notifications' => self::notifications(),
            'mesPrestations' => self::mesPrestations(),
            'mesMissions' => self::mesMissions(),
            'mesCandidatures' => self::mesCandidatures(),
            'favoris' => array_map(static function (array $x, int $i): array {
                $x['img'] = photo($i);
                $x['favSlotId'] = 'fav-' . $i;
                return $x;
            }, self::services(), array_keys(self::services())),
            'avisCriteres' => self::avisCriteres(),
            'paramNav' => self::sideNav(['Profil public', 'Compte & sécurité', 'Notifications', 'Facturation', 'Confidentialité'], 0),
            'paramTitle' => 'Profil public',
            'toggles' => self::toggles(),
            'soldes' => [
                ['k' => 'Commission due', 'v' => '62 €', 'note' => 'dernier jalon, à régler sous 15 jours', 'card' => 'border-radius: 12px; padding: 20px; background: #FFF; border: 1px solid #E8ECF1; color: #022746;'],
                ['k' => 'Missions validées', 'v' => '1 640 €', 'note' => 'réglées hors plateforme', 'card' => 'border-radius: 12px; padding: 20px; background: #022746; color: #E4EDF5;'],
                ['k' => 'Commission 2026', 'v' => '1 707 €', 'note' => 'facturée au prestataire à la validation', 'card' => 'border-radius: 12px; padding: 20px; background: #FFF; border: 1px solid #E8ECF1; color: #022746;'],
            ],
            'operations' => self::operations(),
            'bancaire' => [
                ['k' => 'Titulaire', 'v' => 'Marion Vasseur'],
                ['k' => 'IBAN', 'v' => 'FR76 •••• 4412'],
                ['k' => 'Règlement', 'v' => 'Hors plateforme, entre les parties'],
            ],
            'vitrineTabs' => self::chips(['Compétences', 'Parcours & CV', 'Portfolio', 'Historique', 'Avis'], 0),
            'competences' => self::competences(),
            'outils' => ['Antidote', 'Word (révision)', 'InDesign', 'Acrobat Pro', 'LibreOffice', 'Zotero'],
            'langues' => [
                ['langue' => 'Français', 'niveau' => 'langue maternelle'],
                ['langue' => 'Anglais', 'niveau' => 'courant — lecture technique'],
                ['langue' => 'Latin', 'niveau' => 'notions — citations et appareil critique'],
            ],
            'genres' => self::chips(['Roman', 'Polar', 'Jeunesse 10+', 'Essai', 'Histoire', 'Poésie', 'BD', 'Beau livre'], 0, false, true, [true, true, true, true, true, false, false, false]),
            'experiences' => [
                ['periode' => '2022 — aujourd\'hui', 'poste' => 'Correctrice-relectrice indépendante', 'lieu' => 'Nantes · à distance', 'detail' => '87 ouvrages corrigés pour des maisons d\'édition, des collectifs et des auteurs autoédités. Romans, essais, jeunesse.'],
                ['periode' => '2016 — 2022', 'poste' => 'Responsable de la préparation de copie', 'lieu' => 'Éditions du Chardon, Paris', 'detail' => 'Encadrement de six correcteurs indépendants, écriture de la norme maison, suivi de 40 titres par an.'],
                ['periode' => '2013 — 2016', 'poste' => 'Correctrice', 'lieu' => 'Éditions du Chardon, Paris', 'detail' => 'Correction et préparation de copie sur la collection de littérature générale.'],
                ['periode' => '2011 — 2013', 'poste' => 'Assistante d\'édition', 'lieu' => 'Revue Marges, Lyon', 'detail' => 'Suivi éditorial d\'une revue trimestrielle, relations auteurs et imprimeur.'],
            ],
            'formations' => [
                ['annee' => '2011', 'intitule' => 'Master Métiers du livre et de l\'édition', 'ecole' => 'Université Lyon 2'],
                ['annee' => '2015', 'intitule' => 'Certification en orthotypographie française', 'ecole' => 'Asfored'],
                ['annee' => '2021', 'intitule' => 'Formation Antidote et outils de révision', 'ecole' => 'Druide informatique'],
            ],
            'references' => [
                ['nom' => 'Éditions du Chardon', 'role' => 'Direction éditoriale — Paris', 'statut' => 'Vérifiée', 'statutStyle' => 'display: inline-block; margin-top: 12px; font-family: \'Space Grotesk\', monospace; font-size: 11px; letter-spacing: .06em; text-transform: uppercase; padding: 4px 8px; border-radius: 4px; background: #FDF3F0; color: #D85D3F;'],
                ['nom' => 'Éditions La Ligne', 'role' => 'Fabrication — Lyon', 'statut' => 'Vérifiée', 'statutStyle' => 'display: inline-block; margin-top: 12px; font-family: \'Space Grotesk\', monospace; font-size: 11px; letter-spacing: .06em; text-transform: uppercase; padding: 4px 8px; border-radius: 4px; background: #FDF3F0; color: #D85D3F;'],
                ['nom' => 'Revue Marges', 'role' => 'Rédaction en chef', 'statut' => 'En attente', 'statutStyle' => 'display: inline-block; margin-top: 12px; font-family: \'Space Grotesk\', monospace; font-size: 11px; letter-spacing: .06em; text-transform: uppercase; padding: 4px 8px; border-radius: 4px; background: #F4F6F9; color: #8496A8;'],
            ],
            'portfolioEdit' => self::portfolioEdit(),
            'histStats' => [
                ['v' => '87', 'k' => 'missions livrées'],
                ['v' => '21 340 €', 'k' => 'de missions en 2026'],
                ['v' => '4,9', 'k' => 'note moyenne'],
                ['v' => '98 %', 'k' => 'délais tenus'],
            ],
            'historique' => self::historique(),
            'avisTabs' => self::chips(['Avis reçus (87)', 'Avis que j\'ai laissés (14)'], 0),
            'avisListe' => self::avisVitrine(),
            'vitrineTodo' => self::todo([
                ['label' => 'Métiers et compétences renseignés', 'ok' => true],
                ['label' => 'Trois expériences ou plus', 'ok' => true],
                ['label' => 'Six visuels au portfolio', 'ok' => true],
                ['label' => 'Deux références vérifiées — la troisième est en attente', 'ok' => false],
                ['label' => 'Justificatif d\'activité à jour pour le badge « vérifié »', 'ok' => false],
                ['label' => 'Engagement sans IA générative signé le 14 mars 2026', 'ok' => true],
            ]),
            'vitrineStats' => [
                ['k' => 'Vues du profil (30 j)', 'v' => '1 840'],
                ['k' => 'Taux de contact', 'v' => '4,7 %'],
                ['k' => 'Position moyenne', 'v' => '6ᵉ sur Correction'],
            ],
            'iaEngagements' => self::iaEngagements(),
            'iaPoints' => [
                'Pas de texte, d\'illustration ou de voix généré par IA',
                'Pas de traduction automatique post-éditée sans accord écrit',
                'Aucun manuscrit utilisé pour entraîner un modèle',
                'Manquement : mission remboursée, profil retiré',
            ],
            'missionFilters' => self::chips(['Tous les métiers', 'Correction', 'Bêta-lecture', 'Illustration', 'Traduction', 'Impression', 'Presse & com'], 0),
            'missionsList' => self::missionsList(),
            'candidaterTips' => [
                'Citez deux références comparables : genre, volume, éditeur.',
                'Proposez un prix ferme et un délai que vous tiendrez.',
                'Posez une question sur le texte : c\'est ce qui distingue une vraie candidature.',
            ],
            'missionFacts' => [
                ['k' => 'Budget annoncé', 'v' => '600 – 900 €'],
                ['k' => 'Volume', 'v' => '420 000 signes'],
                ['k' => 'Échéance', 'v' => '12 septembre'],
                ['k' => 'Métier', 'v' => 'Correction'],
            ],
            'missionAttendus' => [
                'Correction orthotypographique complète, français',
                'Harmonisation des noms propres anciens et des citations latines',
                'Bibliographie mise à la norme maison (document fourni)',
                'Livraison en mode révision, Word ou InDesign',
            ],
            'missionFiles' => [
                ['ext' => 'PDF', 'name' => 'extrait-40-pages.pdf', 'size' => '2,1 Mo'],
                ['ext' => 'DOCX', 'name' => 'norme-maison-biblio.docx', 'size' => '180 Ko'],
            ],
            'porteurMeta' => [
                ['k' => 'Missions publiées', 'v' => '23'],
                ['k' => 'Taux d\'attribution', 'v' => '91 %'],
                ['k' => 'Délai de réponse', 'v' => '12 h'],
                ['k' => 'Membre depuis', 'v' => '2023'],
            ],
            'candidatures' => [
                ['name' => 'Paul Ferrand', 'initials' => 'PF', 'price' => '980 €', 'delay' => '2 semaines', 'avatar' => avatar_style('PF', 30)],
                ['name' => 'Atelier Virgule', 'initials' => 'AV', 'price' => '820 €', 'delay' => '3 semaines', 'avatar' => avatar_style('AV', 30)],
                ['name' => 'Nadia Chaumet', 'initials' => 'NC', 'price' => '640 €', 'delay' => '4 semaines', 'avatar' => avatar_style('NC', 30)],
            ],
            'suiviSteps' => self::suiviSteps(),
            'livraison' => [
                ['ext' => 'DOCX', 'name' => 'essai-historique-corrige-v1.docx', 'size' => '3,4 Mo'],
                ['ext' => 'PDF', 'name' => 'rapport-de-lecture.pdf', 'size' => '740 Ko'],
            ],
            'suiviPaiement' => [
                ['k' => 'Mission', 'v' => '780 €'],
                ['k' => 'Acompte', 'v' => '234 €'],
                ['k' => 'Solde (hors plateforme)', 'v' => '546 €'],
                ['k' => 'Commission prestataire', 'v' => '62 €'],
            ],
            'suiviDocs' => ['Devis accepté', 'Facture d\'acompte', 'Note de livraison'],
            'commandeTabs' => self::chips(['En cours (3)', 'Livrées (14)', 'En litige (0)', 'Brouillons (2)'], 0),
            'commandes' => self::commandes(),
            'creerSteps' => self::steps(['La prestation', 'Les formules', 'Le visuel', 'Publication'], 0),
            'creerFormules' => [
                ['name' => 'Essentielle', 'desc' => 'Correction orthotypographique', 'price' => '420 €', 'delay' => '8 jours'],
                ['name' => 'Standard', 'desc' => '+ rapport de lecture, 1 aller-retour', 'price' => '780 €', 'delay' => '3 semaines'],
                ['name' => 'Complète', 'desc' => '2 passes + appel de restitution', 'price' => '1 250 €', 'delay' => '5 semaines'],
            ],
            'prenom' => 'Marion',
            'nom' => 'Vasseur',
            'titreVitrine' => 'Correctrice-relectrice · romans, essais, jeunesse',
            'presentation' => 'Douze ans en maison d\'édition, aujourd\'hui indépendante.',
            'creerVisuels' => [
                ['img' => photo(0), 'slotId' => 'creer-0'],
                ['img' => photo(5), 'slotId' => 'creer-1'],
                ['img' => photo(4), 'slotId' => 'creer-2'],
                ['img' => photo(2), 'slotId' => 'creer-3'],
            ],
            'creerOptions' => [
                ['label' => 'Livraison accélérée (−5 jours)', 'price' => '120 €'],
                ['label' => 'Feuille de style typographique', 'price' => '60 €'],
                ['label' => 'Passe supplémentaire sur épreuves', 'price' => '180 €'],
            ],
            'checklist' => self::todo([
                ['label' => 'Titre clair, sans superlatif', 'ok' => true],
                ['label' => 'Trois formules renseignées', 'ok' => true],
                ['label' => 'Visuel optionnel — sinon visuel charté ADL', 'ok' => true],
                ['label' => 'Périmètre et exclusions précisés', 'ok' => false],
                ['label' => 'Délais réalistes sur votre charge actuelle', 'ok' => false],
            ]),
            'roles' => [
                ['title' => 'Je cherche des prestataires', 'desc' => 'Auteur, éditeur, collectif : commandez des prestations ou publiez vos recherches.', 'style' => 'border: 1.5px solid #E8ECF1; background: #FFF; border-radius: 12px; padding: 20px; cursor: pointer;'],
                ['title' => 'Je propose mes services', 'desc' => 'Correcteur, bêta-lecteur, illustrateur, imprimeur, libraire : créez votre vitrine et vos formules.', 'style' => 'border: 1.5px solid #022746; background: #FBFCFE; border-radius: 12px; padding: 20px; cursor: pointer;'],
            ],
            'onboarding' => [
                ['num' => '01', 'title' => 'Un compte, deux usages', 'body' => 'Cherchez des prestataires, proposez vos services, ou les deux. Rien n\'est exclusif.'],
                ['num' => '02', 'title' => 'Un espace qui s\'adapte', 'body' => 'Les menus et actions correspondent à ce que vous avez choisi. Vous pourrez modifier ce choix plus tard.'],
                ['num' => '03', 'title' => 'L\'engagement sans IA', 'body' => 'Si vous proposez vos services, vous signez l\'engagement : aucun livrable produit par une IA générative.'],
                ['num' => '04', 'title' => 'Vous commencez tout de suite', 'body' => 'Publiez une recherche, complétez votre vitrine, ou parcourez l\'annuaire — selon votre choix.'],
            ],
            'inscriptionProof' => [
                ['v' => '0 €', 'k' => 'aucun abonnement'],
                ['v' => '1ʳᵉ', 'k' => 'mission offerte au prestataire'],
                ['v' => '8 %', 'k' => 'dès la 2ᵉ mission, sans abonnement'],
            ],
            'ways' => [
                ['kicker' => 'Annuaire', 'title' => 'Chercher un profil', 'body' => 'Filtrez par métier, spécialité, ville et tarif, puis engagez la discussion.', 'points' => ['Profils publiés', 'Avis après mission réelle', 'Messagerie intégrée'], 'cta' => 'Parcourir l\'annuaire', 'href' => '/recherche'],
                ['kicker' => 'Prestations', 'title' => 'Acheter une prestation', 'body' => 'Des offres packagées à prix, délai et périmètre affichés. Vous ouvrez un suivi à jalons : le règlement se fait hors plateforme.', 'points' => ['Prix affiché', 'Options à la carte', 'Devis, jalons et facture'], 'cta' => 'Voir les prestations', 'href' => '/prestations'],
                ['kicker' => 'Recherche', 'title' => 'Publier une recherche', 'body' => 'Décrivez le besoin et le budget : les prestataires qualifiés vous envoient leur devis.', 'points' => ['Appels d\'offres ouverts', 'Candidatures gratuites', '1ʳᵉ mission offerte, puis 8 %'], 'cta' => 'Publier une recherche', 'href' => '/espace/publier'],
            ],
            'steps4' => [
                ['num' => '01', 'title' => 'Décrivez le besoin', 'body' => 'Métier, brief, budget, échéance : un formulaire court.'],
                ['num' => '02', 'title' => 'Comparez', 'body' => 'Devis, réalisations, avis vérifiés et délais côte à côte.'],
                ['num' => '03', 'title' => 'Travaillez cadré', 'body' => 'Jalons obligatoires : devis, factures, règlements déclarés, livraison. Messagerie intégrée.'],
                ['num' => '04', 'title' => 'Validez et notez', 'body' => 'Le client confirme la mission, note la prestation : la commission prestataire est alors facturée.'],
            ],
            'commentImg' => photo(2),
            'niveaux' => self::niveaux(),
            'exemple' => [
                ['k' => 'Prestation vendue (montant HT)', 'v' => '780 €', 'style' => 'color: #14202C;'],
                ['k' => '1ʳᵉ mission : commission', 'v' => '0 €', 'style' => 'color: #2E6B45;'],
                ['k' => 'À partir de la 2ᵉ : 8 % sur le HT', 'v' => '− 62 €', 'style' => 'color: #D85D3F;'],
                ['k' => 'Le prestataire conserve (dès la 2ᵉ)', 'v' => '718 €', 'style' => 'color: #022746; font-weight: 700; font-family: \'Space Grotesk\', sans-serif;'],
            ],
            'gratuit' => [
                'Créer un compte, une vitrine et autant de fiches que nécessaire — aucun abonnement',
                'Publier une recherche, proposer une mission ou une prestation',
                'Candidater aux appels d\'offres',
                'Échanger par messagerie, envoyer des devis et suivre les jalons',
                'La première mission réalisée : aucune commission',
            ],
            'garanties' => [
                ['kicker' => 'Profils', 'title' => 'Vérification à l\'entrée', 'body' => 'Justificatif d\'activité, une référence professionnelle contrôlée et un entretien pour les métiers de fabrication. Les faux profils sont retirés sous 24 h.'],
                ['kicker' => 'Suivi', 'title' => 'Jalons, pas d\'encaissement', 'body' => 'Client et prestataire se règlent hors plateforme. Le suivi impose les jalons (devis, factures, déclarations, livraison, validation). La commission est facturée au prestataire à la validation, payable sous 15 jours.'],
                ['kicker' => 'Litiges', 'title' => 'Médiation interne', 'body' => 'Un signalement met les jalons en pause. L\'équipe lit les échanges et propose un accord sur les sommes déjà versées ou encore dues. Recours possible devant la juridiction compétente.'],
            ],
            'charte' => [
                ['num' => '01', 'title' => 'Prix annoncé, prix facturé', 'body' => 'Aucun supplément qui n\'ait été accepté par écrit avant le démarrage.'],
                ['num' => '02', 'title' => 'Délais tenus ou prévenus', 'body' => 'Un retard se signale dès qu\'il est prévisible, pas le jour de la livraison.'],
                ['num' => '03', 'title' => 'Confidentialité du manuscrit', 'body' => 'Aucun extrait diffusé sans autorisation écrite, y compris dans un portfolio.'],
                ['num' => '04', 'title' => 'Périmètre clair', 'body' => 'Ce qui est inclus, les délais et, le cas échéant, l’usage des livrables : c’est écrit dans le devis.'],
                ['num' => '05', 'title' => 'Aucune IA générative', 'body' => 'Textes, illustrations et voix livrés aux acteurs du livre sont produits par des humains. Pas de génération automatique, pas de sous-traitance cachée, pas d\'entraînement de modèle sur les manuscrits confiés.'],
                ['num' => '06', 'title' => 'Avis sincères', 'body' => 'Le client note la qualité, l\'efficacité et la satisfaction globale lorsqu\'il confirme que la mission est terminée.'],
            ],
            'litige' => [
                ['num' => '01', 'title' => 'Signalement', 'body' => 'Depuis le suivi de commande, en décrivant l\'écart au brief. Les jalons sont en pause le temps de l\'examen.'],
                ['num' => '02', 'title' => 'Échange encadré', 'body' => '72 h pour trouver un accord dans la messagerie, avec un modérateur en lecture.'],
                ['num' => '03', 'title' => 'Médiation', 'body' => 'À défaut d\'accord, un médiateur propose un accord sur les sommes déjà versées ou encore dues entre les parties.'],
                ['num' => '04', 'title' => 'Recours', 'body' => 'Vous restez libre de saisir la juridiction compétente.'],
            ],
            'contrats' => ['Prestation de service — correction, maquette', 'Cession de droits — illustration, traduction', 'Mandat — presse et communication', 'Bon de commande — impression'],
            'aideCats' => [
                ['title' => 'Commandes & livraisons', 'desc' => 'Commander, valider, annuler, prolonger un délai.', 'n' => 24],
                ['title' => 'Paiements & factures', 'desc' => 'Jalons, règlements hors plateforme, TVA, commission.', 'n' => 18],
                ['title' => 'Profil & vitrine', 'desc' => 'Vérification, badges, visibilité dans l\'annuaire.', 'n' => 15],
                ['title' => 'Charte & confidentialité', 'desc' => 'Manuscrits, IA générative, mentions obligatoires.', 'n' => 11],
            ],
            'aideFaq' => self::aideFaq(),
            'commentFaq' => self::faqItems(array_map(
                static fn (array $row): array => [$row['q'], $row['a']],
                Seo::commentFaqs()
            )),
            'tarifsFaq' => self::faqItems(array_map(
                static fn (array $row): array => [$row['q'], $row['a']],
                Seo::tarifsFaqs()
            )),
            'checkoutSteps' => self::steps(['Le brief', 'Les jalons', 'Le suivi'], 1, false),
            'paiements' => self::paiements(),
            'badges' => ['Profil vérifié', 'Répond en 24 h', '98 % dans les délais', '87 missions livrées', 'Membre depuis 2022'],
            'profilStats' => [
                ['v' => '87', 'k' => 'missions livrées'],
                ['v' => '4,9', 'k' => 'note moyenne'],
                ['v' => '6 h', 'k' => 'délai de réponse'],
                ['v' => '98 %', 'k' => 'livrées dans les délais'],
            ],
            'profilTags' => ['Roman', 'Polar', 'Essai', 'Jeunesse 10+', 'Orthotypographie', 'Antidote', 'InDesign', 'Français, anglais'],
            'portfolio' => self::portfolio(),
            'profilOffres' => [
                ['cat' => 'Correction', 'title' => 'Correction complète d\'un roman jusqu\'à 90 000 signes', 'delay' => '8 jours', 'price' => '420 €'],
                ['cat' => 'Préparation', 'title' => 'Préparation de copie avant maquette', 'delay' => '5 jours', 'price' => '340 €'],
                ['cat' => 'Relecture', 'title' => 'Relecture typographique sur épreuves PDF', 'delay' => '4 jours', 'price' => '260 €'],
            ],
            'profilInfos' => [
                ['k' => 'Délai type', 'v' => '8 à 14 jours'],
                ['k' => 'Langues', 'v' => 'FR, EN'],
                ['k' => 'Localisation', 'v' => 'Nantes · à distance'],
                ['k' => 'Statut', 'v' => 'Micro-entreprise'],
                ['k' => 'Disponibilité', 'v' => 'dès le 1er sept.'],
            ],
            'steps' => self::steps(['Le besoin', 'Le budget', 'Les pièces', 'Publication'], 0),
            'metierChips' => self::chips(['Correction', 'Bêta-lecture', 'Illustration', 'Traduction', 'Maquette', 'Impression', 'Presse & com'], 0, false, true),
            'missionTitle' => '',
            'missionBrief' => '',
            'previewTitle' => 'Recherche correcteur pour essai historique, 240 pages',
            'previewMetier' => 'Correction',
            'suggestions' => [
                ['name' => 'Marion Vasseur', 'initials' => 'MV', 'role' => 'Correctrice · Nantes', 'rating' => '4,9', 'avatar' => avatar_style('MV', 34)],
                ['name' => 'Paul Ferrand', 'initials' => 'PF', 'role' => 'Correcteur essais · Lille', 'rating' => '4,9', 'avatar' => avatar_style('PF', 34)],
                ['name' => 'Atelier Virgule', 'initials' => 'AV', 'role' => 'Relecture épreuves · Paris', 'rating' => '4,8', 'avatar' => avatar_style('AV', 34)],
            ],
            'publierSuite' => [
                'Votre mission est visible par les prestataires du métier choisi.',
                'Vous recevez en moyenne trois devis en 48 heures.',
                'Vous validez un devis : contrat signé, la mission démarre.',
            ],
            'threads' => self::threads(),
            'threadWho' => 'Marion Vasseur',
            'threadSub' => 'Correction — essai historique, 240 pages',
            'threadInitials' => 'MV',
            'threadAvatar' => avatar_style('MV', 36),
            'messages' => self::messages(),
            'quickReplies' => ['Envoyer un devis', 'Proposer un appel', 'Demander le manuscrit'],
            'devis' => [
                ['k' => 'Correction complète', 'v' => '640 €'],
                ['k' => 'Rapport de lecture', 'v' => '140 €'],
                ['k' => 'Aller-retour inclus', 'v' => '1'],
                ['k' => 'Livraison', 'v' => '3 semaines'],
            ],
            'draft' => '',
            'missionMeta' => [
                ['k' => 'Métier', 'v' => 'Correction'],
                ['k' => 'Volume', 'v' => '420 000 signes'],
                ['k' => 'Budget annoncé', 'v' => '600 – 900 €'],
                ['k' => 'Échéance', 'v' => '12 sept.'],
                ['k' => 'Candidatures', 'v' => '7'],
            ],
            'jalons' => [
                ['label' => 'Devis accepté', 'when' => '8 sept.', 'dot' => 'width: 10px; height: 10px; min-width: 10px; border-radius: 50%; background: #D85D3F; margin-top: 5px;'],
                ['label' => 'Première passe livrée', 'when' => '22 sept.', 'dot' => 'width: 10px; height: 10px; min-width: 10px; border-radius: 50%; border: 2px solid #C3CEDA; margin-top: 5px;'],
                ['label' => 'Validation et commission', 'when' => '29 sept.', 'dot' => 'width: 10px; height: 10px; min-width: 10px; border-radius: 50%; border: 2px solid #C3CEDA; margin-top: 5px;'],
            ],
            'kpis' => [
                ['k' => 'Demandes ce mois', 'v' => '18', 'd' => '+5 vs août'],
                ['k' => 'Taux d\'acceptation', 'v' => '62 %', 'd' => '11 devis signés'],
                ['k' => 'Revenus du mois', 'v' => '3 480 €', 'd' => 'commission déduite'],
                ['k' => 'Note moyenne', 'v' => '4,9', 'd' => 'sur 87 avis'],
            ],
            'chart' => self::chart(),
            'demandes' => [
                ['title' => 'Essai historique, 240 pages — correction complète', 'by' => 'Éditions du Fleuve Noirci', 'when' => 'il y a 2 h', 'budget' => '600 – 900 €', 'deadline' => '12 sept.'],
                ['title' => 'Polar, 380 000 signes — préparation de copie', 'by' => 'Éditions Pampa', 'when' => 'hier', 'budget' => '750 €', 'deadline' => '30 sept.'],
                ['title' => 'Recueil jeunesse — relecture sur épreuves', 'by' => 'Camille D.', 'when' => 'hier', 'budget' => '260 €', 'deadline' => '20 sept.'],
                ['title' => 'Mémoire d\'entreprise, 120 pages', 'by' => 'Atelier Mémoires', 'when' => '3 sept.', 'budget' => '480 €', 'deadline' => '15 oct.'],
            ],
            'enCours' => [
                ['title' => 'Roman — Éditions La Ligne', 'pct' => '70 %', 'note' => 'passe 1 terminée, rapport en cours', 'bar' => 'height: 100%; width: 70%; background: #D85D3F;'],
                ['title' => 'Album jeunesse — Camille D.', 'pct' => '40 %', 'note' => 'livraison prévue le 18 sept.', 'bar' => 'height: 100%; width: 40%; background: #D85D3F;'],
                ['title' => 'Essai — Collectif Encre Vive', 'pct' => '15 %', 'note' => 'démarrage lundi', 'bar' => 'height: 100%; width: 15%; background: #D85D3F;'],
            ],
        ];
    }

    private static function services(): array
    {
        return [
            ['cat' => 'Correction', 'title' => 'Correction complète d\'un roman jusqu\'à 90 000 signes', 'by' => 'Marion Vasseur', 'initials' => 'MV', 'price' => '420 €', 'rating' => '4,9', 'reviews' => 87, 'delay' => '8 jours', 'level' => 'Confirmée', 'tag' => 'Choix de la rédaction'],
            ['cat' => 'Correction', 'title' => 'Relecture typographique sur épreuves PDF', 'by' => 'Atelier Virgule', 'initials' => 'AV', 'price' => '260 €', 'rating' => '4,8', 'reviews' => 52, 'delay' => '4 jours', 'level' => 'Experte', 'tag' => ''],
            ['cat' => 'Correction', 'title' => 'Correction d\'un essai ou document, jusqu\'à 300 pages', 'by' => 'Paul Ferrand', 'initials' => 'PF', 'price' => '640 €', 'rating' => '4,9', 'reviews' => 38, 'delay' => '12 jours', 'level' => 'Confirmé', 'tag' => ''],
            ['cat' => 'Correction', 'title' => 'Réécriture et lissage stylistique, forfait 50 pages', 'by' => 'Nadia Chaumet', 'initials' => 'NC', 'price' => '580 €', 'rating' => '5,0', 'reviews' => 24, 'delay' => '10 jours', 'level' => 'Nouvelle', 'tag' => 'Nouveau'],
            ['cat' => 'Correction', 'title' => 'Préparation de copie avant maquette', 'by' => 'Studio Grain', 'initials' => 'SG', 'price' => '340 €', 'rating' => '4,7', 'reviews' => 61, 'delay' => '5 jours', 'level' => 'Confirmé', 'tag' => ''],
            ['cat' => 'Correction', 'title' => 'Relecture jeunesse, album et premières lectures', 'by' => 'Claire Ozanne', 'initials' => 'CO', 'price' => '190 €', 'rating' => '4,9', 'reviews' => 45, 'delay' => '3 jours', 'level' => 'Experte', 'tag' => 'Répond en 1 h'],
        ];
    }

    private static function homeEntry(): array
    {
        $rows = [
            [
                'kind' => 'Diagnostic',
                'title' => 'Diagnostic de manuscrit',
                'volume' => 'jusqu\'à 20 000 signes',
                'by' => 'Marion Vasseur',
                'initials' => 'MV',
                'price' => '120 €',
                'rating' => '4,9',
                'reviews' => 87,
                'delay' => '5 jours',
            ],
            [
                'kind' => 'Relecture',
                'title' => 'Relecture jeunesse, album',
                'volume' => 'jusqu\'à 32 pages',
                'by' => 'Claire Ozanne',
                'initials' => 'CO',
                'price' => '190 €',
                'rating' => '4,9',
                'reviews' => 45,
                'delay' => '3 jours',
            ],
            [
                'kind' => 'Avis de lecture',
                'title' => 'Avis de lecture argumenté',
                'volume' => 'rapport de 8 pages',
                'by' => 'Paul Ferrand',
                'initials' => 'PF',
                'price' => '160 €',
                'rating' => '4,9',
                'reviews' => 38,
                'delay' => '6 jours',
            ],
        ];

        return array_map(static function (array $x, int $i): array {
            $x['img'] = photo(($i + 3) % 6);
            $x['homeSlotId'] = 'home-entry-' . $i;
            $x['avatar'] = avatar_style($x['initials'], 26);
            $x['href'] = '/prestations/' . slugify($x['title']);
            return $x;
        }, $rows, array_keys($rows));
    }

    private static function homeMetiers(): array
    {
        $rows = [
            ['01', 'Auteurs', '1 240'], ['02', 'Correcteurs', '1 105'], ['03', 'Bêta-lecteurs', '210'], ['04', 'Illustrateurs', '860'],
            ['05', 'Traducteurs', '520'], ['06', 'Maquettistes', '470'], ['07', 'Éditeurs', '310'], ['08', 'Imprimeurs', '148'],
            ['09', 'Presse & com', '236'], ['10', 'Libraires', '690'], ['11', 'Narrateurs audio', '174'], ['12', 'Agents littéraires', '62'],
            ['13', 'Salons & événements', '98'], ['14', 'Iconographes', '84'], ['15', 'Lecteurs éditoriaux', '112'],
            ['16', 'Photographes', '76'], ['17', 'Relieurs', '41'], ['18', 'Juristes', '38'],
        ];
        return array_map(static fn (array $m): array => [
            'num' => $m[0],
            'name' => $m[1],
            'count' => $m[2],
            'countLabel' => $m[2] . ' profils',
            'href' => '/metiers/' . slugify($m[1]),
        ], $rows);
    }

    private static function homeMissions(): array
    {
        $list = [
            ['title' => 'Recherche correcteur pour essai historique, 240 pages', 'by' => 'Éditions du Fleuve Noirci', 'cat' => 'Correction', 'volume' => '420 000 signes', 'when' => 'il y a 2 j', 'budget' => '600 – 900 €', 'deadline' => '12 sept.', 'applicants' => 7, 'urgence' => 'Clôture dans 4 jours', 'tone' => 'urgent', 'who' => ['PF', 'AV', 'NC']],
            ['title' => 'Illustrateur album jeunesse 3-6 ans, 24 pages', 'by' => 'Camille D., autrice', 'cat' => 'Illustration', 'volume' => '24 pages couleur', 'when' => 'il y a 3 j', 'budget' => '1 800 – 2 500 €', 'deadline' => '18 sept.', 'applicants' => 14, 'urgence' => 'Très demandée', 'tone' => 'hot', 'who' => ['AK', 'SG', 'CO']],
            ['title' => 'Traduction ES→FR d\'un recueil de nouvelles', 'by' => 'Éditions Pampa', 'cat' => 'Traduction', 'volume' => '180 000 signes', 'when' => 'il y a 4 j', 'budget' => '3 200 €', 'deadline' => '30 sept.', 'applicants' => 4, 'urgence' => 'Peu de candidats', 'tone' => 'calm', 'who' => ['SR', 'PF']],
            ['title' => 'Attaché de presse, premier roman, sortie janvier', 'by' => 'Éditions La Ligne', 'cat' => 'Presse & com', 'volume' => 'campagne 2 mois', 'when' => 'il y a 6 j', 'budget' => '2 000 – 3 000 €', 'deadline' => '22 sept.', 'applicants' => 9, 'urgence' => '', 'tone' => '', 'who' => ['HA', 'AD']],
            ['title' => 'Impression 500 ex. broché, papier recyclé', 'by' => 'Collectif Encre Vive', 'cat' => 'Impression', 'volume' => '500 ex. · éco-labels', 'when' => 'il y a 1 sem.', 'budget' => '1 400 – 2 000 €', 'deadline' => '5 oct.', 'applicants' => 9, 'urgence' => '', 'tone' => '', 'who' => ['IB', 'SG']],
        ];
        foreach ($list as &$m) {
            $m['urgenceStyle'] = $m['urgence'] === ''
                ? 'display: none;'
                : 'font-size: 12px; padding: 4px 9px; border-radius: 999px; ' . match ($m['tone']) {
                    'urgent' => 'background: rgba(216,93,63,.9); color: #FFF;',
                    'hot' => 'background: rgba(255,255,255,.14); color: #FFF;',
                    default => 'background: rgba(255,255,255,.08); color: #A9C0D5;',
                };
            $m['avatars'] = [];
            foreach ($m['who'] as $i => $w) {
                $m['avatars'][] = [
                    'initials' => $w,
                    'style' => avatar_style($w, 30) . ' border: 2px solid #022746; margin-left: ' . ($i === 0 ? 0 : -10) . 'px;',
                ];
            }
            $m['href'] = '/missions/' . slugify($m['title']);
        }
        return $list;
    }

    private static function journalPreview(): array
    {
        return [
            ['cat' => 'Tarifs', 'read' => '8 min', 'title' => 'Combien coûte vraiment la fabrication d\'un roman en autoédition ?', 'chapo' => '1 240 missions livrées passées au crible, poste par poste.', 'img' => photo(5), 'slotId' => 'jr-0', 'href' => '/journal/cout-fabrication-roman-autoedition'],
            ['cat' => 'Contrats', 'read' => '6 min', 'title' => 'Cession de droits en illustration : les cinq lignes à ne pas oublier', 'chapo' => 'Durée, territoires, supports, exclusivité, réédition.', 'img' => photo(1), 'slotId' => 'jr-1', 'href' => '/journal/cession-droits-illustration'],
            ['cat' => 'Métier', 'read' => '5 min', 'title' => 'Préparation de copie ou correction : ce que vous achetez vraiment', 'chapo' => 'Deux prestations souvent confondues, deux budgets très différents.', 'img' => photo(4), 'slotId' => 'jr-2', 'href' => '/journal/preparation-copie-ou-correction'],
        ];
    }

    private static function journalAll(): array
    {
        return [
            ['cat' => 'Contrats', 'read' => '6 min', 'title' => 'Cession de droits en illustration : les cinq lignes à ne pas oublier', 'chapo' => 'Durée, territoires, supports, exclusivité, réédition.', 'img' => photo(1), 'slotId' => 'ja-0'],
            ['cat' => 'Métier', 'read' => '5 min', 'title' => 'Préparation de copie ou correction : ce que vous achetez vraiment', 'chapo' => 'Deux prestations confondues, deux budgets très différents.', 'img' => photo(4), 'slotId' => 'ja-1'],
            ['cat' => 'Fabrication', 'read' => '9 min', 'title' => 'Choisir son papier sans se ruiner : bouffant, offset, recyclé', 'chapo' => 'Le grammage change le prix, mais surtout la main du livre.', 'img' => photo(2), 'slotId' => 'ja-2'],
            ['cat' => 'Diffusion', 'read' => '7 min', 'title' => 'Faire entrer son livre en librairie quand on est autoédité', 'chapo' => 'Ce que les libraires attendent vraiment d\'un dépôt.', 'img' => photo(5), 'slotId' => 'ja-3'],
            ['cat' => 'Métier', 'read' => '4 min', 'title' => 'Livre audio : combien de temps de studio pour 300 pages ?', 'chapo' => 'Les ratios observés chez les narrateurs de la plateforme.', 'img' => photo(3), 'slotId' => 'ja-4'],
            ['cat' => 'Tarifs', 'read' => '6 min', 'title' => 'Pourquoi un devis de traduction se calcule au signe', 'chapo' => 'Et comment comparer deux devis qui n\'ont pas la même unité.', 'img' => photo(0), 'slotId' => 'ja-5'],
        ];
    }

    private static function chips(array $labels, int $active, bool $light = false, bool $orange = false, ?array $on = null): array
    {
        $out = [];
        foreach ($labels as $i => $label) {
            $isOn = $on ? !empty($on[$i]) : $i === $active;
            if ($light) {
                $style = 'border: 1px solid ' . ($isOn ? 'rgba(255,255,255,.55)' : 'rgba(255,255,255,.18)') . '; background: ' . ($isOn ? 'rgba(255,255,255,.14)' : 'transparent') . '; color: ' . ($isOn ? '#FFF' : '#A9C0D5') . '; border-radius: 999px; padding: 8px 15px; font-size: 14px;';
            } elseif ($orange) {
                $style = 'border: 1px solid ' . ($isOn ? self::ORANGE : '#E1E7ED') . '; background: ' . ($isOn ? '#FDF3F0' : '#FFF') . '; color: ' . ($isOn ? self::ORANGE : '#4A5A6B') . '; border-radius: 999px; padding: 9px 15px; font-size: 14px;';
            } else {
                $style = 'border: 1px solid ' . ($isOn ? self::NAVY : '#E1E7ED') . '; background: ' . ($isOn ? self::NAVY : '#FFF') . '; color: ' . ($isOn ? '#FFF' : '#4A5A6B') . '; border-radius: 999px; padding: 9px 16px; font-size: 14px;';
            }
            $out[] = ['label' => $label, 'style' => $style];
        }
        return $out;
    }

    private static function sideNav(array $labels, int $active): array
    {
        $out = [];
        foreach ($labels as $i => $label) {
            $on = $i === $active;
            $out[] = [
                'label' => $label,
                'style' => 'padding: 11px 14px; border-radius: 9px; font-size: 14px; cursor: pointer; background: ' . ($on ? '#F4F6F9' : 'transparent') . '; color: ' . ($on ? self::NAVY : '#66768A') . '; font-weight: ' . ($on ? '500' : '400') . ';',
            ];
        }
        return $out;
    }

    private static function steps(array $labels, int $active, bool $clickable = true): array
    {
        $out = [];
        foreach ($labels as $i => $label) {
            $on = $clickable ? $i === $active : $i <= $active;
            $out[] = [
                'label' => $label,
                'num' => '0' . ($i + 1),
                'style' => 'flex: 1; padding: 14px 18px; ' . ($clickable ? 'cursor: pointer; ' : '') . 'border-bottom: 3px solid ' . ($on ? self::ORANGE : '#E8ECF1') . '; color: ' . ($on ? self::NAVY : '#8496A8') . ';',
            ];
        }
        return $out;
    }

    private static function todo(array $items): array
    {
        foreach ($items as &$t) {
            $t['sign'] = $t['ok'] ? '✓' : '•';
            $t['mark'] = 'width: 18px; height: 18px; min-width: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; margin-top: 1px; ' . ($t['ok'] ? 'background: #D85D3F; color: #FFF;' : 'border: 1.5px solid #C3CEDA; color: #C3CEDA;');
        }
        return $items;
    }

    private static function filters(): array
    {
        return [
            ['label' => 'Spécialité', 'options' => [['l' => 'Global', 'n' => 210], ['l' => 'Roman', 'n' => 184], ['l' => 'Jeunesse', 'n' => 96], ['l' => 'Essai / document', 'n' => 78], ['l' => 'BD & graphique', 'n' => 31], ['l' => 'Poésie', 'n' => 23]]],
            ['label' => 'Prestation', 'options' => [['l' => 'Correction orthotypo', 'n' => 240], ['l' => 'Préparation de copie', 'n' => 87], ['l' => 'Réécriture', 'n' => 54], ['l' => 'Relecture sur épreuves', 'n' => 31]]],
            ['label' => 'Délai', 'options' => [['l' => 'Moins d\'une semaine', 'n' => 96], ['l' => '1 à 3 semaines', 'n' => 210], ['l' => 'Plus d\'un mois', 'n' => 106]]],
            ['label' => 'Niveau du prestataire', 'options' => [['l' => 'Experte / Expert', 'n' => 62], ['l' => 'Confirmé', 'n' => 188], ['l' => 'Nouveau', 'n' => 162]]],
            ['label' => 'Confiance', 'options' => [['l' => 'Profil vérifié', 'n' => 301], ['l' => 'Avis 4,5 et plus', 'n' => 264], ['l' => 'Répond en 24 h', 'n' => 189]]],
        ];
    }

    private static function pagination(): array
    {
        $out = [];
        foreach ([1, 2, 3, 4, '…', 14] as $i => $n) {
            $out[] = ['n' => $n, 'style' => 'font-family: \'Space Grotesk\', monospace; font-size: 14px; padding: 8px 13px; border-radius: 8px; cursor: pointer; ' . ($i === 0 ? 'background: #022746; color: #FFF;' : 'color: #4A5A6B; border: 1px solid #E1E7ED;')];
        }
        return $out;
    }

    private static function gallery(): array
    {
        $out = [];
        foreach ([0, 5, 4, 2] as $i => $p) {
            $out[] = [
                'img' => photo($p),
                'slotId' => 'gal-' . $i,
                'style' => 'width: 108px; height: 72px; border-radius: 8px; overflow: hidden; position: relative; cursor: pointer; border: 2px solid ' . ($i === 0 ? self::NAVY : 'transparent') . '; background: #F4F6F9;',
            ];
        }
        return $out;
    }

    private static function formules(int $active): array
    {
        $names = [['Essentielle', '420 €'], ['Standard', '780 €'], ['Complète', '1 250 €']];
        $out = [];
        foreach ($names as $i => [$name, $price]) {
            $on = $i === $active;
            $out[] = [
                'name' => $name,
                'price' => $price,
                'headStyle' => 'padding: 16px 18px; cursor: pointer; text-align: center; background: ' . ($on ? '#F7F9FC' : '#FFF') . '; color: #022746; box-shadow: ' . ($on ? 'inset 0 -3px 0 #D85D3F' : 'none') . '; border-left: 1px solid #F2F5F8;',
            ];
        }
        return $out;
    }

    private static function formuleTabs(int $active): array
    {
        $out = [];
        foreach (['Essentielle', 'Standard', 'Complète'] as $i => $name) {
            $on = $i === $active;
            $out[] = ['name' => $name, 'style' => 'flex: 1; text-align: center; padding: 14px 8px; font-size: 14px; cursor: pointer; color: ' . ($on ? self::NAVY : '#8496A8') . '; background: ' . ($on ? '#FFF' : '#FBFCFE') . '; box-shadow: ' . ($on ? 'inset 0 -3px 0 #D85D3F' : 'none') . ';'];
        }
        return $out;
    }

    private static function compare(int $active): array
    {
        $rows = [
            ['Correction orthotypographique', ['✓', '✓', '✓']],
            ['Harmonisation des noms et des temps', ['✓', '✓', '✓']],
            ['Rapport de lecture', ['—', '3 à 5 pages', 'détaillé']],
            ['Aller-retours', ['—', '1', '2']],
            ['Appel de restitution', ['—', '—', '1 h']],
            ['Délai', ['8 jours', '3 semaines', '5 semaines']],
        ];
        $out = [];
        foreach ($rows as [$label, $cells]) {
            $mapped = [];
            foreach ($cells as $i => $txt) {
                $mapped[] = ['txt' => $txt, 'style' => 'padding: 13px 18px; text-align: center; font-size: 14px; border-left: 1px solid #F2F5F8; color: ' . ($txt === '—' ? '#C3CEDA' : '#14202C') . '; background: ' . ($i === $active ? '#FBFCFE' : '#FFF') . ';'];
            }
            $out[] = ['label' => $label, 'cells' => $mapped];
        }
        return $out;
    }

    private static function options(): array
    {
        $items = [
            ['Livraison accélérée (−5 jours)', 120],
            ['Feuille de style typographique', 60],
            ['Passe supplémentaire sur épreuves', 180],
            ['Note de confidentialité dans le devis', 0],
        ];
        $out = [];
        foreach ($items as [$label, $price]) {
            $out[] = [
                'label' => $label,
                'price' => $price,
                'check' => '',
                'row' => 'display: flex; align-items: center; gap: 10px; padding: 11px 12px; border: 1px solid #E8ECF1; border-radius: 9px; cursor: pointer; background: #FFF;',
                'box' => 'width: 18px; height: 18px; min-width: 18px; border-radius: 4px; border: 1.5px solid #C3CEDA; background: #FFF; color: #FFF; font-size: 11px; display: flex; align-items: center; justify-content: center;',
            ];
        }
        return $out;
    }

    private static function faq(): array
    {
        $items = [
            ['Sur quels formats travaillez-vous ?', 'Word (.docx), LibreOffice (.odt) et InDesign. En InDesign, je corrige directement dans la maquette et vous livre le fichier plus un PDF annoté.'],
            ['Que se passe-t-il si mon texte dépasse 90 000 signes ?', 'Le tarif est ajusté au prorata, 4,50 € pour 1 000 signes supplémentaires. Envoyez-moi le manuscrit, je vous confirme le montant exact avant la commande.'],
            ['Corrigez-vous le fond du texte ?', 'Le rapport de lecture pointe les incohérences, les longueurs et les répétitions, mais je ne réécris pas sans votre accord. Pour une réécriture, prenez la formule Complète ou une prestation dédiée.'],
            ['Comment se passe la confidentialité ?', 'Aucun extrait n\'est publié dans un portfolio sans autorisation écrite. La plateforme n\'impose pas de NDA : le prestataire s\'y engage par la charte, et le devis peut le rappeler.'],
        ];
        return self::faqItems($items);
    }

    private static function aideFaq(): array
    {
        $items = [
            ['Quand la commission est-elle facturée ?', 'Lorsque le client confirme que la mission est finalisée et note la prestation (qualité, efficacité, satisfaction). La facture est alors émise au prestataire — c\'est son dernier jalon — payable sous 15 jours.'],
            ['Comment se règlent les missions ?', 'Client et prestataire se règlent entre eux (virement, chèque, facture entreprise…). La plateforme n\'encaisse rien. Chaque étape se confirme dans le suivi : devis, factures, déclarations de règlement, livraison, validation.'],
            ['Puis-je annuler une commande ?', 'Vous pouvez refuser un devis : la commande reste ouverte et le prestataire peut en proposer un autre. Annuler la commande clôture définitivement le dossier, gratuitement tant que le devis n\'est pas accepté. Après acceptation, l\'annulation se négocie dans la messagerie ; à défaut d\'accord, la médiation interne reprend le dossier.'],
            ['Qui facture le client final ?', 'Le prestataire facture directement son client, hors plateforme. La plateforme facture uniquement sa commission au prestataire, à la validation de la mission.'],
            ['La première mission est-elle payante ?', 'Non. La première mission réalisée via la plateforme est entièrement gratuite. À partir de la deuxième, la commission est de 8 % sur le montant hors taxes (hors TVA).'],
            ['La commission est-elle calculée TTC ?', 'Non : elle est calculée sur le montant hors taxes (hors TVA) de la mission. Si le prestataire est en franchise en base, le prix facturé au client vaut montant HT.'],
            ['La plateforme prend-elle une commission sur les appels d\'offres ?', 'Publier et candidater sont gratuits, sans abonnement. La commission de 8 % s\'applique uniquement à partir de la deuxième mission réalisée, sur le montant hors taxes.'],
            ['L\'IA générative est-elle autorisée sur la plateforme ?', 'Non, pour les missions entre acteurs du livre : aucun livrable ne peut être produit par une IA générative. Le moratoire ne s\'applique pas à la fabrication de la plateforme. Le détail figure dans les règles IA.'],
            ['Y a-t-il un contrat type ?', 'Non. L\'acceptation du devis dans le suivi vaut accord entre les parties. La plateforme ne génère ni contrat type ni NDA.'],
        ];
        return self::faqItems($items);
    }

    /** @param list<array{0: string, 1: string}> $items */
    private static function faqItems(array $items): array
    {
        $out = [];
        foreach ($items as $i => [$q, $a]) {
            $open = $i === 0;
            $out[] = [
                'q' => $q,
                'a' => $a,
                'open' => $open,
                'sign' => $open ? '−' : '+',
                'panelAttr' => $open ? '' : 'hidden',
                'expanded' => $open ? 'true' : 'false',
            ];
        }
        return $out;
    }

    private static function distribution(): array
    {
        $rows = [[5, 74], [4, 9], [3, 3], [2, 1], [1, 0]];
        $out = [];
        foreach ($rows as [$star, $n]) {
            $out[] = ['star' => $star, 'n' => $n, 'bar' => 'height: 100%; width: ' . (int) round($n / 87 * 100) . '%; background: #D85D3F;'];
        }
        return $out;
    }

    private static function avisList(): array
    {
        return [
            ['who' => 'Éditions La Ligne', 'initials' => 'EL', 'note' => '5,0', 'txt' => 'Travail d\'une précision rare, et un rapport de lecture qui nous a fait retravailler deux chapitres. On rappellera.', 'when' => 'juillet 2026', 'what' => 'Correction complète, 92 000 signes', 'avatar' => avatar_style('EL', 28)],
            ['who' => 'Camille D., autrice', 'initials' => 'CD', 'note' => '5,0', 'txt' => 'Marion a relevé des incohérences de chronologie que personne n\'avait vues. Échanges clairs, délais tenus.', 'when' => 'juin 2026', 'what' => 'Correction + rapport de lecture', 'avatar' => avatar_style('CD', 28)],
            ['who' => 'Collectif Encre Vive', 'initials' => 'CE', 'note' => '4,5', 'txt' => 'Très bonne prestation. Un léger retard sur la première passe, largement rattrapé ensuite.', 'when' => 'mai 2026', 'what' => 'Préparation de copie', 'avatar' => avatar_style('CE', 28)],
        ];
    }

    private static function notifications(): array
    {
        $items = [
            ['Nouveau devis reçu de Marion Vasseur', 'Correction complète + rapport de lecture — 780 €, valable 5 jours.', '10:24', true, '/espace/messages'],
            ['Livraison à valider', 'Commande nº 2481-03 : première passe livrée, 2 fichiers.', 'hier', true, '/espace/suivi'],
            ['Votre mission a reçu 3 nouvelles candidatures', 'Essai historique, 240 pages — 7 candidatures au total.', 'hier', true, '/espace/missions'],
            ['Versement effectué', '718 € virés sur votre compte pour la commande nº 2455-04.', '3 sept.', false, '/espace/facturation'],
            ['Avis publié', 'Éditions La Ligne vous a attribué 5 étoiles.', '1er sept.', false, '/prestataires/marion-vasseur'],
            ['Première mission offerte', 'Aucune commission sur votre première mission réalisée. Ensuite, 8 %.', '28 août', false, '/espace'],
        ];
        $out = [];
        foreach ($items as [$title, $body, $when, $unread, $href]) {
            $out[] = [
                'title' => $title,
                'body' => $body,
                'when' => $when,
                'href' => $href,
                'row' => 'display: flex; gap: 14px; align-items: flex-start; padding: 16px 18px; border-bottom: 1px solid #F2F5F8; cursor: pointer; background: ' . ($unread ? '#FBFCFE' : '#FFF') . ';',
                'dot' => 'width: 8px; height: 8px; min-width: 8px; border-radius: 50%; margin-top: 6px; background: ' . ($unread ? self::ORANGE : '#DCE3EA') . ';',
            ];
        }
        return $out;
    }

    private static function mesPrestations(): array
    {
        $items = [
            ['Correction complète d\'un roman jusqu\'à 90 000 signes', '742', '38', '12', '420 €', 'En ligne', 'navy'],
            ['Préparation de copie avant maquette', '418', '21', '7', '340 €', 'En ligne', 'navy'],
            ['Relecture typographique sur épreuves PDF', '356', '17', '5', '260 €', 'En ligne', 'navy'],
            ['Relecture jeunesse, album et premières lectures', '324', '12', '3', '190 €', 'En ligne', 'navy'],
            ['Atelier d\'écriture — accompagnement 3 mois', '—', '—', '—', '1 400 €', 'Brouillon', 'grey'],
        ];
        $out = [];
        foreach ($items as [$title, $vues, $contacts, $commandes, $prix, $status, $tone]) {
            $out[] = [
                'title' => $title, 'vues' => $vues, 'contacts' => $contacts, 'commandes' => $commandes, 'prix' => $prix, 'status' => $status,
                'statusStyle' => 'font-size: 12px; padding: 5px 10px; border-radius: 999px; font-family: \'Space Grotesk\', monospace; ' . ($tone === 'navy' ? 'background: #EEF3F8; color: #022746;' : 'background: #F4F6F9; color: #66768A;'),
            ];
        }
        return $out;
    }

    private static function mesMissions(): array
    {
        $items = [
            ['Recherche correcteur pour essai historique, 240 pages', 'Correction', 'il y a 2 j', '12 sept.', '600 – 900 €', 7, 'Ouverte', 'orange', ['PF', 'AV', 'NC', 'MV']],
            ['Maquette intérieure, collection Grands Formats', 'Maquette', 'il y a 6 j', '28 sept.', '1 200 €', 11, 'Ouverte', 'orange', ['SG', 'AK', 'IB']],
            ['Traduction ES→FR d\'un recueil de nouvelles', 'Traduction', 'il y a 3 sem.', 'attribuée', '3 200 €', 4, 'Attribuée', 'grey', ['SR']],
        ];
        $out = [];
        foreach ($items as [$title, $cat, $when, $deadline, $budget, $n, $status, $tone, $who]) {
            $avatars = [];
            foreach ($who as $w) {
                $avatars[] = ['initials' => $w, 'style' => avatar_style($w, 30)];
            }
            $out[] = [
                'title' => $title, 'cat' => $cat, 'when' => $when, 'deadline' => $deadline, 'budget' => $budget, 'n' => $n, 'status' => $status,
                'statusStyle' => 'font-size: 12px; padding: 5px 10px; border-radius: 999px; font-family: \'Space Grotesk\', monospace; ' . ($tone === 'orange' ? 'background: #FDF3F0; color: #D85D3F;' : 'background: #F4F6F9; color: #66768A;'),
                'avatars' => $avatars,
            ];
        }
        return $out;
    }

    private static function mesCandidatures(): array
    {
        $items = [
            ['Essai historique, 240 pages — correction complète', 'Fleuve Noirci', '780 €', 'il y a 2 h', 'Devis envoyé', 'orange'],
            ['Polar, 380 000 signes — préparation de copie', 'Éditions Pampa', '750 €', 'hier', 'Vue', 'navy'],
            ['Recueil jeunesse — relecture sur épreuves', 'Camille D.', '260 €', '3 sept.', 'Acceptée', 'green'],
            ['Mémoire d\'entreprise, 120 pages', 'Atelier Mémoires', '480 €', '28 août', 'Non retenue', 'grey'],
            ['Guide pratique, 180 pages — correction', 'Éditions Cardan', '590 €', '22 août', 'Non retenue', 'grey'],
        ];
        $out = [];
        foreach ($items as [$title, $by, $price, $when, $status, $tone]) {
            $style = match ($tone) {
                'orange' => 'background: #FDF3F0; color: #D85D3F;',
                'navy' => 'background: #EEF3F8; color: #022746;',
                'green' => 'background: #ECF5EF; color: #2E6B45;',
                default => 'background: #F4F6F9; color: #66768A;',
            };
            $out[] = [
                'title' => $title, 'by' => $by, 'price' => $price, 'when' => $when, 'status' => $status,
                'statusStyle' => 'font-size: 12px; padding: 5px 10px; border-radius: 999px; font-family: \'Space Grotesk\', monospace; ' . $style,
            ];
        }
        return $out;
    }

    private static function avisCriteres(): array
    {
        $labels = ['Qualité de la prestation', 'Efficacité', 'Satisfaction globale'];
        $notes = [5, 4, 5];
        $out = [];
        foreach ($labels as $i => $label) {
            $stars = [];
            for ($n = 1; $n <= 5; $n++) {
                $stars[] = ['style' => 'font-size: 24px; cursor: pointer; color: ' . ($n <= $notes[$i] ? self::ORANGE : '#DCE3EA') . ';'];
            }
            $out[] = ['label' => $label, 'note' => $notes[$i] . ' / 5', 'stars' => $stars];
        }
        return $out;
    }

    private static function toggles(): array
    {
        $items = [
            ['Disponible pour de nouveaux appels d\'offres', 'Votre vitrine affiche « disponible dès maintenant ».', true],
            ['Alertes de nouvelles recherches', 'Un e-mail dès qu\'une recherche correspond à vos métiers.', true],
            ['Résumé hebdomadaire', 'Vues, contacts et conversions de vos prestations.', false],
            ['Afficher mon portfolio publiquement', 'Uniquement les projets dont vous avez l\'autorisation.', true],
        ];
        $out = [];
        foreach ($items as [$label, $note, $on]) {
            $out[] = [
                'label' => $label,
                'note' => $note,
                'track' => 'width: 44px; height: 24px; min-width: 44px; border-radius: 999px; background: ' . ($on ? self::ORANGE : '#DCE3EA') . '; display: flex; align-items: center; padding: 3px; box-sizing: border-box; justify-content: ' . ($on ? 'flex-end' : 'flex-start') . ';',
                'knob' => 'width: 18px; height: 18px; border-radius: 50%; background: #FFF; display: block;',
            ];
        }
        return $out;
    }

    private static function operations(): array
    {
        $items = [
            ['24 sept.', 'Versement — commande nº 2455-04', '+ 718 €', 'Facture', true],
            ['22 sept.', 'Commission plateforme, 8 %', '− 62 €', 'Relevé', false],
            ['18 sept.', 'Facture émise — commande nº 2481-03', '780 €', 'Contrat', null],
            ['5 sept.', 'Versement — commande nº 2441-01', '+ 2 944 €', 'Facture', true],
            ['1er sept.', 'Relevé de commission août', '− 214 €', 'Relevé', false],
            ['28 août', 'Versement — commande nº 2429-02', '+ 598 €', 'Facture', true],
        ];
        $out = [];
        foreach ($items as [$date, $label, $amount, $doc, $pos]) {
            $color = $pos === true ? '#2E6B45' : ($pos === false ? self::ORANGE : '#022746');
            $out[] = ['date' => $date, 'label' => $label, 'amount' => $amount, 'doc' => $doc, 'amountStyle' => 'font-family: \'Space Grotesk\', monospace; color: ' . $color . ';'];
        }
        return $out;
    }

    private static function competences(): array
    {
        $items = [
            ['Correction orthotypographique', 'Experte', 100],
            ['Préparation de copie', 'Experte', 100],
            ['Réécriture et lissage', 'Confirmée', 75],
            ['Relecture sur épreuves', 'Experte', 100],
            ['Norme bibliographique', 'Confirmée', 75],
            ['Rédaction de notes critiques', 'Initiée', 45],
        ];
        $out = [];
        foreach ($items as [$label, $niveau, $pct]) {
            $out[] = ['label' => $label, 'niveau' => $niveau, 'pct' => $pct, 'bar' => 'height: 100%; width: ' . $pct . '%; background: #D85D3F;'];
        }
        return $out;
    }

    private static function portfolioEdit(): array
    {
        $captions = ['Roman — Éditions La Ligne, 2026', 'Essai historique, 240 pages', 'Collection jeunesse — 6 titres', 'Livre audio — notes de narration', 'Album illustré, coédition', 'Recueil de nouvelles traduit'];
        $verifs = [true, true, false, true, false, true];
        $photos = [0, 5, 4, 2, 1, 3];
        $out = [];
        foreach ($captions as $i => $caption) {
            $out[] = [
                'caption' => $caption,
                'img' => photo($photos[$i]),
                'slotId' => 'pf-edit-' . $i,
                'badge' => $verifs[$i] ? 'Mission vérifiée' : 'Ajout manuel',
                'badgeStyle' => 'font-family: \'Space Grotesk\', monospace; font-size: 11px; letter-spacing: .06em; text-transform: uppercase; padding: 4px 8px; border-radius: 4px; ' . ($verifs[$i] ? 'background: #FDF3F0; color: #D85D3F;' : 'background: #F4F6F9; color: #8496A8;'),
            ];
        }
        return $out;
    }

    private static function historique(): array
    {
        return [
            ['date' => '24 sept.', 'title' => 'Maquette — préparation de copie', 'detail' => '240 pages · formule Standard', 'client' => 'Studio Grain', 'montant' => '650 €', 'avis' => '★ 5,0'],
            ['date' => '12 sept.', 'title' => 'Correction complète — roman', 'detail' => '92 000 signes · 1 aller-retour', 'client' => 'Éditions La Ligne', 'montant' => '780 €', 'avis' => '★ 5,0'],
            ['date' => '28 août', 'title' => 'Relecture sur épreuves', 'detail' => 'PDF maquetté, 180 pages', 'client' => 'Camille D.', 'montant' => '260 €', 'avis' => '★ 4,5'],
            ['date' => '14 août', 'title' => 'Correction — essai', 'detail' => '400 000 signes, appareil de notes', 'client' => 'Éditions Pampa', 'montant' => '1 100 €', 'avis' => '★ 5,0'],
            ['date' => '2 août', 'title' => 'Préparation de copie', 'detail' => 'Collection jeunesse, 3 titres', 'client' => 'Collectif Encre Vive', 'montant' => '820 €', 'avis' => '★ 4,5'],
            ['date' => '18 juil.', 'title' => 'Réécriture, forfait 50 pages', 'detail' => 'Lissage stylistique', 'client' => 'Nora B.', 'montant' => '580 €', 'avis' => '★ 5,0'],
            ['date' => '5 juil.', 'title' => 'Correction complète — polar', 'detail' => '380 000 signes', 'client' => 'Éditions du Chardon', 'montant' => '940 €', 'avis' => '★ 5,0'],
        ];
    }

    private static function avisVitrine(): array
    {
        $items = [
            ['Éditions La Ligne', 'EL', '5,0', 'Correction complète, 92 000 signes', '12 septembre 2026', 'Travail d\'une précision rare, et un rapport de lecture qui nous a fait retravailler deux chapitres.', 'Répondre'],
            ['Camille D., autrice', 'CD', '4,5', 'Relecture sur épreuves', '28 août 2026', 'Très bonne prestation, un léger retard sur la première passe largement rattrapé ensuite.', 'Répondre'],
            ['Éditions Pampa', 'EP', '5,0', 'Correction d\'un essai', '14 août 2026', 'L\'appareil de notes a été traité avec un soin que nous n\'avions pas vu depuis longtemps.', 'Répondre'],
            ['Collectif Encre Vive', 'CE', '4,5', 'Préparation de copie', '2 août 2026', 'Méthodique et disponible. La feuille de style nous ressert sur tous nos titres.', 'Répondre'],
        ];
        $out = [];
        foreach ($items as [$who, $initials, $note, $what, $when, $txt, $action]) {
            $out[] = ['who' => $who, 'initials' => $initials, 'note' => $note, 'what' => $what, 'when' => $when, 'txt' => $txt, 'action' => $action, 'avatar' => avatar_style($initials, 34)];
        }
        return $out;
    }

    private static function iaEngagements(): array
    {
        $labels = [
            'Je n\'utiliserai aucune IA générative pour produire les textes, illustrations, traductions ou voix que je livre.',
            'Les contenus de ma vitrine — présentation, descriptions, portfolio — sont écrits et réalisés par moi.',
            'J\'accepte le retrait de mon profil et le remboursement de la mission en cas de manquement constaté.',
        ];
        $ok = [true, true, false];
        $out = [];
        foreach ($labels as $i => $label) {
            $on = $ok[$i];
            $out[] = [
                'label' => $label,
                'check' => $on ? '✓' : '',
                'box' => 'width: 18px; height: 18px; min-width: 18px; border-radius: 4px; margin-top: 1px; display: flex; align-items: center; justify-content: center; font-size: 11px; color: #FFF; border: 1.5px solid ' . ($on ? self::ORANGE : '#C9A79A') . '; background: ' . ($on ? self::ORANGE : '#FFF') . ';',
            ];
        }
        return $out;
    }

    private static function missionsList(): array
    {
        $items = [
            ['Recherche correcteur pour essai historique, 240 pages', 'Éditions du Fleuve Noirci', 'Correction', 'il y a 2 j', '600 – 900 €', '12 sept.', 7, 'Urgent', ['Essai', 'Notes de bas de page', 'Norme maison']],
            ['Illustrateur album jeunesse 3-6 ans, 24 pages', 'Camille D., autrice', 'Illustration', 'il y a 3 j', '1 800 – 2 500 €', '18 sept.', 14, '', ['Album', 'Couleur', 'Cession 5 ans']],
            ['Traduction ES→FR d\'un recueil de nouvelles', 'Éditions Pampa', 'Traduction', 'il y a 4 j', '3 200 €', '30 sept.', 4, '', ['Littérature', '180 000 signes']],
            ['Impression 500 ex. broché, papier recyclé', 'Collectif Encre Vive', 'Impression', 'il y a 5 j', '1 400 – 2 000 €', '5 oct.', 9, 'Éco-labels', ['Offset', 'Dos carré collé']],
            ['Attaché de presse, premier roman, sortie janvier', 'Éditions La Ligne', 'Presse & com', 'il y a 6 j', '2 000 – 3 000 €', '22 sept.', 9, '', ['Presse écrite', 'Podcasts', 'Salons']],
            ['Narrateur pour livre audio, 7 h de texte', 'Studio Bel Écho', 'Audio', 'il y a 1 sem.', '2 400 €', '12 oct.', 6, '', ['Voix féminine', 'Studio fourni']],
        ];
        $out = [];
        foreach ($items as [$title, $by, $cat, $when, $budget, $deadline, $applicants, $tag, $tags]) {
            $out[] = [
                'title' => $title, 'by' => $by, 'cat' => $cat, 'when' => $when, 'budget' => $budget, 'deadline' => $deadline, 'applicants' => $applicants, 'tag' => $tag, 'tags' => $tags,
                'tagStyle' => $tag !== '' ? 'background: #FDF3F0; color: #D85D3F; font-size: 11px; padding: 4px 8px; border-radius: 4px; font-family: \'Space Grotesk\', monospace; text-transform: uppercase; letter-spacing: .06em;' : 'display: none;',
                'href' => '/missions/' . slugify($title),
            ];
        }
        return $out;
    }

    private static function suiviSteps(): array
    {
        $items = [
            ['Devis accepté', 'Contrat de prestation signé par les deux parties.', '8 sept.'],
            ['Mission démarrée', 'Manuscrit transmis, accusé de réception de Marion.', '8 sept.'],
            ['Première passe livrée', '1 240 corrections, rapport de lecture joint.', '22 sept.'],
            ['Validation de la livraison', 'À vous de jouer : validez ou demandez une modification.', 'en attente'],
            ['Versement au prestataire', 'Sous 5 jours ouvrés après validation.', '—'],
        ];
        $out = [];
        foreach ($items as $i => [$label, $note, $when]) {
            $done = $i < 3;
            $out[] = [
                'label' => $label, 'note' => $note, 'when' => $when, 'mark' => $done ? '✓' : '',
                'dot' => 'width: 22px; height: 22px; min-width: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; color: #FFF; background: ' . ($done ? self::ORANGE : '#FFF') . '; border: ' . ($done ? '0' : '2px solid #C3CEDA') . ';',
                'line' => $i < 4 ? 'width: 2px; flex: 1; min-height: 30px; background: #E8ECF1; display: block;' : 'display: none;',
            ];
        }
        return $out;
    }

    private static function commandes(): array
    {
        $items = [
            ['Correction complète — essai historique', '2481-03', 'Marion Vasseur', 'MV', '780 €', '22 sept.', 'À valider', 'orange'],
            ['Couverture illustrée + déclinaisons', '2477-01', 'Atelier Kess', 'AK', '480 €', '18 sept.', 'En cours', 'navy'],
            ['300 ex. broché, papier bouffant', '2469-02', 'Imprimerie Baudry', 'IB', '1 190 €', '5 oct.', 'En cours', 'navy'],
            ['Maquette intérieure, 240 pages', '2455-04', 'Studio Grain', 'SG', '650 €', 'livré', 'Livrée', 'grey'],
            ['Traduction ES→FR, recueil', '2441-01', 'Sofia Renard', 'SR', '3 200 €', 'livré', 'Livrée', 'grey'],
        ];
        $out = [];
        foreach ($items as [$title, $num, $by, $initials, $amount, $due, $status, $tone]) {
            $style = match ($tone) {
                'orange' => 'background: #FDF3F0; color: #D85D3F;',
                'navy' => 'background: #EEF3F8; color: #022746;',
                default => 'background: #F4F6F9; color: #66768A;',
            };
            $out[] = [
                'title' => $title, 'num' => $num, 'by' => $by, 'initials' => $initials, 'amount' => $amount, 'due' => $due, 'status' => $status,
                'avatar' => avatar_style($initials, 24),
                'statusStyle' => 'font-size: 12px; padding: 5px 10px; border-radius: 999px; font-family: \'Space Grotesk\', monospace; ' . $style,
            ];
        }
        return $out;
    }

    private static function niveaux(): array
    {
        return [
            ['kicker' => 'Pour commencer', 'name' => 'Première mission', 'pct' => '0 %', 'items' => ['Aucun abonnement', 'Compte, vitrine et fiches libres', 'Candidatures et devis gratuits', 'Commission plateforme offerte'], 'card' => 'border-radius: 14px; padding: 26px; background: #022746; color: #E4EDF5; border: 1px solid #022746;', 'kickerColor' => '#E8845F'],
            ['kicker' => 'Ensuite', 'name' => 'À partir de la 2ᵉ', 'pct' => '8 %', 'items' => ['Calculée sur le montant hors taxes (hors TVA)', 'Uniquement sur les missions réalisées', 'Le client règle le prestataire hors plateforme', 'Facturée au prestataire à la validation (dernier jalon)', '15 jours pour régler, sinon les offres sont suspendues'], 'card' => 'border-radius: 14px; padding: 26px; background: #FFF; color: #022746; border: 1px solid #E8ECF1;', 'kickerColor' => self::ORANGE],
        ];
    }

    private static function paiements(): array
    {
        $items = [
            ['Règlement hors plateforme', 'Virement, chèque ou facture — entre vous et le prestataire'],
            ['Acompte si prévu au devis', 'Déclaré dans le suivi, jamais encaissé ici'],
            ['Commission prestataire', 'Dernier jalon, après votre validation — 0 % puis 8 %'],
        ];
        $out = [];
        foreach ($items as $i => [$label, $note]) {
            $on = $i === 0;
            $out[] = [
                'label' => $label, 'note' => $note,
                'row' => 'display: flex; gap: 12px; align-items: flex-start; padding: 14px; border: 1px solid ' . ($on ? self::NAVY : '#E8ECF1') . '; border-radius: 10px; cursor: pointer; background: ' . ($on ? '#FBFCFE' : '#FFF') . ';',
                'dot' => 'width: 16px; height: 16px; min-width: 16px; border-radius: 50%; margin-top: 2px; border: 1.5px solid ' . ($on ? self::NAVY : '#C3CEDA') . '; box-shadow: ' . ($on ? 'inset 0 0 0 3px #FFF, inset 0 0 0 8px #022746' : 'none') . ';',
            ];
        }
        return $out;
    }

    private static function portfolio(): array
    {
        $caps = ['Roman, Éditions La Ligne', 'Essai, 240 pages', 'Collection jeunesse', 'Livre audio, notes de narration', 'Album illustré', 'Recueil de nouvelles'];
        $photos = [0, 5, 4, 2, 1, 3];
        $out = [];
        foreach ($caps as $i => $caption) {
            $out[] = ['img' => photo($photos[$i]), 'slotId' => 'port-' . $i, 'caption' => $caption];
        }
        return $out;
    }

    private static function threads(): array
    {
        $items = [
            ['Marion Vasseur', 'MV', 'Je vous envoie le devis ce soir.', '10:24', 'Devis reçu', 'Correction — essai historique, 240 pages'],
            ['Atelier Kess', 'AK', 'Deux pistes de couverture en pièce jointe.', 'hier', 'En cours', 'Illustration — couverture, album jeunesse'],
            ['Imprimerie Baudry', 'IB', 'Bon à tirer validé, lancement lundi.', 'hier', 'Livraison prévue', 'Impression — 300 ex. broché'],
            ['Sofia Renard', 'SR', 'Disponible à partir du 15 octobre.', 'lun.', '', 'Traduction — recueil de nouvelles'],
            ['Hélène Aubry', 'HA', 'La liste des libraires est prête.', '3 sept.', '', 'Presse & com — lancement janvier'],
        ];
        $out = [];
        foreach ($items as $i => [$who, $initials, $last, $when, $tag, $sub]) {
            $on = $i === 0;
            $out[] = [
                'who' => $who, 'initials' => $initials, 'last' => $last, 'when' => $when, 'tag' => $tag, 'sub' => $sub,
                'avatar' => avatar_style($initials, 38),
                'style' => 'padding: 14px 18px; border-bottom: 1px solid #F2F5F8; cursor: pointer; background: ' . ($on ? '#F7F9FC' : '#FFF') . '; box-shadow: ' . ($on ? 'inset 3px 0 0 #D85D3F' : 'none') . ';',
                'tagStyle' => $tag !== '' ? 'display: inline-block; margin-top: 8px; background: #FDF3F0; color: #D85D3F; font-size: 11px; padding: 3px 8px; border-radius: 999px;' : 'display: none;',
            ];
        }
        return $out;
    }

    private static function messages(): array
    {
        $items = [
            [false, 'Bonjour, votre essai m\'intéresse. Le texte est-il déjà passé par une préparation de copie ?'],
            [true, 'Bonjour Marion. Non, le manuscrit sort de la relecture de l\'auteur. 240 pages, environ 420 000 signes.'],
            [false, 'Parfait. Je propose une correction complète avec rapport de lecture, sur trois semaines.'],
            [true, 'Ça nous convient. Pouvez-vous chiffrer avec un aller-retour inclus ?'],
        ];
        $out = [];
        foreach ($items as [$me, $txt]) {
            $out[] = [
                'txt' => $txt,
                'row' => 'display: flex; justify-content: ' . ($me ? 'flex-end' : 'flex-start') . ';',
                'bubble' => 'max-width: 470px; padding: 13px 16px; border-radius: 14px; font-size: 15px; line-height: 1.55; ' . ($me ? 'background: #022746; color: #FFF;' : 'background: #FFF; border: 1px solid #E8ECF1; color: #14202C;'),
            ];
        }
        return $out;
    }

    private static function chart(): array
    {
        $rows = [['jan', 42], ['fev', 55], ['mar', 38], ['avr', 71], ['mai', 64], ['juin', 88], ['juil', 96], ['aout', 78]];
        $out = [];
        foreach ($rows as [$m, $h]) {
            $out[] = ['m' => $m, 'bar' => 'width: 100%; height: ' . $h . '%; background: ' . ($h > 90 ? self::ORANGE : '#C9D8E6') . '; border-radius: 4px 4px 0 0;'];
        }
        return $out;
    }

    private static function topbarStats(): string
    {
        try {
            $pros = User::countOfferers();
            $commission = \Adl\Models\Setting::get('commission_percent', '8') ?: '8';
            $label = $pros > 1 ? 'professionnels du livre' : 'professionnel du livre';
            return '1ʳᵉ mission offerte · puis ' . $commission . ' % · ' . format_int($pros) . ' ' . $label;
        } catch (\Throwable) {
            return '1ʳᵉ mission offerte · puis 8 % · sans abonnement';
        }
    }

    private static function liveUnreadMessages(?array $user): int
    {
        if (!$user) {
            return 0;
        }
        try {
            return \Adl\Models\Conversation::unreadCount((int) $user['id']);
        } catch (\Throwable) {
            return 0;
        }
    }

    private static function liveUnreadAlerts(?array $user): int
    {
        if (!$user) {
            return 0;
        }
        try {
            return Notification::unreadCount((int) $user['id']);
        } catch (\Throwable) {
            return 0;
        }
    }

    private static function isEspaceScreen(string $screen): bool
    {
        return in_array($screen, [
            'dashboard', 'publier', 'commande', 'suivi', 'suivi-detail', 'commandes', 'mesmissions',
            'candidatures', 'mesprestations', 'creer', 'messagerie', 'notifications',
            'favoris', 'avis', 'vitrine', 'parametres', 'facturation',
        ], true);
    }

    private static function headerCta(bool $seeks, bool $offers): ?array
    {
        if ($seeks) {
            return ['label' => 'Publier une recherche', 'href' => '/espace/publier'];
        }
        if ($offers) {
            return ['label' => 'Proposer une prestation', 'href' => '/espace/prestations/creer'];
        }
        return null;
    }

    /** @return array<string, string> */
    private static function espaceNavBadges(?array $user, int $unreadMessages, int $unreadAlerts, bool $seeks, bool $offers): array
    {
        $badges = [
            'messagerie' => self::navBadge($unreadMessages),
            'notifications' => self::navBadge($unreadAlerts),
        ];
        if (!$user) {
            return $badges;
        }
        $userId = (int) $user['id'];
        if ($seeks || $offers) {
            $badges['suivi'] = self::safeNavBadge(static fn (): int => OrderMilestone::countDueActions($userId));
        }
        if ($seeks) {
            $badges['mesmissions'] = self::safeNavBadge(static fn (): int => Application::countUnreviewedForOwner($userId));
        }
        if ($offers) {
            $badges['facturation'] = self::safeNavBadge(static fn (): int => Invoice::countOpenForSeller($userId));
        }

        return $badges;
    }

    private static function safeNavBadge(callable $count): string
    {
        try {
            return self::navBadge((int) $count());
        } catch (\Throwable) {
            return '';
        }
    }

    private static function navBadge(int $n): string
    {
        return $n > 0 ? (string) $n : '';
    }

    /** @param array<string, string> $badges */
    private static function espaceNav(string $screen, bool $seeks, bool $offers, array $badges = []): array
    {
        $item = static function (string $label, string $href, string $key, string $icon = 'dot') use ($screen, $badges): array {
            $aliases = ['suivi' => ['suivi', 'suivi-detail']];
            $active = $screen === $key || in_array($screen, $aliases[$key] ?? [], true);
            $badge = $badges[$key] ?? '';
            $badgeAria = match ($key) {
                'messagerie' => $badge . ' non lus',
                'notifications' => $badge . ' nouvelles',
                default => $badge . ' en attente',
            };
            return [
                'label' => $label,
                'href' => $href,
                'active' => $active,
                'icon' => $icon,
                'badge' => $badge,
                'badge_aria' => $badge !== '' ? $badgeAria : '',
            ];
        };

        $groups = [[
            'title' => 'Espace',
            'items' => [$item('Tableau de bord', '/espace', 'dashboard', 'home')],
        ]];

        if ($seeks) {
            $groups[] = [
                'title' => 'Recherche',
                'items' => [
                    $item('Annuaire', '/recherche', '', 'search'),
                    $item('Publier une recherche', '/espace/publier', 'publier', 'file-plus'),
                    $item('Mes recherches', '/espace/missions', 'mesmissions', 'clipboard'),
                    $item('Mes commandes', '/espace/commandes', 'commandes', 'bag'),
                    $item('Suivi', '/espace/suivi', 'suivi', 'clipboard'),
                    $item('Favoris', '/espace/favoris', 'favoris', 'heart'),
                ],
            ];
        }

        if ($offers) {
            $groups[] = [
                'title' => 'Proposer',
                'items' => [
                    $item('Ma vitrine', '/espace/vitrine', 'vitrine', 'id'),
                    $item('Proposer une prestation', '/espace/prestations/creer', 'creer', 'plus-box'),
                    $item('Mes prestations', '/espace/prestations', 'mesprestations', 'grid'),
                    $item('Appels d\'offres', '/missions', '', 'megaphone'),
                    $item('Mes candidatures', '/espace/candidatures', 'candidatures', 'send'),
                    ...($seeks ? [] : [$item('Suivi', '/espace/suivi', 'suivi', 'clipboard')]),
                    $item('Facturation', '/espace/facturation', 'facturation', 'invoice'),
                ],
            ];
        }

        $groups[] = [
            'title' => 'Compte',
            'items' => [
                $item('Messages', '/espace/messages', 'messagerie', 'mail'),
                $item('Alertes', '/espace/notifications', 'notifications', 'bell'),
                $item('Paramètres', '/espace/parametres', 'parametres', 'gear'),
            ],
        ];

        return $groups;
    }
}
