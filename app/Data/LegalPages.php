<?php

declare(strict_types=1);

namespace Adl\Data;

final class LegalPages
{
    public const UPDATED = '25 août 2026';
    public const VERSION = '1.0';

    public static function slugs(): array
    {
        return [
            'mentions' => ['label' => 'Mentions légales', 'href' => '/mentions-legales', 'title' => 'Mentions légales'],
            'cgu' => ['label' => 'CGU', 'href' => '/cgu', 'title' => 'Conditions générales d\'utilisation'],
            'cgv' => ['label' => 'CGV', 'href' => '/cgv', 'title' => 'Conditions générales de vente'],
            'confidentialite' => ['label' => 'Confidentialité', 'href' => '/confidentialite', 'title' => 'Politique de confidentialité'],
            'cookies' => ['label' => 'Cookies', 'href' => '/cookies', 'title' => 'Politique cookies'],
        ];
    }

    public static function slugFromPath(string $path): string
    {
        return match (rtrim($path, '/')) {
            '/cgu' => 'cgu',
            '/cgv' => 'cgv',
            '/confidentialite' => 'confidentialite',
            '/cookies' => 'cookies',
            default => 'mentions',
        };
    }

    public static function nav(string $active): array
    {
        $out = [];
        foreach (self::slugs() as $slug => $item) {
            $on = $slug === $active;
            $out[] = [
                'label' => $item['label'],
                'href' => $item['href'],
                'active' => $on,
            ];
        }
        return $out;
    }

    public static function get(string $slug): array
    {
        $meta = self::slugs()[$slug] ?? self::slugs()['mentions'];
        $sections = match ($slug) {
            'cgu' => self::cgu(),
            'cgv' => self::cgv(),
            'confidentialite' => self::confidentialite(),
            'cookies' => self::cookies(),
            default => self::mentions(),
        };

        return [
            'slug' => $slug,
            'title' => $meta['title'],
            'nav' => self::nav($slug),
            'sections' => $sections,
            'updated' => self::UPDATED,
            'version' => self::VERSION,
        ];
    }

    private static function mentions(): array
    {
        return [
            [
                'title' => 'Éditeur du site',
                'blocks' => [
                    ['p' => 'Le site acteursdulivre.fr (ci-après « la Plateforme ») est édité par :'],
                    ['html' => '<p><strong>EDITIONS TESSERACT</strong><br>Société par actions simplifiée (SAS) au capital de 6&nbsp;100&nbsp;€<br>Siège social : 486 rue Sadi Carnot, 59184 Sainghin-en-Weppes, France<br>RCS Lille Métropole 980&nbsp;005&nbsp;292<br>SIRET : 980&nbsp;005&nbsp;292&nbsp;00019<br>N° TVA intracommunautaire : FR14&nbsp;980&nbsp;005&nbsp;292<br>Code APE / NAF : 5811Z — Édition de livres</p>'],
                    ['p' => 'Président : TERCIUM, représentée par Julien LARZILLIÈRE. Directeur général : GPR PROJECTS, représentée par Guillaume REYNAERT.'],
                    ['html' => '<p>La maison d\'édition est également présente sur <a href="https://editions-tesseract.fr/">editions-tesseract.fr</a>.</p>'],
                ],
            ],
            [
                'title' => 'Directeur de la publication',
                'blocks' => [
                    ['p' => 'Le directeur de la publication est Julien LARZILLIÈRE, en qualité de représentant de TERCIUM, Président de EDITIONS TESSERACT.'],
                ],
            ],
            [
                'title' => 'Hébergement',
                'blocks' => [
                    ['p' => 'La Plateforme est hébergée en Suisse, en Europe, par :'],
                    ['html' => '<p><strong>Infomaniak Network SA</strong><br>Rue Eugène Marziano 25<br>1227 Les Acacias (GE), Suisse<br>Registre du commerce du canton de Genève : CH-660.0.059.996-1<br>IDE / TVA : CHE-103.167.648<br>Site : <a href="https://www.infomaniak.com/">infomaniak.com</a></p>'],
                    ['p' => 'Les serveurs sont situés en Suisse. La Commission européenne a reconnu le caractère adéquat de la protection des données personnelles en Suisse.'],
                ],
            ],
            [
                'title' => 'Contact',
                'blocks' => [
                    ['html' => '<ul><li>Contact général : <a href="mailto:bonjour@acteursdulivre.fr">bonjour@acteursdulivre.fr</a></li><li>Litiges et médiation : <a href="mailto:mediation@acteursdulivre.fr">mediation@acteursdulivre.fr</a></li><li>Presse : <a href="mailto:presse@acteursdulivre.fr">presse@acteursdulivre.fr</a></li><li>Formulaire : <a href="' . e(url('/contact')) . '">page Contact</a></li></ul>'],
                ],
            ],
            [
                'title' => 'Objet de la Plateforme',
                'blocks' => [
                    ['p' => 'acteursdulivre.fr est une place de marché des métiers du livre. Elle met en relation des porteurs de projet (auteurs, éditeurs, collectifs, institutions) et des prestataires (correcteurs, bêta-lecteurs, illustrateurs, traducteurs, maquettistes, imprimeurs, attachés de presse, libraires, narrateurs, agents, organisateurs de salons, notamment).'],
                    ['p' => 'EDITIONS TESSERACT n\'est ni l\'éditeur des ouvrages des utilisateurs, ni l\'employeur, ni l\'agent des prestataires. Les contrats de prestation, de cession de droits ou de mandat sont conclus entre le porteur de projet et le prestataire. La Plateforme intervient au titre de l\'intermédiation, de l\'outillage (vitrine, missions, messagerie, contrats types, facturation) et, le cas échéant, de la médiation.'],
                    ['p' => 'La Plateforme est actuellement en pré-ouverture : les professionnels du livre peuvent créer un compte. L\'ouverture aux clients est annoncée pour octobre 2026.'],
                ],
            ],
            [
                'title' => 'Propriété intellectuelle',
                'blocks' => [
                    ['p' => 'La marque, le nom de domaine, les textes, la charte graphique et les éléments de l\'interface d\'acteursdulivre.fr sont protégés. Toute reproduction non autorisée est interdite.'],
                    ['p' => 'Les textes, visuels, manuscrits, extraits et portfolios déposés par les utilisateurs restent leur propriété. EDITIONS TESSERACT n\'acquiert aucun droit d\'auteur sur les livres ou les livrables, hors la licence limitée nécessaire à l\'affichage des vitrines et au déroulement des missions sur la Plateforme.'],
                ],
            ],
            [
                'title' => 'Crédits',
                'blocks' => [
                    ['p' => 'Typographie d\'interface : Space Grotesk, servie par Google Fonts. Photographies de démonstration : visuels de prototype, destinés à l\'illustration de l\'interface avant ouverture complète.'],
                ],
            ],
            [
                'title' => 'Droit applicable',
                'blocks' => [
                    ['p' => 'Les présentes mentions sont régies par le droit français. À défaut de règlement amiable, les tribunaux compétents sont ceux du ressort du siège social de EDITIONS TESSERACT, sous réserve des règles impératives de compétence applicables aux consommateurs.'],
                ],
            ],
        ];
    }

    private static function cgu(): array
    {
        return [
            [
                'title' => '1. Objet et acceptation',
                'blocks' => [
                    ['p' => 'Les présentes conditions générales d\'utilisation (CGU) régissent l\'accès et l\'usage du site acteursdulivre.fr, édité par EDITIONS TESSERACT.'],
                    ['html' => '<p>L\'inscription, l\'utilisation d\'un compte ou la publication de contenu emportent acceptation des CGU, des <a href="' . e(url('/cgv')) . '">conditions générales de vente</a> lorsqu\'une mission est conclue via la Plateforme, et de la <a href="' . e(url('/confidentialite')) . '">politique de confidentialité</a>.</p>'],
                    ['p' => 'Si vous n\'acceptez pas ces conditions, vous devez cesser d\'utiliser la Plateforme.'],
                ],
            ],
            [
                'title' => '2. Définitions',
                'blocks' => [
                    ['html' => '<ul><li><strong>Porteur de projet</strong> ou <strong>Client</strong> : personne qui cherche un prestataire, commande une prestation ou publie une mission.</li><li><strong>Prestataire</strong> : professionnel du livre qui propose ses services via une vitrine, des formules ou des candidatures.</li><li><strong>Utilisateur</strong> : toute personne qui consulte ou utilise la Plateforme, avec ou sans compte.</li><li><strong>Mission</strong> : prestation convenue entre un Client et un Prestataire à la suite d\'une commande de formule ou de l\'acceptation d\'un devis.</li><li><strong>Plateforme</strong> : le site acteursdulivre.fr et les services associés.</li></ul>'],
                ],
            ],
            [
                'title' => '3. Description du service',
                'blocks' => [
                    ['p' => 'La Plateforme permet notamment :'],
                    ['html' => '<ul><li>de consulter un annuaire de professionnels du livre et des pages métier ;</li><li>de publier une vitrine, un CV, un portfolio et des formules à prix affiché ;</li><li>de commander une prestation packagée ou de publier un appel d\'offres ;</li><li>de candidater, d\'échanger par messagerie, d\'envoyer un devis et de suivre une mission ;</li><li>d\'utiliser des contrats types adaptés au métier (prestation de service, cession de droits, mandat, bon de commande) ;</li><li>de laisser un avis après une mission réellement réalisée via la Plateforme ;</li><li>d\'accéder à un centre d\'aide, à un journal et à un formulaire de contact.</li></ul>'],
                    ['p' => 'EDITIONS TESSERACT ne choisit pas le prestataire à la place du Client, ne pousse aucun profil contre rémunération publicitaire, et ne prend aucun droit sur les ouvrages.'],
                ],
            ],
            [
                'title' => '4. Pré-ouverture',
                'blocks' => [
                    ['p' => 'Tant que la bannière « Pré-ouverture » est affichée, l\'inscription est ouverte aux auteurs et professionnels du livre. L\'ouverture aux clients est prévue en octobre 2026. Certaines fonctions (commande payante, versements, avis liés à une mission payée) peuvent être incomplètes, en démonstration ou indisponibles.'],
                    ['p' => 'Les présentes CGU s\'appliquent dès l\'inscription. Les stipulations relatives aux commandes et aux paiements s\'appliqueront pleinement dès que ces fonctions seront ouvertes.'],
                ],
            ],
            [
                'title' => '5. Accès et compte',
                'blocks' => [
                    ['p' => 'La consultation du site est libre. La création d\'un compte est nécessaire pour publier, commander, candidater ou utiliser la messagerie.'],
                    ['p' => 'Un seul compte permet de chercher des prestataires et de proposer ses services. Lors de l\'inscription, l\'Utilisateur indique un prénom, un nom, un e-mail, un mot de passe d\'au moins huit caractères, et choisit s\'il cherche des prestataires, propose ses services, ou les deux. Ce choix peut être modifié ensuite dans l\'espace personnel.'],
                    ['p' => 'L\'Utilisateur garantit l\'exactitude des informations fournies et la confidentialité de ses identifiants. Toute action réalisée depuis le compte est réputée effectuée par son titulaire, sauf preuve contraire.'],
                    ['p' => 'La Plateforme s\'adresse à des personnes majeures. Les prestataires doivent être en mesure d\'exercer une activité indépendante ou sociétaire conforme au droit applicable (immatriculation, facturation, TVA le cas échéant).'],
                ],
            ],
            [
                'title' => '6. Vérification des profils prestataires',
                'blocks' => [
                    ['p' => 'Pour obtenir le badge « vérifié », le Prestataire peut être invité à fournir un justificatif d\'activité et une référence professionnelle. Un entretien peut être demandé pour les métiers de fabrication. Les faux profils peuvent être retirés sans préavis.'],
                    ['p' => 'La vérification atteste d\'éléments déclaratifs contrôlés ; elle ne constitue ni une certification de compétence, ni une caution de EDITIONS TESSERACT sur la qualité de chaque livrable.'],
                ],
            ],
            [
                'title' => '7. Charte qualité et interdiction d\'IA générative',
                'blocks' => [
                    ['p' => 'L\'usage de la Plateforme emporte adhésion à la charte qualité, dont les engagements suivants :'],
                    ['html' => '<ul><li>prix annoncé = prix facturé, hors options acceptées par écrit avant le démarrage ;</li><li>délais tenus ou signalés dès qu\'un retard est prévisible ;</li><li>confidentialité des manuscrits et des briefs : aucun extrait diffusé sans autorisation écrite ; un accord de confidentialité (NDA) peut être signé avant transmission ;</li><li>droits d\'exploitation écrits (cession, durée, supports, territoires) lorsqu\'ils sont pertinents ;</li><li>aucune intelligence artificielle générative pour produire les livrables (textes, illustrations, voix, traductions, maquettes générées) ;</li><li>avis sincères, uniquement après une mission réellement réalisée via la Plateforme.</li></ul>'],
                    ['p' => 'Les outils d\'aide au métier (correcteurs orthographiques, logiciels de PAO, dictionnaires, gestion de versions) restent autorisés. Sont interdits : la génération automatique de contenu, la sous-traitance cachée, et l\'entraînement de modèles sur les manuscrits ou fichiers confiés.'],
                    ['p' => 'Le Prestataire qui crée un compte s\'engage expressément à ce cadre. Un manquement peut entraîner la suspension ou la clôture du compte, sans préjudice des recours des Clients.'],
                ],
            ],
            [
                'title' => '8. Contenus publiés par les Utilisateurs',
                'blocks' => [
                    ['p' => 'L\'Utilisateur est seul responsable des contenus qu\'il publie (vitrine, formules, missions, messages, avis, fichiers). Il garantit disposer des droits nécessaires et que ces contenus ne portent pas atteinte aux droits des tiers, à l\'ordre public ou à la réglementation.'],
                    ['p' => 'Sont notamment interdits : les contenus illicites, diffamatoires, trompeurs, les usurpations d\'identité, le démarchage hors cadre de la mission, et la publication d\'extraits d\'un manuscrit d\'autrui sans accord.'],
                    ['p' => 'EDITIONS TESSERACT peut retirer un contenu signalé ou manifestement non conforme, et suspendre le compte en cas de manquement grave ou répété.'],
                ],
            ],
            [
                'title' => '9. Propriété intellectuelle des projets',
                'blocks' => [
                    ['p' => 'Les manuscrits, briefs, fichiers sources et livrables restent la propriété de leurs titulaires. La Plateforme n\'en fait usage que pour l\'affichage nécessaire aux vitrines, à la mise en relation et au suivi de mission.'],
                    ['p' => 'Sauf clause contraire du contrat conclu entre les parties, une commande de correction, maquette ou impression n\'emporte pas cession des droits d\'auteur sur l\'œuvre. Une commande d\'illustration, de traduction ou de narration s\'accompagne d\'un contrat type précisant l\'étendue de la cession.'],
                    ['p' => 'Aucun contenu déposé sur la Plateforme n\'est utilisé pour entraîner un modèle d\'intelligence artificielle.'],
                ],
            ],
            [
                'title' => '10. Messagerie, avis et signalement',
                'blocks' => [
                    ['p' => 'La messagerie est réservée aux échanges liés à une recherche, un devis ou une mission. Les avis ne peuvent être déposés qu\'après une mission réalisée via la Plateforme. Un avis peut être signalé ; la modération peut le masquer s\'il est hors sujet, injurieux ou manifestement de complaisance.'],
                    ['html' => '<p>Tout abus, faux profil ou manquement à la charte peut être signalé via la <a href="' . e(url('/contact')) . '">page Contact</a> ou, pour une commande en cours, depuis le suivi de mission.</p>'],
                ],
            ],
            [
                'title' => '11. Disponibilité',
                'blocks' => [
                    ['p' => 'EDITIONS TESSERACT s\'efforce d\'assurer un accès continu, sans garantir une disponibilité ininterrompue. Des interruptions peuvent intervenir pour maintenance, mise à jour ou cas de force majeure. Aucune indemnité n\'est due de ce seul fait.'],
                ],
            ],
            [
                'title' => '12. Responsabilité',
                'blocks' => [
                    ['p' => 'EDITIONS TESSERACT est un intermédiaire technique et commercial. Elle n\'est pas partie au contrat de prestation conclu entre le Client et le Prestataire, sauf pour le service d\'intermédiation décrit dans les CGV.'],
                    ['p' => 'Elle n\'est pas responsable de la qualité, de l\'originalité ou de la conformité d\'un livrable, ni des retards imputables aux parties, ni des fichiers transmis par les Utilisateurs. Les Utilisateurs restent seuls responsables de leurs obligations fiscales, sociales et contractuelles.'],
                    ['p' => 'La responsabilité de EDITIONS TESSERACT, si elle était engagée au titre de l\'usage de la Plateforme, est limitée aux dommages directs et, pour les professionnels, au montant des commissions effectivement perçues au cours des douze mois précédents, hors dol ou faute lourde et hors dispositions impératives protectrices des consommateurs.'],
                ],
            ],
            [
                'title' => '13. Suspension et résiliation',
                'blocks' => [
                    ['p' => 'L\'Utilisateur peut demander la clôture de son compte à tout moment, sous réserve de l\'achèvement ou de la résolution des missions en cours. EDITIONS TESSERACT peut suspendre ou clôturer un compte en cas de manquement aux CGU, à la charte, aux CGV, ou de risque pour la sécurité de la Plateforme.'],
                ],
            ],
            [
                'title' => '14. Modification',
                'blocks' => [
                    ['p' => 'Les CGU peuvent être mises à jour pour refléter l\'évolution de la Plateforme ou de la réglementation. La date de mise à jour figure en bas de page. L\'usage continu après publication vaut acceptation, sauf pour les missions déjà en cours, qui restent régies par la version applicable à leur conclusion.'],
                ],
            ],
            [
                'title' => '15. Droit applicable',
                'blocks' => [
                    ['p' => 'Les CGU sont soumises au droit français. Le Client consommateur conserve le bénéfice des dispositions impératives du pays de sa résidence habituelle lorsqu\'elles lui sont plus favorables. Les litiges relèvent des juridictions compétentes selon les règles de droit commun, le siège de EDITIONS TESSERACT étant à Sainghin-en-Weppes (Nord).'],
                ],
            ],
        ];
    }

    private static function cgv(): array
    {
        return [
            [
                'title' => '1. Objet',
                'blocks' => [
                    ['p' => 'Les présentes conditions générales de vente (CGV) régissent le service d\'intermédiation payant fourni par EDITIONS TESSERACT sur acteursdulivre.fr : mise en relation, outils de commande, contrats types, suivi, facturation de la commission et médiation interne.'],
                    ['p' => 'Le contrat de réalisation de la mission (correction, illustration, traduction, impression, etc.) est conclu entre le Client et le Prestataire. EDITIONS TESSERACT n\'est pas le vendeur de cette prestation intellectuelle ou industrielle, sauf lorsqu\'elle agirait elle-même comme prestataire, ce qui n\'est pas le cas du fonctionnement actuel de la Plateforme.'],
                    ['html' => '<p>L\'usage du site est également soumis aux <a href="' . e(url('/cgu')) . '">CGU</a>.</p>'],
                ],
            ],
            [
                'title' => '2. Identité du prestataire d\'intermédiation',
                'blocks' => [
                    ['html' => '<p><strong>EDITIONS TESSERACT</strong>, SAS au capital de 6&nbsp;100&nbsp;€, 486 rue Sadi Carnot, 59184 Sainghin-en-Weppes, RCS Lille Métropole 980&nbsp;005&nbsp;292, TVA FR14&nbsp;980&nbsp;005&nbsp;292. Contact : <a href="mailto:bonjour@acteursdulivre.fr">bonjour@acteursdulivre.fr</a>.</p>'],
                ],
            ],
            [
                'title' => '3. Ce qui est gratuit',
                'blocks' => [
                    ['p' => 'Restent gratuits, sans abonnement :'],
                    ['html' => '<ul><li>la création d\'un compte, d\'une vitrine et de formules ;</li><li>la publication d\'une mission et la réception de devis ;</li><li>la candidature aux appels d\'offres ;</li><li>la messagerie, l\'envoi de devis et l\'usage des contrats types ;</li><li>l\'annulation d\'une mission avant son démarrage effectif.</li></ul>'],
                    ['p' => 'Aucune commission n\'est due du seul fait de candidater ou de publier un appel d\'offres.'],
                ],
            ],
            [
                'title' => '4. Formation de la mission',
                'blocks' => [
                    ['p' => 'Une mission peut naître de deux façons :'],
                    ['html' => '<ul><li>commande d\'une formule à prix, délai et périmètre affichés, éventuellement complétée d\'options acceptées ;</li><li>acceptation d\'un devis envoyé en réponse à un appel d\'offres ou à un échange.</li></ul>'],
                    ['p' => 'Le Client décrit le besoin (métier, volume, budget, échéance, fichiers). Le Prestataire accepte ou refuse. L\'acceptation d\'un devis ou d\'une commande vaut contrat entre les parties, assorti du contrat type adapté au métier lorsqu\'il est joint.'],
                    ['p' => 'Le Prestataire n\'est jamais tenu d\'accepter une commande. Le Client n\'est jamais tenu d\'accepter un devis.'],
                ],
            ],
            [
                'title' => '5. Prix de la mission et commission',
                'blocks' => [
                    ['p' => 'Le prix de la mission est celui affiché sur la formule ou celui du devis accepté, en euros, hors options ultérieures. Sauf mention contraire, les prix des prestataires s\'entendent selon le régime fiscal qu\'ils déclarent (TVA applicable ou franchise en base).'],
                    ['p' => 'La Plateforme se rémunère uniquement sur les missions attribuées et réalisées, par une commission prélevée sur le montant de la mission :'],
                    ['html' => '<ul><li>8&nbsp;% aux niveaux Nouveau et Confirmé ;</li><li>6&nbsp;% au niveau Expert (après 120 missions livrées, sous réserve des critères de note, de délais et de réactivité observés sur les douze derniers mois).</li></ul>'],
                    ['p' => 'Exemple : une formule à 780 € au taux de 8 % donne une commission de 62 € et un versement prestataire de 718 €, avant prise en compte de la TVA éventuelle sur la commission.'],
                    ['p' => 'La commission rémunère le service d\'intermédiation et d\'outillage. Elle n\'est pas un salaire ni une retenue à la source pour le compte du Prestataire.'],
                ],
            ],
            [
                'title' => '6. Qui facture quoi',
                'blocks' => [
                    ['p' => 'Le Prestataire facture le Client pour la mission. EDITIONS TESSERACT émet un relevé de commission, en principe mensuel, à destination du Prestataire, et peut émettre une facture de commission par mission pour les acheteurs institutionnels lorsque le circuit de paiement l\'exige.'],
                    ['p' => 'Chaque Utilisateur est seul responsable de ses mentions légales de facture, de sa TVA et de ses obligations comptables.'],
                ],
            ],
            [
                'title' => '7. Paiement',
                'blocks' => [
                    ['p' => 'Lorsque le circuit de paiement de la Plateforme est ouvert, les moyens suivants sont prévus :'],
                    ['html' => '<ul><li>carte bancaire (Visa, Mastercard), avec enregistrement du règlement au démarrage de la mission ;</li><li>virement SEPA, sous deux jours ouvrés, notamment pour les commandes au-delà de 1&nbsp;000&nbsp;€ ;</li><li>facturation entreprise : bon de commande et paiement à 30 jours, pour les éditeurs et institutions.</li></ul>'],
                    ['p' => 'Le règlement du Prestataire intervient après validation de la livraison par le Client, ou à défaut selon les jalons convenus. Pour le niveau Expert, un règlement sous 15 jours ouvrés est annoncé. Les fonds peuvent être versés sur le compte bancaire renseigné par le Prestataire (IBAN).'],
                    ['p' => 'Les données de paiement carte, lorsqu\'elles seront collectées, seront traitées par un prestataire de paiement agréé. EDITIONS TESSERACT n\'a pas vocation à stocker les numéros de carte complets.'],
                    ['p' => 'En cas de signalement de litige depuis le suivi de commande, le versement peut être suspendu le temps de l\'examen.'],
                ],
            ],
            [
                'title' => '8. Exécution, délais et validation',
                'blocks' => [
                    ['p' => 'Le Prestataire exécute la mission conformément au brief, au devis et au contrat type. Les échanges et les fichiers passent par la messagerie et le suivi de commande lorsque ces outils sont disponibles.'],
                    ['p' => 'Le Client dispose, sauf délai différent convenu, d\'un temps raisonnable pour valider le livrable ou demander les allers-retours inclus. L\'absence de réponse dans le délai indiqué sur le suivi peut valoir validation, après relance.'],
                    ['p' => 'Un retard prévisible doit être signalé dès qu\'il est connu. Un supplément de prix n\'est dû que s\'il a été accepté par écrit avant le travail supplémentaire.'],
                ],
            ],
            [
                'title' => '9. Annulation',
                'blocks' => [
                    ['p' => 'Tant que la mission n\'a pas démarré (aucun fichier transmis pour exécution, aucun jalon commencé), l\'annulation est gratuite pour les deux parties et aucune commission n\'est due.'],
                    ['p' => 'Après démarrage, l\'annulation suit le contrat conclu entre les parties. En l\'absence de clause plus précise : le travail déjà réalisé peut être facturé au prorata ; la commission n\'est due que sur les sommes effectivement dues au Prestataire. En cas de désaccord, la médiation interne s\'applique.'],
                ],
            ],
            [
                'title' => '10. Droit de rétractation',
                'blocks' => [
                    ['p' => 'Le Client professionnel ne bénéficie pas du droit de rétractation prévu pour les consommateurs.'],
                    ['p' => 'Le Client consommateur dispose en principe d\'un délai de 14 jours pour se rétracter d\'un contrat conclu à distance, conformément aux articles L.221-18 et suivants du Code de la consommation, pour le service d\'intermédiation facturé par EDITIONS TESSERACT.'],
                    ['p' => 'Par exception (article L.221-28), le droit de rétractation ne s\'applique pas aux services pleinement exécutés avant la fin du délai si le consommateur a donné son accord préalable exprès et renoncé à son droit, ni aux contenus numériques fournis sur un support immatériel dont l\'exécution a commencé avec son accord, ni, le cas échéant, aux ouvrages confectionnés selon les spécifications du consommateur.'],
                    ['p' => 'La mission de création ou de correction intellectuelle, une fois commencée avec l\'accord du Client, peut ainsi échapper au droit de rétractation. Avant le démarrage, l\'annulation gratuite prévue à l\'article 9 s\'applique.'],
                ],
            ],
            [
                'title' => '11. Confidentialité des manuscrits',
                'blocks' => [
                    ['p' => 'Le Prestataire s\'interdit de divulguer, reproduire ou réutiliser un manuscrit, un brief ou un fichier hors de la mission, y compris dans son portfolio, sans autorisation écrite. Un NDA peut être signé avant toute transmission. Cette obligation survit à la fin de la mission.'],
                ],
            ],
            [
                'title' => '12. Litiges entre Client et Prestataire',
                'blocks' => [
                    ['p' => 'En cas d\'écart au brief :'],
                    ['html' => '<ol><li>signalement depuis le suivi de commande ; le versement peut être suspendu ;</li><li>72 heures d\'échange encadré dans la messagerie, avec un modérateur en lecture si nécessaire ;</li><li>à défaut d\'accord, médiation interne : proposition de remboursement total, partiel ou de versement ;</li><li>chaque partie reste libre de saisir un médiateur de la consommation (si le Client est consommateur) ou la juridiction compétente.</li></ol>'],
                    ['html' => '<p>Contact médiation : <a href="mailto:mediation@acteursdulivre.fr">mediation@acteursdulivre.fr</a>.</p>'],
                ],
            ],
            [
                'title' => '13. Médiation de la consommation',
                'blocks' => [
                    ['p' => 'Conformément aux articles L.611-1 et suivants du Code de la consommation, le Client consommateur peut recourir gratuitement à un médiateur de la consommation en cas de litige non résolu avec EDITIONS TESSERACT au titre du service d\'intermédiation.'],
                    ['p' => 'Le médiateur de la consommation désigné par EDITIONS TESSERACT sera indiqué sur cette page au plus tard à l\'ouverture de la place de marché aux clients (octobre 2026). En attendant cette désignation, toute réclamation écrite peut être adressée à mediation@acteursdulivre.fr.'],
                    ['html' => '<p>La plateforme européenne de règlement en ligne des litiges est accessible à l\'adresse : <a href="https://ec.europa.eu/consumers/odr">https://ec.europa.eu/consumers/odr</a>.</p>'],
                ],
            ],
            [
                'title' => '14. Garanties',
                'blocks' => [
                    ['p' => 'EDITIONS TESSERACT garantit que le service d\'intermédiation est fourni avec la diligence raisonnable d\'un professionnel. Elle ne garantit pas le résultat d\'une mission réalisée par un tiers.'],
                    ['p' => 'Le Client consommateur bénéficie de la garantie légale de conformité pour le service fourni par EDITIONS TESSERACT, dans les conditions du Code de la consommation. Les réclamations se font à bonjour@acteursdulivre.fr.'],
                ],
            ],
            [
                'title' => '15. Responsabilité',
                'blocks' => [
                    ['p' => 'EDITIONS TESSERACT n\'est pas responsable des manquements du Prestataire ou du Client à leurs obligations réciproques. Sa responsabilité au titre des CGV est limitée aux dommages directs liés au service d\'intermédiation, dans les mêmes limites que celles énoncées aux CGU, sous réserve des droits impératifs des consommateurs.'],
                ],
            ],
            [
                'title' => '16. Modification et droit applicable',
                'blocks' => [
                    ['p' => 'Les CGV peuvent être mises à jour. Les missions en cours restent soumises à la version en vigueur au jour de l\'acceptation du devis ou de la commande.'],
                    ['p' => 'Les CGV sont régies par le droit français. Compétence des tribunaux selon les règles de droit commun, siège à Sainghin-en-Weppes, sous réserve des règles protectrices des consommateurs.'],
                ],
            ],
        ];
    }

    private static function confidentialite(): array
    {
        return [
            [
                'title' => '1. Responsable de traitement',
                'blocks' => [
                    ['html' => '<p>Le responsable de traitement est <strong>EDITIONS TESSERACT</strong>, SAS, 486 rue Sadi Carnot, 59184 Sainghin-en-Weppes, RCS Lille Métropole 980&nbsp;005&nbsp;292. Contact : <a href="mailto:bonjour@acteursdulivre.fr">bonjour@acteursdulivre.fr</a>.</p>'],
                    ['p' => 'Aucun délégué à la protection des données n\'est désigné à ce jour. Les demandes s\'adressent à l\'e-mail ci-dessus, objet « Données personnelles ».'],
                ],
            ],
            [
                'title' => '2. Données collectées',
                'blocks' => [
                    ['p' => 'Selon l\'usage de la Plateforme, nous pouvons traiter :'],
                    ['html' => '<ul><li><strong>Compte</strong> : prénom, nom, e-mail, mot de passe (stocké sous forme hachée), rôle (client, prestataire, administrateur), statut, dates de création et de dernière connexion.</li><li><strong>Profil / vitrine</strong> : intitulé, présentation, ville, disponibilité (mode disponible / occupé et précision éventuelle), niveau, métiers, réalisations, pièces éventuellement transmises pour vérification (justificatif d\'activité, référence).</li><li><strong>Missions et commandes</strong> : briefs, volumes, budgets, fichiers échangés, devis, contrats, jalons, avis.</li><li><strong>Messagerie et notifications</strong> : contenu des messages et métadonnées nécessaires à l\'acheminement.</li><li><strong>Contact et newsletter</strong> : nom, e-mail, message, motif le cas échéant.</li><li><strong>Facturation</strong> : mentions de facture, IBAN partiellement masqué à l\'affichage, relevés de commission.</li><li><strong>Traces techniques</strong> : identifiant de session, jeton CSRF, adresse IP et journaux serveur conservés par l\'hébergeur pour la sécurité, dans une durée limitée.</li></ul>'],
                    ['p' => 'Les champs indispensables à l\'inscription sont l\'e-mail, le mot de passe, le prénom et le nom. Les autres données sont fournies pour utiliser les fonctions correspondantes.'],
                ],
            ],
            [
                'title' => '3. Finalités et bases légales',
                'blocks' => [
                    ['html' => '<ul><li><strong>Fournir le compte et la Plateforme</strong> — exécution du contrat (CGU).</li><li><strong>Mise en relation, missions, messagerie, facturation de commission</strong> — exécution du contrat (CGU / CGV).</li><li><strong>Vérification des profils, modération, lutte contre la fraude</strong> — intérêt légitime et, le cas échéant, obligation légale.</li><li><strong>Répondre aux messages de contact</strong> — intérêt légitime ou mesures précontractuelles.</li><li><strong>Lettre d\'information mensuelle</strong> — consentement (désinscription en un clic).</li><li><strong>Obligations comptables et fiscales</strong> — obligation légale.</li><li><strong>Amélioration de la sécurité</strong> — intérêt légitime.</li></ul>'],
                    ['p' => 'Aucun profilage publicitaire n\'est réalisé. Aucun manuscrit n\'est utilisé pour entraîner un modèle d\'intelligence artificielle.'],
                ],
            ],
            [
                'title' => '4. Destinataires',
                'blocks' => [
                    ['p' => 'Les données sont accessibles aux personnes habilitées de EDITIONS TESSERACT (administration, modération, médiation, support).'],
                    ['p' => 'Selon le contexte, elles sont également visibles : du Prestataire ou du Client cocontractant (profil public, messages, fichiers de mission) ; de l\'hébergeur Infomaniak Network SA (Suisse) ; du prestataire d\'envoi d\'e-mails lorsque le SMTP est configuré ; d\'un prestataire de paiement agréé lorsque les règlements en ligne seront ouverts.'],
                    ['p' => 'Les profils publics (vitrine, formules, avis) sont visibles par les visiteurs du site. Les manuscrits et pièces de mission ne sont pas publiés dans l\'annuaire.'],
                    ['p' => 'Aucune vente de fichiers n\'est pratiquée. Une autorité (CNIL, justice) peut être destinataire sur réquisition.'],
                ],
            ],
            [
                'title' => '5. Hébergement et transferts',
                'blocks' => [
                    ['p' => 'Les données sont hébergées en Suisse chez Infomaniak Network SA. La Suisse bénéficie d\'une décision d\'adéquation de la Commission européenne : le transfert n\'exige pas de clauses contractuelles types supplémentaires de ce seul fait.'],
                    ['p' => 'La feuille de style typographique est chargée depuis les serveurs de Google Fonts. Cette requête peut transiter hors Union européenne. Voir la politique cookies.'],
                ],
            ],
            [
                'title' => '6. Durées de conservation',
                'blocks' => [
                    ['html' => '<ul><li>Compte et profil : durée de vie du compte, puis suppression ou anonymisation dans un délai raisonnable après clôture, hors obligations légales.</li><li>Missions, factures et commissions : durée d\'exécution, puis conservation comptable (en principe 10 ans).</li><li>Messages de contact : le temps du traitement, puis 3 ans au plus pour le suivi de la relation.</li><li>Journaux techniques : durée usuelle de sécurité de l\'hébergeur, limitée au nécessaire.</li><li>Newsletter : jusqu\'à désinscription.</li></ul>'],
                ],
            ],
            [
                'title' => '7. Vos droits',
                'blocks' => [
                    ['p' => 'Vous pouvez demander l\'accès, la rectification, l\'effacement, la limitation, la portabilité lorsque le traitement repose sur le contrat ou le consentement, et vous opposer aux traitements fondés sur l\'intérêt légitime, dans les conditions du RGPD et de la loi Informatique et Libertés.'],
                    ['html' => '<p>Écrivez à <a href="mailto:bonjour@acteursdulivre.fr">bonjour@acteursdulivre.fr</a> ou utilisez les <a href="' . e(url('/espace/parametres')) . '">paramètres du compte</a>. Une pièce d\'identité peut être demandée en cas de doute. Vous pouvez introduire une réclamation auprès de la CNIL (cnil.fr).</p>'],
                    ['p' => 'La suppression du compte n\'efface pas nécessairement les factures ni les contenus qu\'un cocontractant doit conserver, ni les avis liés à une mission réalisée, qui peuvent être conservés de façon minimisée.'],
                ],
            ],
            [
                'title' => '8. Mineurs',
                'blocks' => [
                    ['p' => 'La Plateforme n\'est pas destinée aux personnes de moins de 18 ans. Si un compte a été créé par un mineur, contactez-nous pour sa suppression.'],
                ],
            ],
            [
                'title' => '9. Sécurité',
                'blocks' => [
                    ['p' => 'Les mots de passe sont hachés. Les sessions et les formulaires sensibles sont protégés par un jeton CSRF. L\'accès administration est réservé aux comptes de rôle administrateur. Aucun dispositif n\'étant infaillible, l\'Utilisateur signale tout accès anormal à bonjour@acteursdulivre.fr.'],
                ],
            ],
            [
                'title' => '10. Modification',
                'blocks' => [
                    ['html' => '<p>Cette politique peut être mise à jour. La date figure en bas de page. Pour les cookies, voir la <a href="' . e(url('/cookies')) . '">politique cookies</a>.</p>'],
                ],
            ],
        ];
    }

    private static function cookies(): array
    {
        return [
            [
                'title' => '1. Ce que nous utilisons aujourd\'hui',
                'blocks' => [
                    ['p' => 'La Plateforme n\'utilise pas, à ce jour, de cookies publicitaires, de mesure d\'audience tierce ni de bandeau de consentement : seuls des cookies et stockages strictement nécessaires au fonctionnement sont déposés.'],
                    ['p' => 'Si des traceurs non essentiels étaient ajoutés plus tard (statistiques, lecture vidéo tierce, paiement embarqué), un recueil de consentement serait mis en place avant leur dépôt, hors cookies indispensables.'],
                ],
            ],
            [
                'title' => '2. Cookies et stockage nécessaires',
                'blocks' => [
                    ['html' => '<ul><li><strong>adl_session</strong> (cookie de session PHP, nom par défaut) : maintient la connexion, les messages flash et le jeton de sécurité. Durée : la session de navigation, sauf paramétrage serveur plus long. Finalité : authentification et sécurité. Base : intérêt légitime / exécution du contrat.</li><li><strong>Jeton CSRF</strong> : valeur aléatoire liée à la session, jointe aux formulaires (connexion, inscription, contact, paramètres). Empêche l\'envoi d\'un formulaire à votre place depuis un autre site.</li></ul>'],
                    ['p' => 'Ces traceurs ne nécessitent pas de consentement au sens des recommandations CNIL, car ils sont indispensables au service demandé.'],
                ],
            ],
            [
                'title' => '3. Stockage local (navigateur)',
                'blocks' => [
                    ['p' => 'Un widget de test de charte graphique peut enregistrer trois couleurs (marine, orange, beige) dans le localStorage du navigateur, uniquement sur votre appareil, pour prévisualiser l\'interface. Ce n\'est pas un cookie, ce n\'est pas transmis à nos serveurs, et cela ne sert pas à vous identifier. Vous pouvez l\'effacer via « Réinitialiser » dans le widget ou en vidant les données du site dans votre navigateur.'],
                ],
            ],
            [
                'title' => '4. Tiers : Google Fonts',
                'blocks' => [
                    ['p' => 'La police Space Grotesk est chargée depuis fonts.googleapis.com / fonts.gstatic.com. Votre navigateur établit une connexion vers ces serveurs, ce qui peut entraîner le traitement d\'une adresse IP par Google. Nous ne contrôlons pas les cookies éventuellement déposés par Google sur ses propres domaines.'],
                    ['p' => 'Vous pouvez bloquer les polices tierces dans votre navigateur ; l\'interface basculera sur une police système.'],
                ],
            ],
            [
                'title' => '5. Newsletter et formulaires',
                'blocks' => [
                    ['p' => 'Le bandeau d\'inscription à la lettre d\'information du pied de page renvoie aujourd\'hui vers la page Contact : aucune liste n\'est alimentée automatiquement par un cookie. Si vous nous écrivez, vos données suivent la politique de confidentialité.'],
                ],
            ],
            [
                'title' => '6. Gérer les cookies',
                'blocks' => [
                    ['p' => 'Vous pouvez supprimer les cookies du site via les paramètres de votre navigateur. La suppression du cookie de session vous déconnecte.'],
                    ['html' => '<p>Pour en savoir plus sur les données personnelles : <a href="' . e(url('/confidentialite')) . '">politique de confidentialité</a>. Contact : <a href="mailto:bonjour@acteursdulivre.fr">bonjour@acteursdulivre.fr</a>.</p>'],
                ],
            ],
        ];
    }
}
