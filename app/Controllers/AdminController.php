<?php

declare(strict_types=1);

namespace Adl\Controllers;

use Adl\Core\Auth;
use Adl\Core\Mailer;
use Adl\Core\OAuth;
use Adl\Core\Request;
use Adl\Core\View;
use Adl\Data\AdminCatalog;
use Adl\Data\Catalog;
use Adl\Models\Article;
use Adl\Models\Commission;
use Adl\Models\Conversation;
use Adl\Models\EmailTemplate;
use Adl\Models\Invoice;
use Adl\Models\Mission;
use Adl\Models\Order;
use Adl\Models\Profile;
use Adl\Models\Report;
use Adl\Models\Review;
use Adl\Models\Service;
use Adl\Models\Setting;
use Adl\Models\Taxonomy;
use Adl\Models\User;
use Throwable;

final class AdminController
{
    public function dashboard(Request $request): void
    {
        $pendingVerif = 0;
        $hiddenReviews = 0;
        try {
            $pendingVerif = Profile::countPendingVerification();
        } catch (Throwable) {
        }
        try {
            $hiddenReviews = count(array_filter(Review::all(), static fn (array $r): bool => !empty($r['hidden'])));
        } catch (Throwable) {
        }
        $openMissions = Mission::countOpen();
        $overdue = Invoice::countOverdue();
        $disputes = Order::countByStatus('dispute');

        $weeks = User::weeklySignups(8);
        $max = max(1, ...array_map(static fn (array $w): int => $w['ok'] + $w['pending'], $weeks));
        $chart = [];
        foreach ($weeks as $week) {
            $total = $week['ok'] + $week['pending'];
            $chart[] = [
                'label' => $week['label'],
                'ok' => $week['ok'],
                'pending' => $week['pending'],
                'okH' => (int) round(110 * $week['ok'] / $max),
                'pendingH' => (int) round(110 * $week['pending'] / $max),
                'empty' => $total === 0,
            ];
        }

        $activity = [];
        foreach (array_slice(User::all(), 0, 5) as $user) {
            $activity[] = [
                'when' => (string) ($user['created_at'] ?? ''),
                'txt' => User::displayName($user) . ' s’est inscrit',
                'meta' => User::roleLabel((string) ($user['role'] ?? 'client')),
            ];
        }
        foreach (array_slice(Order::recent(5), 0, 4) as $order) {
            $activity[] = [
                'when' => (string) ($order['created_at'] ?? ''),
                'txt' => 'Commande ' . ($order['num'] ?: '') . ' — ' . $order['title'],
                'meta' => $order['status_label'],
            ];
        }
        usort($activity, static fn (array $a, array $b): int => strcmp($b['when'], $a['when']));
        $activity = array_slice($activity, 0, 8);

        $this->page('dash', 'admin/dashboard', [
            'kpis' => [
                ['k' => 'Prestataires actifs', 'v' => format_int(User::countOfferers())],
                ['k' => 'Porteurs de projet', 'v' => format_int(User::countSeekers())],
                ['k' => 'Missions ouvertes', 'v' => format_int($openMissions)],
                ['k' => 'Commandes', 'v' => format_int(Order::countAll())],
                ['k' => 'Commission', 'v' => Commission::percent() . ' %'],
            ],
            'chart' => $chart,
            'files' => [
                ['label' => 'Profils à vérifier', 'n' => $pendingVerif, 'href' => '/admin/verifications', 'note' => 'dossiers prestataires'],
                ['label' => 'Missions ouvertes', 'n' => $openMissions, 'href' => '/admin/missions', 'note' => 'appels d’offres'],
                ['label' => 'Factures en retard', 'n' => $overdue, 'href' => '/admin/finances', 'note' => 'commissions échues'],
                ['label' => 'Litiges', 'n' => $disputes, 'href' => '/admin/litiges', 'note' => 'commandes en médiation'],
                ['label' => 'Avis masqués', 'n' => $hiddenReviews, 'href' => '/admin/avis', 'note' => 'retirés du public'],
                ['label' => 'Signalements', 'n' => self::reportCount(), 'href' => '/admin/moderation', 'note' => 'ouverts'],
            ],
            'activity' => $activity,
        ]);
    }

    public function verifications(Request $request): void
    {
        $filtre = $this->filtre($request, ['pending', 'verified', 'refused', 'tous'], 'pending');
        try {
            $dossiers = Profile::forAdmin($filtre === 'tous' ? 'tous' : $filtre);
        } catch (Throwable) {
            $dossiers = [];
        }
        $this->page('verif', 'admin/verifications', [
            'dossiers' => $dossiers,
            'filters' => $this->filterLinks('/admin/verifications', [
                'pending' => 'En attente',
                'verified' => 'Vérifiés',
                'refused' => 'Refusés',
                'tous' => 'Tous',
            ], $filtre),
        ]);
    }

    public function verificationFile(Request $request, string $id): void
    {
        Auth::requireAdmin();
        $profile = Profile::findByUser((int) $id);
        $path = trim((string) ($profile['verification_doc_path'] ?? ''));
        if ($path === '') {
            not_found('Justificatif introuvable.');
        }
        $name = trim((string) ($profile['verification_doc_name'] ?? '')) ?: 'justificatif';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimes = upload_mime_map();
        send_any_upload($path, $name, $mimes[$ext][0] ?? 'application/octet-stream');
    }

    public function verificationSave(Request $request, string $id): void
    {
        Auth::requireAdmin();
        try {
            Profile::setVerification((int) $id, $request->string('status'));
            flash('saved', 'Vérification enregistrée.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/admin/verifications');
    }

    public function moderation(Request $request): void
    {
        $reports = [];
        try {
            $reports = Report::all();
        } catch (Throwable) {
        }
        $this->page('moderation', 'admin/moderation', [
            'services' => Service::all(),
            'missions' => Mission::all(),
            'reports' => $reports,
        ]);
    }

    public function reportSave(Request $request, string $id): void
    {
        Auth::requireAdmin();
        try {
            Report::setStatus((int) $id, $request->string('status', 'closed'));
            flash('saved', 'Signalement mis à jour.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        $back = $request->string('back');
        redirect($back !== '' ? $back : '/admin/moderation');
    }

    public function conversationShow(Request $request, string $id): void
    {
        $thread = Conversation::findForAdmin((int) $id);
        if (!$thread) {
            not_found('Cette conversation est introuvable.');
        }
        $this->page('moderation', 'admin/conversation', [
            'title' => (string) ($thread['subject'] ?? 'Conversation'),
            'thread' => $thread,
            'messages' => $thread['messages'] ?? [],
        ]);
    }

    public function conversationFile(Request $request, string $id, string $mid): void
    {
        Auth::requireAdmin();
        try {
            $file = Conversation::attachmentForAdmin((int) $id, (int) $mid);
        } catch (Throwable $e) {
            not_found($e->getMessage());
        }
        send_private_file($file['path'], $file['name'], $file['mime']);
    }

    public function moderationSave(Request $request, string $type, string $id): void
    {
        Auth::requireAdmin();
        try {
            if ($type === 'prestation') {
                Service::setStatus((int) $id, $request->string('status'));
                flash('saved', 'Prestation mise à jour.');
            } elseif ($type === 'mission') {
                Mission::setStatus((int) $id, $request->string('status'));
                flash('saved', 'Mission mise à jour.');
            } else {
                flash('error', 'Type inconnu.');
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect($request->string('back') !== '' ? $request->string('back') : '/admin/moderation');
    }

    public function litiges(Request $request): void
    {
        $this->page('litiges', 'admin/litiges', [
            'litiges' => Order::byStatus('dispute'),
        ]);
    }

    public function litigeSave(Request $request, string $id): void
    {
        Auth::requireAdmin();
        try {
            $note = $request->string('note');
            if ($note !== '') {
                Order::setDisputeNote((int) $id, $note);
            }
            Order::setStatus((int) $id, $request->string('status'));
            flash('saved', 'Commande mise à jour.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        $back = $request->string('back');
        redirect($back !== '' ? $back : '/admin/litiges');
    }

    public function avis(Request $request): void
    {
        $this->page('avis', 'admin/avis', [
            'reviews' => Review::all(),
        ]);
    }

    public function avisSave(Request $request, string $id): void
    {
        Auth::requireAdmin();
        $action = $request->string('action');
        try {
            if ($action === 'hide') {
                Review::hide((int) $id, true);
                flash('saved', 'Avis masqué.');
            } elseif ($action === 'show') {
                Review::hide((int) $id, false);
                flash('saved', 'Avis rétabli.');
            } elseif ($action === 'delete') {
                Review::delete((int) $id);
                flash('saved', 'Avis supprimé.');
            } else {
                flash('error', 'Action inconnue.');
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/admin/avis');
    }

    public function utilisateurs(Request $request): void
    {
        $query = $request->string('q', '');
        $filtre = $this->filtre($request, ['tous', 'prestataires', 'porteurs', 'admins', 'suspendus'], 'tous');
        $accounts = User::search($query, $filtre);
        $n = count($accounts);
        $subtitle = format_int($n) . ' ' . ($n > 1 ? 'comptes' : 'compte');
        if ($query !== '') {
            $subtitle .= ' pour « ' . $query . ' »';
        }
        $this->page('users', 'admin/utilisateurs', [
            'query' => $query,
            'filtre' => $filtre,
            'accounts' => $accounts,
            'userFilters' => $this->filterLinks('/admin/utilisateurs', [
                'tous' => 'Tous',
                'prestataires' => 'Prestataires',
                'porteurs' => 'Porteurs de projet',
                'admins' => 'Administrateurs',
                'suspendus' => 'Suspendus',
            ], $filtre, $query !== '' ? ['q' => $query] : []),
            'usersSubtitle' => $subtitle,
        ]);
    }

    public function utilisateur(Request $request, string $id): void
    {
        Auth::requireAdmin();
        $account = User::find((int) $id);
        if (!$account) {
            flash('error', 'Utilisateur introuvable.');
            redirect('/admin/utilisateurs');
        }

        $isSelf = (int) $account['id'] === (int) (Auth::id() ?? 0);
        $onlyAdmin = ($account['role'] ?? '') === 'admin' && User::countAdmins() <= 1;
        $profile = null;
        try {
            $profile = Profile::findByUser((int) $account['id']);
        } catch (Throwable) {
            $profile = null;
        }

        $this->page('users', 'admin/utilisateur', [
            'title' => User::displayName($account),
            'account' => $account,
            'profile' => $profile,
            'isSelf' => $isSelf,
            'lockRole' => $isSelf || $onlyAdmin,
            'lockStatus' => $isSelf,
        ]);
    }

    public function utilisateurSave(Request $request, string $id): void
    {
        $actor = Auth::requireAdmin();
        try {
            User::updateAccess((int) $id, $request->string('role'), $request->string('status'), (int) $actor['id']);
            flash('saved', 'Compte mis à jour.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/admin/utilisateurs/' . (int) $id);
    }

    public function prestations(Request $request): void
    {
        $filtre = $this->filtre($request, ['tous', 'published', 'draft'], 'tous');
        $items = Service::all();
        if ($filtre !== 'tous') {
            $items = array_values(array_filter($items, static fn (array $s): bool => ($s['status'] ?? '') === $filtre));
        }
        $this->page('catalogue', 'admin/prestations', [
            'items' => $items,
            'filters' => $this->filterLinks('/admin/prestations', [
                'tous' => 'Toutes',
                'published' => 'En ligne',
                'draft' => 'Brouillons',
            ], $filtre),
        ]);
    }

    public function missions(Request $request): void
    {
        $filtre = $this->filtre($request, ['tous', 'open', 'assigned', 'closed', 'draft'], 'tous');
        $items = Mission::all();
        if ($filtre !== 'tous') {
            $items = array_values(array_filter($items, static fn (array $m): bool => ($m['status'] ?? '') === $filtre));
        }
        $this->page('missions', 'admin/missions', [
            'items' => $items,
            'filters' => $this->filterLinks('/admin/missions', [
                'tous' => 'Toutes',
                'open' => 'Ouvertes',
                'assigned' => 'Attribuées',
                'closed' => 'Clôturées',
                'draft' => 'Brouillons',
            ], $filtre),
        ]);
    }

    public function finances(Request $request): void
    {
        $orderTotal = Order::countAll();
        $orders = Order::recent(80);
        $invoices = Invoice::all();
        $this->page('finances', 'admin/finances', [
            'orders' => $orders,
            'orderTotal' => $orderTotal,
            'ordersTruncated' => $orderTotal > count($orders),
            'invoices' => $invoices,
            'kpis' => [
                ['k' => 'Volume d’affaires', 'v' => format_euros(Order::sumAmount())],
                ['k' => 'Commandes', 'v' => format_int(Order::countAll())],
                ['k' => 'Commission', 'v' => Commission::percent() . ' %'],
                ['k' => 'Factures en retard', 'v' => format_int(Invoice::countOverdue())],
            ],
        ]);
    }

    public function invoiceSave(Request $request, string $id): void
    {
        Auth::requireAdmin();
        try {
            $action = $request->string('action');
            if ($action === 'paid') {
                Invoice::markPaid((int) $id);
                flash('saved', 'Facture marquée comme réglée.');
            } else {
                flash('error', 'Action inconnue.');
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/admin/finances');
    }

    public function preOuverture(Request $request): void
    {
        $tradeCounts = Catalog::tradeCounts();
        $maxTrade = max(1, ...(array_values($tradeCounts) ?: [0]));
        $couverture = [];
        foreach ($tradeCounts as $metier => $n) {
            $couverture[] = [
                'metier' => $metier,
                'n' => $n,
                'pct' => (int) round(100 * $n / $maxTrade),
            ];
        }
        $this->page('preouverture', 'admin/preouverture', [
            'kpis' => [
                ['k' => 'Prestataires inscrits', 'v' => format_int(User::countOfferers())],
                ['k' => 'Porteurs de projet', 'v' => format_int(User::countSeekers())],
                ['k' => 'Prestations en ligne', 'v' => format_int(Service::countPublished())],
                ['k' => 'Métiers couverts', 'v' => count(array_filter($tradeCounts)) . ' / ' . count($tradeCounts)],
            ],
            'couverture' => $couverture,
        ]);
    }

    public function journal(Request $request): void
    {
        $this->page('cms', 'admin/journal', [
            'articles' => Article::all(),
        ]);
    }

    public function articleEdit(Request $request, string $id = 'nouveau'): void
    {
        Auth::requireAdmin();
        $article = $id === 'nouveau' ? [
            'id' => 0,
            'title' => '',
            'slug' => '',
            'category' => 'Journal',
            'excerpt' => '',
            'body' => '',
            'image_path' => '',
            'image_alt' => '',
            'img' => '',
            'published' => false,
        ] : Article::find((int) $id);
        if (!$article) {
            flash('error', 'Article introuvable.');
            redirect('/admin/journal');
        }
        $this->page('cms', 'admin/article', [
            'title' => $article['id'] ? (string) $article['title'] : 'Nouvel article',
            'article' => $article,
        ]);
    }

    public function articleSave(Request $request, string $id = 'nouveau'): void
    {
        Auth::requireAdmin();
        try {
            $payload = [
                'title' => $request->string('title'),
                'slug' => $request->string('slug'),
                'category' => $request->string('category'),
                'excerpt' => $request->string('excerpt'),
                'image_alt' => $request->string('image_alt'),
                'body' => $request->input('body', ''),
                'published' => $request->bool('published'),
            ];
            $file = $request->file('image');
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $payload['image_path'] = store_upload($file, 'journal', ['jpg', 'jpeg', 'png', 'webp'], 5 * 1024 * 1024);
            }
            $savedId = Article::save($id === 'nouveau' ? null : (int) $id, $payload);
            flash('saved', 'Article enregistré.');
            redirect('/admin/journal/' . $savedId);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect($id === 'nouveau' ? '/admin/journal/nouveau' : '/admin/journal/' . (int) $id);
        }
    }

    public function articleDelete(Request $request, string $id): void
    {
        Auth::requireAdmin();
        Article::delete((int) $id);
        flash('saved', 'Article supprimé.');
        redirect('/admin/journal');
    }

    public function reglages(Request $request): void
    {
        $this->page('reglages', 'admin/reglages', [
            'settings' => [
                'commission_percent' => (string) Commission::percent(),
                'founder_commission_percent' => (string) Commission::founderPercent(),
                'founder_limit' => (string) Commission::founderLimit(),
                'invoice_due_days' => (string) Commission::dueDays(),
            ],
        ]);
    }

    public function reglagesSave(Request $request): void
    {
        Auth::requireAdmin();
        $commission = max(0, min(100, (int) $request->string('commission_percent', '8')));
        $founder = max(0, min(100, (int) $request->string('founder_commission_percent', '6')));
        $limit = max(0, (int) $request->string('founder_limit', '100'));
        $due = max(1, (int) $request->string('invoice_due_days', '15'));
        Setting::set('commission_percent', (string) $commission);
        Setting::set('founder_commission_percent', (string) $founder);
        Setting::set('founder_limit', (string) $limit);
        Setting::set('invoice_due_days', (string) $due);
        flash('saved', 'Réglages enregistrés.');
        redirect('/admin/reglages');
    }

    public function listes(Request $request): void
    {
        Auth::requireAdmin();
        try {
            $trades = Taxonomy::list(Taxonomy::KIND_TRADE);
            $specialties = Taxonomy::list(Taxonomy::KIND_SPECIALTY);
        } catch (Throwable) {
            $trades = [];
            $specialties = [];
        }
        foreach ($trades as &$term) {
            $term['usage'] = Taxonomy::usageCount($term);
        }
        unset($term);
        foreach ($specialties as &$term) {
            $term['usage'] = Taxonomy::usageCount($term);
        }
        unset($term);

        $this->page('listes', 'admin/listes', [
            'trades' => $trades,
            'specialties' => $specialties,
        ]);
    }

    public function listesSave(Request $request): void
    {
        Auth::requireAdmin();
        $action = $request->string('action');
        $id = $request->int('id');

        try {
            if ($action === 'create') {
                Taxonomy::create(
                    $request->string('kind'),
                    $request->string('name'),
                    $request->bool('is_global')
                );
                flash('saved', 'Terme ajouté à la liste.');
            } elseif ($action === 'save' && $id) {
                Taxonomy::update(
                    $id,
                    $request->string('name'),
                    $request->bool('enabled'),
                    $request->bool('is_global')
                );
                flash('saved', 'Terme enregistré.');
            } elseif ($action === 'delete' && $id) {
                Taxonomy::delete($id);
                flash('saved', 'Terme supprimé.');
            } elseif (($action === 'up' || $action === 'down') && $id) {
                Taxonomy::move($id, $action);
                flash('saved', 'Ordre mis à jour.');
            } else {
                flash('error', 'Action inconnue.');
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('/admin/listes');
    }

    public function smtp(Request $request): void
    {
        $this->page('smtp', 'admin/smtp', [
            'settings' => Mailer::config(),
            'tested' => flash('tested'),
        ]);
    }

    public function smtpSave(Request $request): void
    {
        Auth::requireAdmin();
        foreach (['mail_host', 'mail_port', 'mail_username', 'mail_encryption', 'mail_from_address', 'mail_from_name'] as $key) {
            Setting::set($key, $request->string($key, ''));
        }
        $password = $request->string('mail_password', '');
        if ($password !== '') {
            Setting::set('mail_password', $password);
        }
        flash('saved', true);
        redirect('/admin/smtp');
    }

    public function smtpTest(Request $request): void
    {
        Auth::requireAdmin();
        $to = $request->string('test_email', Auth::user()['email'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Indiquez une adresse e-mail valide pour le test.');
            redirect('/admin/smtp');
        }
        try {
            Mailer::send($to, 'Test SMTP — Acteurs du Livre', '<p>Ceci est un e-mail de test envoyé depuis l\'administration.</p>');
            if (!Mailer::usesSmtp()) {
                flash('error', 'Aucun hôte SMTP n\'est configuré : le message a été écrit dans storage/mail, il n\'a pas été envoyé.');
            } else {
                flash('tested', 'E-mail de test envoyé vers ' . $to . '. Vérifiez la boîte de réception (et les indésirables).');
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/admin/smtp');
    }

    public function sso(Request $request): void
    {
        $this->page('sso', 'admin/sso', [
            'sso' => OAuth::adminSnapshot(),
            'warning' => flash('warning'),
        ]);
    }

    public function ssoSave(Request $request): void
    {
        Auth::requireAdmin();
        Setting::set('oauth_enabled', $request->bool('oauth_enabled') ? '1' : '0');
        Setting::set('oauth_google_enabled', $request->bool('oauth_google_enabled') ? '1' : '0');
        Setting::set('oauth_facebook_enabled', $request->bool('oauth_facebook_enabled') ? '1' : '0');
        foreach (['google_client_id', 'facebook_app_id'] as $key) {
            Setting::set($key, $request->string($key, ''));
        }
        foreach (['google_client_secret', 'facebook_app_secret'] as $key) {
            $secret = $request->string($key, '');
            if ($secret !== '') {
                Setting::set($key, $secret);
            }
        }

        $notes = [];
        if ($request->bool('oauth_enabled')) {
            foreach (OAuth::PROVIDERS as $provider) {
                $flag = $provider === 'facebook' ? 'oauth_facebook_enabled' : 'oauth_google_enabled';
                if ($request->bool($flag) && !OAuth::hasCredentials($provider)) {
                    $notes[] = OAuth::label($provider) . ' est activé mais les clés sont incomplètes : le bouton n’apparaîtra pas.';
                }
            }
            if (!$request->bool('oauth_google_enabled') && !$request->bool('oauth_facebook_enabled')) {
                $notes[] = 'Le SSO est activé, mais ni Google ni Facebook ne le sont. Cochez au moins un fournisseur.';
            }
        }
        flash('saved', true);
        if ($notes !== []) {
            flash('warning', implode(' ', $notes));
        }
        redirect('/admin/sso');
    }

    public function emails(Request $request): void
    {
        $this->page('emails', 'admin/emails', [
            'templates' => EmailTemplate::all(),
        ]);
    }

    public function emailEdit(Request $request, string $id): void
    {
        Auth::requireAdmin();
        $template = EmailTemplate::find((int) $id);
        if (!$template) {
            redirect('/admin/emails');
        }
        $this->page('emails', 'admin/email-edit', [
            'title' => $template['name'],
            'template' => $template,
        ]);
    }

    public function emailSave(Request $request, string $id): void
    {
        Auth::requireAdmin();
        EmailTemplate::update((int) $id, $request->string('subject'), $request->input('body_html', ''));
        flash('saved', true);
        redirect('/admin/emails');
    }

    private static function reportCount(): int
    {
        try {
            return Report::countOpen();
        } catch (Throwable) {
            return 0;
        }
    }

    private function page(string $screen, string $view, array $extra = []): void
    {
        Auth::requireAdmin();
        View::render($view, AdminCatalog::forScreen($screen, array_merge([
            'saved' => flash('saved'),
            'error' => flash('error'),
        ], $extra)), 'layouts/admin');
    }

    /** @param list<string> $allowed */
    private function filtre(Request $request, array $allowed, string $default): string
    {
        $filtre = $request->string('filtre', $default);
        return in_array($filtre, $allowed, true) ? $filtre : $default;
    }

    /**
     * @param array<string, string> $labels
     * @param array<string, string> $keep
     * @return list<array{id: string, label: string, href: string, on: bool}>
     */
    private function filterLinks(string $base, array $labels, string $current, array $keep = []): array
    {
        $out = [];
        foreach ($labels as $id => $label) {
            $params = $keep;
            if ($id !== array_key_first($labels)) {
                $params['filtre'] = $id;
            }
            $out[] = [
                'id' => $id,
                'label' => $label,
                'href' => $base . ($params === [] ? '' : '?' . http_build_query($params)),
                'on' => $id === $current,
            ];
        }
        return $out;
    }
}
