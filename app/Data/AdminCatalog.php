<?php

declare(strict_types=1);

namespace Adl\Data;

use Adl\Core\Auth;
use Adl\Models\Article;
use Adl\Models\Mission;
use Adl\Models\Order;
use Adl\Models\Profile;
use Adl\Models\Service;
use Adl\Models\Setting;
use Adl\Models\User;
use DateTimeImmutable;
use DateTimeZone;

final class AdminCatalog
{
    public const NAVY = '#15212f';
    public const ORANGE = '#D85D3F';

    public static function forScreen(string $screen, array $extra = []): array
    {
        $user = Auth::user();
        $data = array_merge(self::shared($user, $screen, $extra['query'] ?? ''), self::content(), self::liveOverlay(), $extra);
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
            ['verif', 'Vérifications', '', '', '/admin/verifications'],
            ['moderation', 'Modération', '', '', '/admin/moderation'],
            ['litiges', 'Litiges', '', '', '/admin/litiges'],
            ['avis', 'Avis signalés', '', '', '/admin/avis'],
            ['users', 'Utilisateurs', '', 'Données', '/admin/utilisateurs'],
            ['catalogue', 'Prestations', '', '', '/admin/prestations'],
            ['missions', 'Appels d\'offres', '', '', '/admin/missions'],
            ['finances', 'Commandes & finances', '', '', '/admin/finances'],
            ['preouverture', 'Pré-ouverture', '', 'Plateforme', '/admin/pre-ouverture'],
            ['cms', 'Journal & pages', '', '', '/admin/journal'],
            ['reglages', 'Réglages', '', '', '/admin/reglages'],
            ['listes', 'Métiers & spécialités', '', '', '/admin/listes'],
            ['smtp', 'SMTP', '', '', '/admin/smtp'],
            ['sso', 'Connexion Google / Facebook', '', '', '/admin/sso'],
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
            'adminName' => $user ? User::displayName($user) : 'Administration',
            'adminInitials' => $user ? User::initials($user) : 'AD',
            'adminRole' => ($user['role'] ?? '') === 'admin' ? 'Administration · accès complet' : 'Modération',
            'adminCountdown' => 'Pré-ouverture · ouverture clients en octobre 2026',
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
            'listes' => 'Métiers & spécialités',
            'smtp' => 'Paramètres SMTP',
            'sso' => 'Connexion Google / Facebook',
            'emails' => 'Modèles d\'e-mails',
        ];
    }

    private static function content(): array
    {
        $emptyKpi = 'border-radius: 12px; padding: 18px; background: #FFF; border: 1px solid #E8ECF1; color: #022746;';

        return array_merge(self::emptyWorkflow(), [
            'kpis' => [
                ['k' => 'Prestataires actifs', 'v' => '0', 'd' => 'aucun compte', 'deltaStyle' => 'font-size: 13px; margin-top: 4px; color: #66768A;'],
                ['k' => 'Missions ouvertes', 'v' => '0', 'd' => 'aucun appel d\'offres', 'deltaStyle' => 'font-size: 13px; margin-top: 4px; color: #66768A;'],
                ['k' => 'Commandes', 'v' => '0', 'd' => 'aucun volume', 'deltaStyle' => 'font-size: 13px; margin-top: 4px; color: #66768A;'],
                ['k' => 'Commission', 'v' => '8 %', 'd' => 'réglage actuel', 'deltaStyle' => 'font-size: 13px; margin-top: 4px; color: #66768A;'],
                ['k' => 'Porteurs de projet', 'v' => '0', 'd' => 'aucun compte', 'deltaStyle' => 'font-size: 13px; margin-top: 4px; color: #66768A;'],
            ],
            'files' => self::queueFiles(0),
            'userFilters' => self::chips(['Tous', 'Prestataires', 'Porteurs de projet', 'Suspendus'], 0),
            'finKpis' => [
                ['k' => 'Volume d\'affaires', 'v' => '0 €', 'note' => 'aucune commande', 'card' => $emptyKpi],
                ['k' => 'Commission', 'v' => '8 %', 'note' => 'taux actuel', 'card' => 'border-radius: 12px; padding: 18px; background: #022746; color: #E4EDF5;'],
                ['k' => 'Prestations en ligne', 'v' => '0', 'note' => 'catalogue public', 'card' => $emptyKpi],
                ['k' => 'Missions ouvertes', 'v' => '0', 'note' => 'appels d\'offres', 'card' => $emptyKpi],
            ],
            'preKpis' => [
                ['k' => 'Prestataires inscrits', 'v' => '0', 'd' => 'aucun compte'],
                ['k' => 'Porteurs de projet', 'v' => '0', 'd' => 'aucun compte'],
                ['k' => 'Prestations en ligne', 'v' => '0', 'd' => 'catalogue public'],
                ['k' => 'Métiers couverts', 'v' => '0 / ' . count(Profile::TRADES), 'd' => 'profils publiés'],
            ],
            'preFilters' => self::chips(['Tous', 'Prestataires', 'Porteurs de projet'], 0),
            'reglagesNav' => self::sideNav(['Commission', 'Politique IA', 'Métiers', 'Modération', 'Équipe & droits', 'SMTP & e-mails'], 0),
            'reglagesTitle' => 'Commission',
            'commissionRows' => [
                ['niveau' => 'Première mission', 'pct' => '0 %', 'seuil' => 'offerte'],
                ['niveau' => 'Missions suivantes', 'pct' => '8 %', 'seuil' => 'à partir de la 2ᵉ'],
            ],
            'iaReglages' => self::iaReglages(),
            'metiersReglage' => self::metiersReglage(),
        ]);
    }

    /** @return array<string, mixed> */
    private static function emptyWorkflow(): array
    {
        return [
            'chart' => [],
            'activite' => [],
            'dossiers' => [],
            'signalements' => [],
            'users' => [],
            'catalogue' => [],
            'missions' => [],
            'commandes' => [],
            'impayes' => [],
            'commissionMetier' => [],
            'litiges' => [],
            'avisSignales' => [],
            'attente' => [],
            'couverture' => [],
            'articles' => [],
            'verifFilters' => self::chips(['Tous', 'Priorité', 'Relances', 'Refus proposés'], 0),
            'modFilters' => self::chips(['Tout', 'IA générative', 'Hors plateforme', 'Droits & plagiat', 'Autre'], 0),
            'dossierName' => '',
            'dossierRole' => '',
            'dossierInitials' => '',
            'dossierAvatar' => '',
            'dossierMeta' => [],
            'dossierPieces' => [],
            'controles' => [],
            'hasDossier' => false,
            'emptyDossier' => 'Aucun dossier à instruire pour le moment.',
            'litigeNum' => '',
            'litigeTitle' => '',
            'litigeParties' => '',
            'litigeMontant' => '',
            'litigeCommande' => '',
            'litigeTimeline' => [],
            'decisions' => [],
            'hasLitige' => false,
            'emptyLitige' => 'Aucun litige ouvert pour le moment.',
            'iaSignalementsCount' => '0',
            'iaSignalementsNote' => 'Aucun contenu soupçonné d\'avoir été généré par une IA.',
            'hasIaSignalements' => false,
            'usersSubtitle' => 'Aucun utilisateur',
            'dashSubtitle' => 'Pilotage de la plateforme',
            'verifSubtitle' => 'Aucun dossier en attente.',
            'moderationSubtitle' => 'Aucun signalement en file.',
            'litigesSubtitle' => 'Aucun litige ouvert.',
            'avisSubtitle' => 'Un avis n\'est publiable qu\'après une mission facturée. Aucune contestation en attente.',
            'catalogueSubtitle' => 'Aucune prestation.',
            'missionsSubtitle' => 'Aucun appel d\'offres.',
            'financesSubtitle' => 'Montants hors taxes',
            'preouvertureSubtitle' => 'Ouverture aux clients prévue en octobre 2026.',
            'cmsSubtitle' => 'Aucun article.',
            'emptyChart' => 'Pas encore d\'historique d\'inscriptions à afficher.',
            'emptyActivite' => 'Aucune activité récente.',
            'emptyDossiers' => 'Aucun dossier en attente de vérification.',
            'emptySignalements' => 'Aucun contenu signalé pour le moment.',
            'emptyUsers' => 'Aucun utilisateur pour le moment.',
            'emptyCatalogue' => 'Aucune prestation enregistrée.',
            'emptyMissions' => 'Aucun appel d\'offres pour le moment.',
            'emptyCommandes' => 'Aucune commande pour le moment.',
            'emptyImpayes' => 'Aucun impayé à relancer.',
            'emptyCommissionMetier' => 'Pas encore de commission ventilée par métier.',
            'emptyLitiges' => 'Aucun litige ouvert.',
            'emptyAvis' => 'Aucune contestation d\'avis en attente.',
            'emptyAttente' => 'Personne sur la liste d\'attente pour le moment.',
            'emptyArticles' => 'Aucun article pour le moment.',
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
            $href = $label === 'SMTP & e-mails' ? '/admin/smtp' : ($label === 'Connexion sociale' ? '/admin/sso' : '#');
            $out[] = [
                'label' => $label,
                'href' => $href,
                'style' => 'padding: 11px 13px; border-radius: 9px; font-size: 14px; cursor: pointer; background: ' . ($on ? '#F4F6F9' : 'transparent') . '; color: ' . ($on ? self::NAVY : '#66768A') . '; font-weight: ' . ($on ? '500' : '400') . ';',
            ];
        }
        return $out;
    }

    /** @return list<array<string, mixed>> */
    private static function queueFiles(int $openMissions): array
    {
        return [
            ['label' => 'Profils à vérifier', 'note' => 'justificatif d\'activité et engagement IA', 'n' => 0, 'age' => '—', 'sla' => 'Aucun dossier', 'slaStyle' => self::pill('grey'), 'href' => '/admin/verifications'],
            ['label' => 'Contenus signalés', 'note' => 'modération éditoriale', 'n' => 0, 'age' => '—', 'sla' => 'Aucun signalement', 'slaStyle' => self::pill('grey'), 'href' => '/admin/moderation'],
            ['label' => 'Missions ouvertes', 'note' => 'appels d\'offres publics', 'n' => $openMissions, 'age' => '—', 'sla' => $openMissions > 0 ? 'En ligne' : 'Aucune mission', 'slaStyle' => self::pill($openMissions > 0 ? 'green' : 'grey'), 'href' => '/admin/missions'],
            ['label' => 'Litiges ouverts', 'note' => 'médiation', 'n' => 0, 'age' => '—', 'sla' => 'Aucun litige', 'slaStyle' => self::pill('grey'), 'href' => '/admin/litiges'],
            ['label' => 'Avis contestés', 'note' => 'contestation par le prestataire', 'n' => 0, 'age' => '—', 'sla' => 'Aucun avis', 'slaStyle' => self::pill('grey'), 'href' => '/admin/avis'],
        ];
    }

    private static function iaReglages(): array
    {
        $items = [
            ['Engagement obligatoire à l\'inscription', 'Bloque la création de compte prestataire sans signature.', true],
            ['Rappel au dépôt de contenu', 'Bandeau sur la création de prestation et le portfolio.', true],
            ['Retrait automatique au second signalement fondé', 'Suspension du profil en attente d\'examen.', true],
            ['Contrôle systématique des visuels à la publication', 'Chaque visuel est examiné avant mise en ligne.', false],
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
        $out = [];
        foreach (Profile::TRADES as $label) {
            $out[] = [
                'label' => $label,
                'style' => 'border: 1px solid ' . self::ORANGE . '; background: #FDF3F0; color: ' . self::ORANGE . '; border-radius: 999px; padding: 9px 15px; font-size: 14px;',
            ];
        }
        return $out;
    }

    private static function qty(int $n, string $one, string $many): string
    {
        return format_int($n) . ' ' . ($n > 1 ? $many : $one);
    }

    private static function monthLabel(DateTimeImmutable $date): string
    {
        $months = [1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril', 5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août', 9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'];
        return $months[(int) $date->format('n')] . ' ' . $date->format('Y');
    }

    private static function weekLabel(): string
    {
        $tz = new DateTimeZone('Europe/Paris');
        $monday = new DateTimeImmutable('monday this week', $tz);
        $sunday = $monday->modify('+6 days');
        $months = [1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril', 5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août', 9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'];
        $start = (int) $monday->format('j');
        $end = (int) $sunday->format('j');
        $monthEnd = $months[(int) $sunday->format('n')];
        $year = $sunday->format('Y');
        if ((int) $monday->format('n') !== (int) $sunday->format('n')) {
            return 'Semaine du ' . $start . ' ' . $months[(int) $monday->format('n')] . ' au ' . $end . ' ' . $monthEnd . ' ' . $year;
        }
        return 'Semaine du ' . $start . ' au ' . $end . ' ' . $monthEnd . ' ' . $year;
    }

    /** @return array<string, mixed> */
    private static function liveOverlay(): array
    {
        try {
            $users = User::all();
            $offerers = User::countOfferers();
            $seekers = User::countSeekers();
            $openMissions = Mission::countOpen();
            $services = Service::all();
            $publishedServices = array_values(array_filter($services, static fn (array $s): bool => ($s['status'] ?? '') === 'published'));
            $missions = Mission::all();
            $orders = Order::recent(40);
            $articles = Article::all();
            $commission = Setting::get('commission_percent', '8') ?: '8';
            $volume = Order::sumAmount();
            $orderCount = Order::countAll();
            $tradeCounts = Catalog::tradeCounts();
            $covered = count(array_filter($tradeCounts, static fn (int $n): bool => $n > 0));
            $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));

            $userRows = [];
            foreach ($users as $user) {
                $initials = User::initials($user);
                $status = (string) ($user['status'] ?? 'active');
                $tone = $status === 'active' ? 'green' : ($status === 'suspended' ? 'orange' : 'navy');
                $userRows[] = [
                    'name' => User::displayName($user),
                    'initials' => $initials,
                    'email' => $user['email'],
                    'metier' => User::usageLabel($user),
                    'niveau' => $user['role'] === 'admin' ? 'Admin' : 'Compte',
                    'missions' => '—',
                    'note' => '—',
                    'statut' => $status === 'active' ? 'Actif' : ($status === 'suspended' ? 'Suspendu' : 'En attente'),
                    'avatar' => avatar_style($initials, 30),
                    'statutStyle' => self::pill($tone),
                ];
            }

            $catalogue = [];
            foreach ($services as $service) {
                $catalogue[] = [
                    'title' => $service['title'],
                    'metier' => $service['cat'],
                    'by' => $service['by'],
                    'prix' => $service['price'],
                    'vues' => '—',
                    'cmd' => '—',
                    'statut' => $service['status_label'],
                    'statutStyle' => self::pill(($service['status'] ?? '') === 'published' ? 'green' : 'grey'),
                ];
            }

            $missionRows = [];
            foreach ($missions as $mission) {
                $missionRows[] = [
                    'title' => $mission['title'],
                    'by' => $mission['by'],
                    'metier' => $mission['category_name'] ?? '',
                    'budget' => $mission['budget'],
                    'candidatures' => $mission['applicants'],
                    'when' => $mission['when'],
                    'flag' => '',
                    'flagStyle' => 'display: none;',
                ];
            }

            $orderRows = [];
            foreach ($orders as $order) {
                $orderRows[] = [
                    'num' => $order['num'],
                    'title' => $order['title'],
                    'parties' => $order['parties'],
                    'montant' => $order['amount_label'],
                    'commission' => '—',
                    'statut' => $order['status_label'],
                    'statutStyle' => self::pill($order['status_tone']),
                ];
            }

            $articleRows = [];
            $publishedN = 0;
            $draftN = 0;
            foreach ($articles as $article) {
                if (!empty($article['published'])) {
                    $publishedN++;
                } else {
                    $draftN++;
                }
                $articleRows[] = [
                    'title' => $article['title'],
                    'cat' => $article['cat'],
                    'auteur' => 'Rédaction',
                    'vues' => '—',
                    'statut' => $article['status'],
                    'statutStyle' => self::pill(!empty($article['published']) ? 'green' : 'grey'),
                ];
            }

            $maxTrade = max(1, ...array_values($tradeCounts));
            $couverture = [];
            foreach ($tradeCounts as $metier => $n) {
                $pct = (int) round(100 * $n / $maxTrade);
                $couverture[] = [
                    'metier' => $metier,
                    'n' => format_int($n),
                    'color' => $n === 0 ? '#8496A8' : '#022746',
                    'bar' => 'height: 100%; width: ' . $pct . '%; background: ' . ($n === 0 ? '#DCE3EA' : '#022746') . ';',
                ];
            }

            $cmsSubtitle = self::qty($publishedN, 'article publié', 'articles publiés');
            if ($draftN > 0) {
                $cmsSubtitle .= ' · ' . self::qty($draftN, 'brouillon', 'brouillons');
            }

            $kpiNote = 'font-size: 13px; margin-top: 4px; color: #66768A;';
            $card = 'border-radius: 12px; padding: 18px; background: #FFF; border: 1px solid #E8ECF1; color: #022746;';

            return [
                'kpis' => [
                    ['k' => 'Prestataires actifs', 'v' => format_int($offerers), 'd' => self::qty(count($users), 'compte', 'comptes'), 'deltaStyle' => $kpiNote],
                    ['k' => 'Missions ouvertes', 'v' => format_int($openMissions), 'd' => self::qty(count($missions), 'au total', 'au total'), 'deltaStyle' => $kpiNote],
                    ['k' => 'Commandes', 'v' => format_int($orderCount), 'd' => format_euros($volume), 'deltaStyle' => $kpiNote],
                    ['k' => 'Commission', 'v' => $commission . ' %', 'd' => 'réglage actuel', 'deltaStyle' => $kpiNote],
                    ['k' => 'Porteurs de projet', 'v' => format_int($seekers), 'd' => 'comptes actifs', 'deltaStyle' => $kpiNote],
                ],
                'users' => $userRows,
                'usersSubtitle' => self::qty($offerers, 'prestataire', 'prestataires') . ' · ' . self::qty($seekers, 'porteur de projet', 'porteurs de projet'),
                'catalogue' => $catalogue,
                'missions' => $missionRows,
                'commandes' => $orderRows,
                'articles' => $articleRows,
                'files' => self::queueFiles($openMissions),
                'finKpis' => [
                    ['k' => 'Volume d\'affaires', 'v' => format_euros($volume), 'note' => self::qty($orderCount, 'commande', 'commandes'), 'card' => $card],
                    ['k' => 'Commission', 'v' => $commission . ' %', 'note' => 'taux actuel', 'card' => 'border-radius: 12px; padding: 18px; background: #022746; color: #E4EDF5;'],
                    ['k' => 'Prestations en ligne', 'v' => format_int(count($publishedServices)), 'note' => 'catalogue public', 'card' => $card],
                    ['k' => 'Missions ouvertes', 'v' => format_int($openMissions), 'note' => 'appels d\'offres', 'card' => $card],
                ],
                'preKpis' => [
                    ['k' => 'Prestataires inscrits', 'v' => format_int($offerers), 'd' => self::qty(count($users), 'compte', 'comptes')],
                    ['k' => 'Porteurs de projet', 'v' => format_int($seekers), 'd' => 'comptes actifs'],
                    ['k' => 'Prestations en ligne', 'v' => format_int(count($publishedServices)), 'd' => 'catalogue public'],
                    ['k' => 'Métiers couverts', 'v' => $covered . ' / ' . count(Profile::TRADES), 'd' => 'profils publiés'],
                ],
                'couverture' => $couverture,
                'commissionRows' => [
                    ['niveau' => 'Première mission', 'pct' => '0 %', 'seuil' => 'offerte'],
                    ['niveau' => 'Missions suivantes', 'pct' => $commission . ' %', 'seuil' => 'à partir de la 2ᵉ'],
                ],
                'dashSubtitle' => self::weekLabel() . ' · fuseau Europe/Paris',
                'verifSubtitle' => 'Aucun dossier en attente.',
                'moderationSubtitle' => 'Aucun signalement en file.',
                'litigesSubtitle' => 'Aucun litige ouvert.',
                'avisSubtitle' => 'Un avis n\'est publiable qu\'après une mission facturée. Aucune contestation en attente.',
                'catalogueSubtitle' => count($services) === 0
                    ? 'Aucune prestation.'
                    : self::qty(count($publishedServices), 'prestation en ligne', 'prestations en ligne')
                    . (count($services) > count($publishedServices) ? ' · ' . self::qty(count($services) - count($publishedServices), 'brouillon', 'brouillons') : ''),
                'missionsSubtitle' => count($missions) === 0
                    ? 'Aucun appel d\'offres.'
                    : self::qty($openMissions, 'mission ouverte', 'missions ouvertes')
                    . (count($missions) !== $openMissions ? ' · ' . self::qty(count($missions), 'au total', 'au total') : ''),
                'financesSubtitle' => self::monthLabel($now) . ' · montants hors taxes',
                'preouvertureSubtitle' => 'Ouverture aux clients prévue en octobre 2026.',
                'cmsSubtitle' => ($publishedN + $draftN) === 0 ? 'Aucun article.' : $cmsSubtitle,
                'emptyUsers' => $userRows === [] ? 'Aucun utilisateur pour le moment.' : '',
                'emptyCatalogue' => $catalogue === [] ? 'Aucune prestation enregistrée.' : '',
                'emptyMissions' => $missionRows === [] ? 'Aucun appel d\'offres pour le moment.' : '',
                'emptyCommandes' => $orderRows === [] ? 'Aucune commande pour le moment.' : '',
                'emptyArticles' => $articleRows === [] ? 'Aucun article pour le moment.' : '',
                'emptyCouverture' => $couverture === [] ? 'Aucun profil publié pour l\'instant.' : '',
            ];
        } catch (\Throwable) {
            return self::emptyWorkflow();
        }
    }
}
