<?php

declare(strict_types=1);

namespace Adl\Data;

use Adl\Core\Auth;
use Adl\Models\User;

final class AdminCatalog
{
    public const NAVY = '#15212f';
    public const ORANGE = '#D85D3F';

    public static function forScreen(string $screen, array $extra = []): array
    {
        $user = Auth::user();
        $data = array_merge(self::shared($user, $screen, $extra['query'] ?? ''), self::content(), $extra);
        $data['screen'] = $screen;
        $flags = ['dash', 'verif', 'moderation', 'users', 'catalogue', 'missions', 'finances', 'litiges', 'avis', 'preouverture', 'cms', 'reglages'];
        foreach ($flags as $id) {
            $data['is' . ucfirst($id)] = $id === $screen;
        }
        $data['isDash'] = $screen === 'dash';
        $data['isVerif'] = $screen === 'verif';
        $data['isCms'] = $screen === 'cms';
        $data['isPreouverture'] = $screen === 'preouverture';
        return $data;
    }

    private static function shared(?array $user, string $screen, string $query): array
    {
        $items = [
            ['dash', 'Tableau de bord', '', 'Pilotage', '/admin'],
            ['verif', 'Vérifications', '24', '', '/admin/verifications'],
            ['moderation', 'Modération', '18', '', '/admin/moderation'],
            ['litiges', 'Litiges', '4', '', '/admin/litiges'],
            ['avis', 'Avis signalés', '5', '', '/admin/avis'],
            ['users', 'Utilisateurs', '', 'Données', '/admin/utilisateurs'],
            ['catalogue', 'Prestations', '', '', '/admin/prestations'],
            ['missions', 'Appels d\'offres', '12', '', '/admin/missions'],
            ['finances', 'Commandes & finances', '', '', '/admin/finances'],
            ['preouverture', 'Pré-ouverture', '', 'Plateforme', '/admin/pre-ouverture'],
            ['cms', 'Journal & pages', '', '', '/admin/journal'],
            ['reglages', 'Réglages', '', '', '/admin/reglages'],
            ['smtp', 'SMTP', '', '', '/admin/smtp'],
            ['emails', 'Modèles d\'e-mails', '', '', '/admin/emails'],
        ];
        $nav = [];
        foreach ($items as [$id, $label, $badge, $group, $href]) {
            $nav[] = [
                'id' => $id,
                'label' => $label,
                'badge' => $badge,
                'group' => $group,
                'href' => $href,
                'active' => $id === $screen,
            ];
        }

        return [
            'title' => self::titles()[$screen] ?? 'Administration',
            'query' => $query,
            'nav' => $nav,
            'adminName' => $user ? User::displayName($user) : 'Awa Diallo',
            'adminInitials' => $user ? User::initials($user) : 'AD',
            'adminRole' => ($user['role'] ?? '') === 'admin' ? 'Administration · accès complet' : 'Modération · niveau 2',
        ];
    }

    private static function titles(): array
    {
        return [
            'dash' => 'Tableau de bord',
            'verif' => 'Vérifications',
            'moderation' => 'Modération',
            'litiges' => 'Litiges',
            'avis' => 'Avis signalés',
            'users' => 'Utilisateurs',
            'catalogue' => 'Prestations',
            'missions' => 'Appels d\'offres',
            'finances' => 'Commandes & finances',
            'preouverture' => 'Pré-ouverture',
            'cms' => 'Journal & pages',
            'reglages' => 'Réglages',
            'smtp' => 'Paramètres SMTP',
            'emails' => 'Modèles d\'e-mails',
        ];
    }

    private static function content(): array
    {
        $navy = self::NAVY;
        $orange = self::ORANGE;

        return [
            'kpis' => [
                ['k' => 'Prestataires actifs', 'v' => '5 890', 'd' => '+214 cette semaine', 'deltaStyle' => 'font-size: 13px; margin-top: 4px; color: #2E6B45;'],
                ['k' => 'Missions ouvertes', 'v' => '148', 'd' => '+12', 'deltaStyle' => 'font-size: 13px; margin-top: 4px; color: #2E6B45;'],
                ['k' => 'Commandes du mois', 'v' => '312', 'd' => '+8 %', 'deltaStyle' => 'font-size: 13px; margin-top: 4px; color: #2E6B45;'],
                ['k' => 'Commission encaissée', 'v' => '24 180 €', 'd' => '+11 %', 'deltaStyle' => 'font-size: 13px; margin-top: 4px; color: #2E6B45;'],
                ['k' => 'Délai de modération', 'v' => '9 h', 'd' => 'cible 24 h', 'deltaStyle' => 'font-size: 13px; margin-top: 4px; color: #2E6B45;'],
            ],
            'chart' => self::chart(),
            'files' => self::files(),
            'activite' => self::activite(),
            'verifFilters' => self::chips(['Tous (24)', 'Priorité (5)', 'Relances (3)', 'Refus proposés (2)'], 0),
            'dossiers' => self::dossiers(),
            'dossierName' => 'Atelier Virgule',
            'dossierRole' => 'Relecture sur épreuves · Paris',
            'dossierInitials' => 'AV',
            'dossierAvatar' => avatar_style('AV', 46),
            'dossierMeta' => [
                ['k' => 'Métier déclaré', 'v' => 'Correction'],
                ['k' => 'Statut juridique', 'v' => 'Micro-entreprise'],
                ['k' => 'SIRET', 'v' => 'vérifié'],
                ['k' => 'Références', 'v' => '2 sur 2 confirmées'],
                ['k' => 'Engagement IA', 'v' => 'signé'],
            ],
            'dossierPieces' => [
                ['ext' => 'PDF', 'name' => 'avis-de-situation-insee.pdf', 'ok' => 'Conforme', 'okStyle' => self::pill('green')],
                ['ext' => 'PDF', 'name' => 'attestation-assurance.pdf', 'ok' => 'Conforme', 'okStyle' => self::pill('green')],
                ['ext' => 'PDF', 'name' => 'engagement-sans-ia-signe.pdf', 'ok' => 'Signé', 'okStyle' => self::pill('orange')],
                ['ext' => 'JPG', 'name' => 'portfolio-3-realisations.jpg', 'ok' => 'À contrôler', 'okStyle' => self::pill('grey')],
            ],
            'controles' => self::controles(),
            'modFilters' => self::chips(['Tout (18)', 'IA générative (7)', 'Hors plateforme (4)', 'Droits & plagiat (4)', 'Autre (3)'], 0),
            'signalements' => self::signalements(),
            'userFilters' => self::chips(['Tous', 'Prestataires', 'Porteurs de projet', 'Organisations', 'Suspendus'], 0),
            'users' => self::users(),
            'catalogue' => self::catalogue(),
            'missions' => self::missions(),
            'finKpis' => [
                ['k' => 'Volume d\'affaires', 'v' => '302 400 €', 'note' => '312 commandes', 'card' => 'border-radius: 12px; padding: 18px; background: #FFF; border: 1px solid #E8ECF1; color: #022746;'],
                ['k' => 'Commission encaissée', 'v' => '24 180 €', 'note' => 'taux moyen 8,0 %', 'card' => 'border-radius: 12px; padding: 18px; background: #022746; color: #E4EDF5;'],
                ['k' => 'Panier moyen', 'v' => '969 €', 'note' => '+6 % vs juillet', 'card' => 'border-radius: 12px; padding: 18px; background: #FFF; border: 1px solid #E8ECF1; color: #022746;'],
                ['k' => 'Impayés', 'v' => '3 840 €', 'note' => '4 factures en retard', 'card' => 'border-radius: 12px; padding: 18px; background: #FFF; border: 1px solid #E8ECF1; color: #022746;'],
            ],
            'commandes' => self::commandes(),
            'impayes' => [
                ['who' => 'Presse Rapide SARL', 'retard' => 'facture 2418-02 · 41 jours', 'montant' => '1 240 €'],
                ['who' => 'Contenu Web Plus', 'retard' => 'facture 2402-01 · 36 jours', 'montant' => '980 €'],
                ['who' => 'Ateliers Mémoires', 'retard' => 'facture 2396-03 · 12 jours', 'montant' => '1 620 €'],
            ],
            'commissionMetier' => [
                ['metier' => 'Correction', 'montant' => '7 420 €', 'bar' => 'height: 100%; width: 100%; background: #D85D3F;'],
                ['metier' => 'Illustration', 'montant' => '5 860 €', 'bar' => 'height: 100%; width: 79%; background: #D85D3F;'],
                ['metier' => 'Impression', 'montant' => '4 910 €', 'bar' => 'height: 100%; width: 66%; background: #D85D3F;'],
                ['metier' => 'Traduction', 'montant' => '3 480 €', 'bar' => 'height: 100%; width: 47%; background: #D85D3F;'],
                ['metier' => 'Presse & com', 'montant' => '1 320 €', 'bar' => 'height: 100%; width: 18%; background: #D85D3F;'],
            ],
            'litiges' => self::litiges(),
            'litigeNum' => 'LIT-2026-041',
            'litigeTitle' => 'Rapport de lecture non livré',
            'litigeParties' => 'Éditions Pampa / Paul Ferrand',
            'litigeMontant' => '640 €',
            'litigeCommande' => '2477-01',
            'litigeTimeline' => [
                ['when' => '18 août', 'label' => 'Commande passée', 'note' => 'Devis accepté, contrat de prestation signé par les deux parties.'],
                ['when' => '2 sept.', 'label' => 'Livraison contestée', 'note' => 'Le client conteste le périmètre livré et refuse la validation.'],
                ['when' => '3 sept.', 'label' => 'Échange encadré ouvert', 'note' => '72 h pour trouver un accord dans la messagerie, modérateur en lecture.'],
                ['when' => '6 sept.', 'label' => 'Médiation saisie', 'note' => 'Aucun accord. Pièces réunies : brief, livrable, échanges, contrat.'],
            ],
            'decisions' => self::decisions(),
            'avisSignales' => self::avisSignales(),
            'preKpis' => [
                ['k' => 'Prestataires inscrits', 'v' => '5 890', 'd' => 'objectif 6 500'],
                ['k' => 'Liste d\'attente clients', 'v' => '2 140', 'd' => '+310 cette semaine'],
                ['k' => 'Invitations envoyées', 'v' => '3 vagues', 'd' => '1 240 accès ouverts'],
                ['k' => 'Métiers couverts', 'v' => '10 / 12', 'd' => 'agents et salons à combler'],
            ],
            'preFilters' => self::chips(['Tous', 'Auteurs', 'Clients'], 0),
            'attente' => self::attente(),
            'couverture' => [
                ['metier' => 'Correction', 'n' => '1 105', 'bar' => 'height: 100%; width: 100%; background: #022746;'],
                ['metier' => 'Illustration', 'n' => '860', 'bar' => 'height: 100%; width: 78%; background: #022746;'],
                ['metier' => 'Librairie', 'n' => '690', 'bar' => 'height: 100%; width: 62%; background: #022746;'],
                ['metier' => 'Agents littéraires', 'n' => '62', 'bar' => 'height: 100%; width: 12%; background: #D85D3F;'],
                ['metier' => 'Salons & événements', 'n' => '98', 'bar' => 'height: 100%; width: 18%; background: #D85D3F;'],
            ],
            'articles' => self::articles(),
            'reglagesNav' => self::sideNav(['Commission & niveaux', 'Politique IA', 'Métiers', 'Modération', 'Équipe & droits', 'SMTP & e-mails'], 0),
            'reglagesTitle' => 'Commission & niveaux',
            'commissionRows' => [
                ['niveau' => 'Nouveau', 'pct' => '8 %', 'seuil' => 'dès l\'inscription'],
                ['niveau' => 'Confirmé', 'pct' => '8 %', 'seuil' => 'à partir de 30 missions'],
                ['niveau' => 'Expert', 'pct' => '6 %', 'seuil' => 'à partir de 120 missions'],
            ],
            'iaReglages' => self::iaReglages(),
            'metiersReglage' => self::metiersReglage(),
        ];
    }

    public static function pill(string $tone): string
    {
        $map = [
            'orange' => 'background: #FDF3F0; color: #D85D3F;',
            'navy' => 'background: #EEF3F8; color: #022746;',
            'green' => 'background: #ECF5EF; color: #2E6B45;',
            'grey' => 'background: #F4F6F9; color: #66768A;',
        ];
        return 'font-size: 12px; padding: 5px 10px; border-radius: 999px; font-family: \'Space Grotesk\', monospace; ' . ($map[$tone] ?? $map['grey']);
    }

    private static function chips(array $labels, int $active): array
    {
        $out = [];
        foreach ($labels as $i => $label) {
            $on = $i === $active;
            $out[] = [
                'label' => $label,
                'style' => 'border: 1px solid ' . ($on ? self::NAVY : '#E1E7ED') . '; background: ' . ($on ? self::NAVY : '#FFF') . '; color: ' . ($on ? '#FFF' : '#4A5A6B') . '; border-radius: 999px; padding: 8px 14px; font-size: 13px;',
            ];
        }
        return $out;
    }

    private static function sideNav(array $labels, int $active): array
    {
        $out = [];
        foreach ($labels as $i => $label) {
            $on = $i === $active;
            $href = $label === 'SMTP & e-mails' ? '/admin/smtp' : '#';
            $out[] = [
                'label' => $label,
                'href' => $href,
                'style' => 'padding: 11px 13px; border-radius: 9px; font-size: 14px; cursor: pointer; background: ' . ($on ? '#F4F6F9' : 'transparent') . '; color: ' . ($on ? self::NAVY : '#66768A') . '; font-weight: ' . ($on ? '500' : '400') . ';',
            ];
        }
        return $out;
    }

    private static function chart(): array
    {
        $rows = [['jan', 30, 8], ['fev', 42, 10], ['mar', 38, 6], ['avr', 55, 14], ['mai', 61, 9], ['juin', 74, 16], ['juil', 82, 12], ['aout', 91, 24]];
        $out = [];
        foreach ($rows as [$m, $ok, $pend]) {
            $out[] = [
                'm' => $m,
                'barOk' => 'width: 100%; height: ' . ($ok * 0.9) . '%; background: #022746; border-radius: 0 0 3px 3px;',
                'barPending' => 'width: 100%; height: ' . ($pend * 0.9) . '%; background: #D85D3F; border-radius: 3px 3px 0 0;',
            ];
        }
        return $out;
    }

    private static function files(): array
    {
        $items = [
            ['Profils à vérifier', 'justificatif d\'activité et engagement IA', 24, '6 h', 'Dans les temps', 'green', '/admin/verifications'],
            ['Contenus signalés', '7 soupçons d\'IA générative', 18, '3 h', 'Dans les temps', 'green', '/admin/moderation'],
            ['Missions à modérer', 'avant publication publique', 12, '28 h', 'SLA dépassé', 'orange', '/admin/missions'],
            ['Litiges ouverts', 'médiation sous 72 h', 4, '4 j', '1 en retard', 'orange', '/admin/litiges'],
            ['Avis contestés', 'contestation par le prestataire', 5, '18 h', 'Dans les temps', 'green', '/admin/avis'],
        ];
        $out = [];
        foreach ($items as [$label, $note, $n, $age, $sla, $tone, $href]) {
            $out[] = ['label' => $label, 'note' => $note, 'n' => $n, 'age' => $age, 'sla' => $sla, 'slaStyle' => self::pill($tone), 'href' => $href];
        }
        return $out;
    }

    private static function activite(): array
    {
        $items = [
            ['Profil « Imprimerie Feuillage » validé, badge vérifié attribué', 'Awa D.', 'il y a 12 min', 'green'],
            ['Prestation retirée : illustration soupçonnée générée par IA', 'Thomas B.', 'il y a 40 min', 'orange'],
            ['Litige LIT-2026-041 : pièce complémentaire demandée', 'Thomas B.', 'il y a 1 h', 'navy'],
            ['Commission du niveau Expert passée de 6,5 % à 6 %', 'Samuel O.', 'il y a 3 h', 'navy'],
            ['Vague 3 d\'invitations envoyée à 180 correcteurs', 'Awa D.', 'hier', 'navy'],
            ['Avis contesté maintenu après examen des échanges', 'Awa D.', 'hier', 'grey'],
        ];
        $out = [];
        foreach ($items as [$txt, $who, $when, $tone]) {
            $color = match ($tone) {
                'orange' => self::ORANGE,
                'green' => '#2E6B45',
                'navy' => self::NAVY,
                default => '#C3CEDA',
            };
            $out[] = ['txt' => $txt, 'who' => $who, 'when' => $when, 'dot' => 'width: 8px; height: 8px; min-width: 8px; border-radius: 50%; margin-top: 6px; background: ' . $color . ';'];
        }
        return $out;
    }

    private static function dossiers(): array
    {
        $items = [
            ['Atelier Virgule', 'AV', 'Relecture sur épreuves · Paris', 'il y a 3 h', '4 pièces', 'Correction', 'Priorité'],
            ['Imprimerie Feuillage', 'IF', 'Imprimeur offset · Angers', 'il y a 6 h', '6 pièces', 'Impression', ''],
            ['Nora Belkacem', 'NB', 'Traductrice AR→FR · à distance', 'hier', '3 pièces', 'Traduction', ''],
            ['Studio Bel Écho', 'BE', 'Narration & livre audio · Lille', 'hier', '5 pièces', 'Audio', 'Relance'],
            ['Marc Tissier', 'MT', 'Agent littéraire · Paris', 'il y a 2 j', '2 pièces', 'Agents', ''],
            ['Librairie du Passage', 'LP', 'Libraire indépendant · Dijon', 'il y a 3 j', '4 pièces', 'Librairie', ''],
        ];
        $out = [];
        foreach ($items as $i => [$name, $initials, $role, $when, $pieces, $metier, $tag]) {
            $on = $i === 0;
            $out[] = [
                'name' => $name, 'initials' => $initials, 'role' => $role, 'when' => $when, 'pieces' => $pieces, 'metier' => $metier, 'tag' => $tag,
                'avatar' => avatar_style($initials, 38),
                'row' => 'display: flex; gap: 14px; align-items: center; padding: 15px 18px; border-bottom: 1px solid #F2F5F8; cursor: pointer; background: ' . ($on ? '#FBFCFE' : '#FFF') . '; box-shadow: ' . ($on ? 'inset 3px 0 0 #D85D3F' : 'none') . ';',
                'tagStyle' => $tag !== '' ? self::pill($tag === 'Priorité' ? 'orange' : 'navy') : 'display: none;',
            ];
        }
        return $out;
    }

    private static function controles(): array
    {
        $labels = [
            'Identité et existence légale vérifiées',
            'Références professionnelles contactées',
            'Engagement sans IA générative signé',
            'Portfolio contrôlé — pas d\'image générée',
            'Tarifs cohérents avec le marché du métier',
        ];
        $ok = [true, true, true, false, false];
        $out = [];
        foreach ($labels as $i => $label) {
            $on = $ok[$i];
            $out[] = [
                'label' => $label,
                'check' => $on ? '✓' : '',
                'row' => 'display: flex; gap: 10px; align-items: center; cursor: pointer;',
                'box' => 'width: 18px; height: 18px; min-width: 18px; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 11px; color: #FFF; border: 1.5px solid ' . ($on ? self::NAVY : '#C3CEDA') . '; background: ' . ($on ? self::NAVY : '#FFF') . ';',
            ];
        }
        return $out;
    }

    private static function signalements(): array
    {
        $items = [
            ['IA générative', 'orange', 'Signalement client', 'il y a 2 h', 'Couverture illustrée — « Le Sel des jours »', 'Studio Halo', 'Prestation nº 1284, commande 2468-03', 'Le client relève des mains à six doigts et une typographie déformée sur le visuel livré. Le prestataire n\'a pas fourni de fichiers de travail intermédiaires malgré deux demandes.', 'Risque élevé'],
            ['IA générative', 'orange', 'Contrôle interne', 'il y a 5 h', 'Présentation de vitrine — texte suspect', 'Correction Plume & Cie', 'Profil nº 4471', 'Formulations génériques répétées à l\'identique sur trois profils créés le même jour, depuis la même adresse IP. Métiers déclarés incohérents entre eux.', 'Faisceau d\'indices'],
            ['Hors plateforme', 'navy', 'Détection messagerie', 'hier', 'Proposition de règlement par virement direct', 'Marc T. → Éditions La Ligne', 'Conversation nº 8842', '« On peut régler ça en direct, ça vous évitera la commission. » Message intercepté avant envoi, expéditeur averti automatiquement.', 'Premier avertissement'],
            ['Droits', 'grey', 'Signalement prestataire', 'hier', 'Portfolio reprenant des visuels d\'un tiers', 'Atelier Lumen', 'Profil nº 3120', 'Deux illustrations du portfolio apparaissent sur le site d\'une autre illustratrice inscrite, avec une antériorité établie.', 'À instruire'],
            ['Autre', 'grey', 'Signalement client', 'il y a 2 j', 'Prestation au périmètre trompeur', 'Rapido Correction', 'Prestation nº 2016', '« Correction complète » annoncée à 39 € pour 500 000 signes : périmètre irréaliste au regard de la charte tarifaire du métier.', 'À instruire'],
        ];
        $out = [];
        foreach ($items as [$motif, $tone, $source, $when, $title, $who, $where, $extrait, $risque]) {
            $out[] = ['motif' => $motif, 'source' => $source, 'when' => $when, 'title' => $title, 'who' => $who, 'where' => $where, 'extrait' => $extrait, 'risque' => $risque, 'motifStyle' => self::pill($tone)];
        }
        return $out;
    }

    private static function users(): array
    {
        $items = [
            ['Marion Vasseur', 'MV', 'marion@vasseur-correction.fr', 'Correction', 'Confirmée', '87', '4,9', 'Actif', 'green'],
            ['Atelier Kess', 'AK', 'contact@atelierkess.fr', 'Illustration', 'Experte', '41', '5,0', 'Actif', 'green'],
            ['Imprimerie Baudry', 'IB', 'devis@baudry-impression.fr', 'Impression', 'Expert', '213', '4,8', 'Actif', 'green'],
            ['Éditions La Ligne', 'EL', 'fabrication@editionslaligne.fr', 'Éditeur', 'Organisation', '34', '—', 'Actif', 'green'],
            ['Studio Halo', 'SH', 'studio.halo@mail.fr', 'Illustration', 'Nouveau', '3', '3,2', 'Sous enquête', 'orange'],
            ['Rapido Correction', 'RC', 'rapido.corr@mail.fr', 'Correction', 'Nouveau', '1', '2,8', 'Suspendu', 'orange'],
            ['Sofia Renard', 'SR', 'sofia.renard@trad.fr', 'Traduction', 'Confirmée', '64', '4,9', 'Actif', 'green'],
            ['Librairie du Passage', 'LP', 'passage@librairie.fr', 'Librairie', 'Nouveau', '0', '—', 'En attente', 'navy'],
        ];
        $out = [];
        foreach ($items as [$name, $initials, $email, $metier, $niveau, $missions, $note, $statut, $tone]) {
            $out[] = [
                'name' => $name, 'initials' => $initials, 'email' => $email, 'metier' => $metier, 'niveau' => $niveau,
                'missions' => $missions, 'note' => $note, 'statut' => $statut,
                'avatar' => avatar_style($initials, 30),
                'statutStyle' => self::pill($tone),
            ];
        }
        return $out;
    }

    private static function catalogue(): array
    {
        $items = [
            ['Correction complète d\'un roman jusqu\'à 90 000 signes', 'Correction', 'Marion Vasseur', '420 €', '742', '12', 'En ligne', 'green'],
            ['Couverture illustrée + déclinaisons réseaux', 'Illustration', 'Atelier Kess', '480 €', '1 204', '18', 'En ligne', 'green'],
            ['300 exemplaires broché, papier bouffant', 'Impression', 'Imprimerie Baudry', '1 190 €', '980', '31', 'En ligne', 'green'],
            ['Enregistrement livre audio, 6 h de narration', 'Audio', 'Studio Bel Écho', '2 100 €', '310', '4', 'En attente', 'navy'],
            ['Correction complète à 39 € tous volumes', 'Correction', 'Rapido Correction', '39 €', '88', '1', 'Retirée', 'orange'],
            ['Illustration de couverture, style « aquarelle »', 'Illustration', 'Studio Halo', '260 €', '412', '3', 'Suspendue', 'orange'],
            ['Mise en page intérieure, fichiers prêts à imprimer', 'Maquette', 'Studio Grain', '650 €', '534', '9', 'En ligne', 'green'],
        ];
        $out = [];
        foreach ($items as [$title, $metier, $by, $prix, $vues, $cmd, $statut, $tone]) {
            $out[] = ['title' => $title, 'metier' => $metier, 'by' => $by, 'prix' => $prix, 'vues' => $vues, 'cmd' => $cmd, 'statut' => $statut, 'statutStyle' => self::pill($tone)];
        }
        return $out;
    }

    private static function missions(): array
    {
        $items = [
            ['Recherche correcteur pour essai historique, 240 pages', 'Éditions du Fleuve Noirci', 'Correction', '600 – 900 €', 7, 'il y a 2 j', '', ''],
            ['Illustrateur album jeunesse 3-6 ans, 24 pages', 'Camille D.', 'Illustration', '1 800 – 2 500 €', 14, 'il y a 3 j', '', ''],
            ['Correction de 12 romans, forfait global', 'Presse Rapide SARL', 'Correction', '900 €', 0, 'il y a 4 h', 'Budget hors marché', 'orange'],
            ['Traduction ES→FR d\'un recueil de nouvelles', 'Éditions Pampa', 'Traduction', '3 200 €', 4, 'il y a 4 j', '', ''],
            ['Rewriting de fiches produits, gros volume', 'Contenu Web Plus', 'Rédaction', '400 €', 0, 'il y a 6 h', 'Hors périmètre livre', 'orange'],
            ['Narrateur pour livre audio, 7 h de texte', 'Studio Bel Écho', 'Audio', '2 400 €', 6, 'il y a 1 sem.', '', ''],
        ];
        $out = [];
        foreach ($items as [$title, $by, $metier, $budget, $candidatures, $when, $flag, $tone]) {
            $out[] = [
                'title' => $title, 'by' => $by, 'metier' => $metier, 'budget' => $budget, 'candidatures' => $candidatures, 'when' => $when, 'flag' => $flag,
                'flagStyle' => $flag !== '' ? self::pill('orange') : 'display: none;',
            ];
        }
        return $out;
    }

    private static function commandes(): array
    {
        $items = [
            ['2481-03', 'Correction complète — essai historique', 'Fleuve Noirci → M. Vasseur', '780 €', '62 €', 'En cours', 'navy'],
            ['2477-01', 'Couverture illustrée + déclinaisons', 'Camille D. → Atelier Kess', '480 €', '38 €', 'Livrée', 'green'],
            ['2469-02', '300 ex. broché papier bouffant', 'Encre Vive → Baudry', '1 190 €', '95 €', 'En cours', 'navy'],
            ['2468-03', 'Illustration de couverture', 'Camille D. → Studio Halo', '480 €', '38 €', 'En litige', 'orange'],
            ['2455-04', 'Maquette intérieure, 240 pages', 'La Ligne → Studio Grain', '650 €', '52 €', 'Réglée', 'green'],
            ['2441-01', 'Traduction ES→FR, recueil', 'Pampa → S. Renard', '3 200 €', '256 €', 'Réglée', 'green'],
            ['2429-02', 'Préparation de copie, 3 titres', 'Encre Vive → M. Vasseur', '820 €', '66 €', 'Retard', 'orange'],
        ];
        $out = [];
        foreach ($items as [$num, $title, $parties, $montant, $commission, $statut, $tone]) {
            $out[] = ['num' => $num, 'title' => $title, 'parties' => $parties, 'montant' => $montant, 'commission' => $commission, 'statut' => $statut, 'statutStyle' => self::pill($tone)];
        }
        return $out;
    }

    private static function litiges(): array
    {
        $items = [
            ['LIT-2026-041', 'Rapport de lecture non livré', 'Éditions Pampa / Paul Ferrand', '640 €', '72 h dépassées', 'orange', '2477-01'],
            ['LIT-2026-040', 'Couverture soupçonnée générée par IA', 'Camille D. / Studio Halo', '480 €', 'IA', 'orange', '2468-03'],
            ['LIT-2026-038', 'Retard de 3 semaines sur l\'impression', 'Collectif Encre Vive / Imprimerie Baudry', '1 190 €', 'En échange', 'navy', '2455-02'],
            ['LIT-2026-035', 'Périmètre de correction contesté', 'Nora B. / Atelier Virgule', '260 €', 'Décision rédigée', 'grey', '2441-04'],
        ];
        $out = [];
        foreach ($items as $i => [$num, $title, $parties, $montant, $urgence, $tone, $commande]) {
            $on = $i === 0;
            $out[] = [
                'num' => $num, 'title' => $title, 'parties' => $parties, 'montant' => $montant, 'urgence' => $urgence, 'commande' => $commande,
                'row' => 'padding: 16px 18px; border-bottom: 1px solid #F2F5F8; cursor: pointer; background: ' . ($on ? '#FBFCFE' : '#FFF') . '; box-shadow: ' . ($on ? 'inset 3px 0 0 #D85D3F' : 'none') . ';',
                'urgenceStyle' => self::pill($tone),
            ];
        }
        return $out;
    }

    private static function decisions(): array
    {
        $items = [
            ['Règlement intégral au prestataire', 'Le livrable est conforme au brief contractuel.'],
            ['Répartition 50 / 50', 'Périmètre ambigu, responsabilité partagée.'],
            ['Remboursement intégral du client', 'Livrable non conforme ou manquement à la charte.'],
            ['Reprise du travail sous 10 jours', 'Le prestataire complète, sans supplément.'],
        ];
        $out = [];
        foreach ($items as $i => [$label, $note]) {
            $on = $i === 0;
            $out[] = [
                'label' => $label, 'note' => $note,
                'row' => 'display: flex; gap: 12px; align-items: flex-start; padding: 13px 15px; border: 1px solid ' . ($on ? self::NAVY : '#E8ECF1') . '; border-radius: 10px; cursor: pointer; background: ' . ($on ? '#FBFCFE' : '#FFF') . ';',
                'dot' => 'width: 16px; height: 16px; min-width: 16px; border-radius: 50%; margin-top: 2px; border: 1.5px solid ' . ($on ? self::NAVY : '#C3CEDA') . '; box-shadow: ' . ($on ? 'inset 0 0 0 3px #FFF, inset 0 0 0 8px #022746' : 'none') . ';',
            ];
        }
        return $out;
    }

    private static function avisSignales(): array
    {
        $items = [
            ['Contestation prestataire', 'navy', '1,0', 'il y a 4 h', 'Travail bâclé, délais non tenus, je déconseille.', 'Presse Rapide SARL', 'Marion Vasseur', 'commande 2418-02', 'La commande a été annulée par le client avant livraison, puis facturée. Aucun livrable n\'a été rendu : l\'avis ne porte pas sur une prestation réalisée.'],
            ['Soupçon de faux avis', 'orange', '5,0', 'hier', 'Parfait, rapide, je recommande vivement !', 'Compte créé le 24 août', 'Rapido Correction', 'commande 2471-05', 'Trois avis 5 étoiles déposés le même jour depuis la même adresse IP que le prestataire.'],
            ['Propos inappropriés', 'orange', '2,0', 'il y a 2 j', 'Avis contenant des propos personnels sur la prestataire, masqué en attente d\'examen.', 'Client anonymisé', 'Nora Belkacem', 'commande 2460-01', 'Signalé par la prestataire au titre de la charte : attaque personnelle sans lien avec la prestation.'],
            ['Contestation prestataire', 'navy', '3,0', 'il y a 3 j', 'Bon travail mais deux allers-retours de plus que prévu.', 'Éditions Pampa', 'Studio Grain', 'commande 2444-02', 'Le prestataire produit les échanges montrant que les allers-retours supplémentaires venaient de changements de brief côté client.'],
        ];
        $out = [];
        foreach ($items as [$motif, $tone, $note, $when, $txt, $auteur, $cible, $mission, $contestation]) {
            $out[] = ['motif' => $motif, 'note' => $note, 'when' => $when, 'txt' => $txt, 'auteur' => $auteur, 'cible' => $cible, 'mission' => $mission, 'contestation' => $contestation, 'motifStyle' => self::pill($tone)];
        }
        return $out;
    }

    private static function attente(): array
    {
        $items = [
            ['Claire Lemoine', 'claire.lemoine@mail.fr', 'Autrice', '24 août', 'Ouvert', 'green'],
            ['Éditions du Cardan', 'contact@cardan.fr', 'Éditeur', '24 août', 'Vague 4', 'navy'],
            ['Yann Prigent', 'yann.prigent@mail.fr', 'Imprimeur', '23 août', 'Vague 4', 'navy'],
            ['Salon du livre de Brest', 'orga@salonbrest.fr', 'Événement', '22 août', 'À qualifier', 'orange'],
            ['Amina Cherif', 'amina.cherif@mail.fr', 'Autrice', '22 août', 'Ouvert', 'green'],
            ['Librairie Ombres', 'bonjour@ombres.fr', 'Libraire', '21 août', 'Vague 4', 'navy'],
            ['Collectif Papier Bleu', 'collectif@papierbleu.fr', 'Collectif', '20 août', 'Ouvert', 'green'],
        ];
        $out = [];
        foreach ($items as [$name, $email, $profil, $when, $acces, $tone]) {
            $out[] = ['name' => $name, 'email' => $email, 'profil' => $profil, 'when' => $when, 'acces' => $acces, 'accesStyle' => self::pill($tone)];
        }
        return $out;
    }

    private static function articles(): array
    {
        $items = [
            ['Combien coûte vraiment la fabrication d\'un roman en autoédition ?', 'Tarifs', 'Léa Rousset', '8 420', 'Publié', 'green'],
            ['Cession de droits en illustration : les cinq lignes à ne pas oublier', 'Contrats', 'Léa Rousset', '3 180', 'Publié', 'green'],
            ['Préparation de copie ou correction : ce que vous achetez', 'Métier', 'Awa Diallo', '2 640', 'Publié', 'green'],
            ['Pourquoi nous interdisons l\'IA générative', 'Plateforme', 'Samuel Ohayon', '—', 'Relecture', 'orange'],
            ['Choisir son papier sans se ruiner', 'Fabrication', 'Léa Rousset', '—', 'Brouillon', 'grey'],
            ['Page « Tarifs et commission »', 'Page', 'Samuel Ohayon', '5 910', 'Publié', 'green'],
        ];
        $out = [];
        foreach ($items as [$title, $cat, $auteur, $vues, $statut, $tone]) {
            $out[] = ['title' => $title, 'cat' => $cat, 'auteur' => $auteur, 'vues' => $vues, 'statut' => $statut, 'statutStyle' => self::pill($tone)];
        }
        return $out;
    }

    private static function iaReglages(): array
    {
        $items = [
            ['Engagement obligatoire à l\'inscription', 'Bloque la création de compte prestataire sans signature.', true],
            ['Rappel au dépôt de contenu', 'Bandeau sur la création de prestation et le portfolio.', true],
            ['Retrait automatique au second signalement fondé', 'Suspension du profil en attente d\'examen.', true],
            ['Contrôle systématique des visuels à la publication', 'Coûteux en temps de modération : 4 h par jour environ.', false],
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

    private static function metiersReglage(): array
    {
        $labels = ['Auteurs', 'Illustrateurs', 'Correcteurs', 'Bêta-lecteurs', 'Traducteurs', 'Maquettistes', 'Éditeurs', 'Imprimeurs', 'Presse & com', 'Libraires', 'Narrateurs audio', 'Agents littéraires', 'Salons & événements'];
        $on = [true, true, true, true, true, true, true, true, true, true, true, false, false];
        $out = [];
        foreach ($labels as $i => $label) {
            $active = $on[$i];
            $out[] = [
                'label' => $label,
                'style' => 'border: 1px solid ' . ($active ? self::ORANGE : '#E1E7ED') . '; background: ' . ($active ? '#FDF3F0' : '#FFF') . '; color: ' . ($active ? self::ORANGE : '#8496A8') . '; border-radius: 999px; padding: 9px 15px; font-size: 14px;',
            ];
        }
        return $out;
    }
}
