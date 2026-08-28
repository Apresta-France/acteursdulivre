<?php

declare(strict_types=1);

namespace Adl\Controllers;

use Adl\Core\Auth;
use Adl\Core\Request;
use Adl\Core\View;
use Adl\Data\Catalog;
use Adl\Data\Onboarding;
use Adl\Models\Application;
use Adl\Models\Commission;
use Adl\Models\Favorite;
use Adl\Models\Invoice;
use Adl\Models\Mission;
use Adl\Models\Notification;
use Adl\Models\Order;
use Adl\Models\PortfolioItem;
use Adl\Models\Profile;
use Adl\Models\Review;
use Adl\Models\Service;
use Adl\Models\User;

final class AccountController
{
    public function dashboard(Request $request): void
    {
        $user = Auth::requireUser();
        $missionCount = 0;
        $openMissionCount = 0;
        $profileCompletion = 0;
        $profileHref = '';

        try {
            if (User::seeksServices($user)) {
                $missions = Mission::forUser((int) $user['id']);
                $missionCount = count($missions);
                $openMissionCount = count(array_filter(
                    $missions,
                    static fn (array $m): bool => ($m['status'] ?? '') === 'open'
                ));
            }
        } catch (\Throwable) {
        }

        $availabilityStatus = Profile::STATUS_AVAILABLE;
        $availabilityNote = '';
        $profile = null;
        $billing = ['block' => null, 'warning' => null];

        try {
            if (User::offersServices($user)) {
                $profile = Profile::findByUser((int) $user['id']);
                if ($profile) {
                    $profileCompletion = (int) ($profile['completion'] ?? 0);
                    $profileHref = Profile::publicHref($profile);
                    $availabilityStatus = Profile::normalizeStatus($profile['availability_status'] ?? null);
                    $availabilityNote = trim((string) ($profile['availability'] ?? ''));
                }
                $billing = self::billingState((int) $user['id']);
            }
        } catch (\Throwable) {
        }

        View::page('dashboard', [
            'title' => 'Tableau de bord',
            'error' => flash('error'),
            'saved' => flash('saved'),
            'missionCount' => $missionCount,
            'openMissionCount' => $openMissionCount,
            'profileCompletion' => $profileCompletion,
            'profileHref' => $profileHref,
            'availabilityStatus' => $availabilityStatus,
            'availabilityNote' => $availabilityNote,
            'onboardingPending' => Onboarding::isPending($user),
            'onboardingPriorities' => Onboarding::isPending($user)
                ? Onboarding::priorities($user, $profile ?? null, $missionCount)
                : [],
            'billingBlock' => $billing['block'],
            'billingWarning' => $billing['warning'],
            'isFounder' => User::isFounder($user),
        ]);
    }

    public function onboarding(Request $request): void
    {
        $user = Auth::requireUser();
        $ctx = Onboarding::context($user);
        $step = Onboarding::resolveStep($user, $request->string('etape'), $ctx['plan']);
        $coach = Onboarding::coach($step, $user, $ctx['profile'], $ctx['missionCount']);

        View::page('bienvenue', [
            'title' => $coach['title'],
            'step' => $step,
            'plan' => $ctx['plan'],
            'coach' => $coach,
            'profile' => $ctx['profile'] ?? [],
            'prenom' => (string) ($user['first_name'] ?? ''),
            'nom' => (string) ($user['last_name'] ?? ''),
            'missions' => $ctx['missions'],
            'missionCount' => $ctx['missionCount'],
            'priorities' => $ctx['priorities'],
            'trades' => Catalog::trades(),
            'titleHints' => Onboarding::TITLE_HINTS,
            'presentationHints' => Onboarding::PRESENTATION_HINTS,
            'suggestions' => array_values(array_filter(
                Catalog::suggestionsForTrade(
                    (string) (($ctx['profile']['trades'][0] ?? null) ?: 'Correction')
                ),
                static function (array $item) use ($ctx): bool {
                    $slug = (string) ($ctx['profile']['slug'] ?? '');
                    return $slug === '' || ($item['href'] ?? '') !== '/prestataires/' . $slug;
                }
            )),
            'old' => flash('old') ?: [],
            'error' => flash('error'),
            'saved' => flash('saved'),
        ]);
    }

    public function onboardingSave(Request $request): void
    {
        $user = Auth::requireUser();
        $ctx = Onboarding::context($user);
        $step = Onboarding::resolveStep($user, $request->string('etape'), $ctx['plan']);
        $intent = $request->string('intent', 'continue');

        if ($intent === 'later' || $step === 'apercu') {
            User::markOnboardingDone((int) $user['id']);
            if ($intent === 'later') {
                flash('saved', 'Vous pourrez reprendre ces étapes depuis votre tableau de bord.');
            }
            redirect('/espace');
        }

        try {
            if ($intent !== 'skip') {
                if ($step === 'identite') {
                    $this->saveOnboardingIdentity($request, $user);
                } elseif ($step === 'vitrine') {
                    $this->saveOnboardingVitrine($request, $user);
                } elseif ($step === 'mission') {
                    $this->saveOnboardingMission($request, $user);
                }
            }
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            flash('old', $request->all());
            redirect('/espace/bienvenue?etape=' . rawurlencode($step));
        }

        $next = Onboarding::nextStep($step, $ctx['plan']);
        if ($next === null) {
            User::markOnboardingDone((int) $user['id']);
            redirect('/espace');
        }
        redirect('/espace/bienvenue?etape=' . rawurlencode($next));
    }

    /** @param array<string, mixed> $user */
    private function saveOnboardingIdentity(Request $request, array $user): void
    {
        $first = $request->string('first_name', (string) $user['first_name']);
        $last = $request->string('last_name', (string) $user['last_name']);
        if ($first === '' || $last === '') {
            throw new \RuntimeException('Indiquez votre prénom et votre nom.');
        }

        User::update((int) $user['id'], [
            'first_name' => $first,
            'last_name' => $last,
        ]);
        User::storeAvatar((int) $user['id'], $request->file('avatar'));

        if (User::offersServices($user)) {
            Profile::patch((int) $user['id'], [
                'first_name' => $first,
                'last_name' => $last,
            ]);
        }
    }

    /** @param array<string, mixed> $user */
    private function saveOnboardingVitrine(Request $request, array $user): void
    {
        Auth::requireOfferer();
        $trades = array_values(array_unique(array_filter($request->list('trades'), 'is_string')));
        if (count($trades) > 3) {
            $trades = array_slice($trades, 0, 3);
        }
        $allowed = Catalog::trades();
        $trades = array_values(array_filter($trades, static fn (string $trade): bool => in_array($trade, $allowed, true)));

        Profile::patch((int) $user['id'], [
            'first_name' => (string) $user['first_name'],
            'last_name' => (string) $user['last_name'],
            'title' => $request->string('title'),
            'presentation' => $request->string('presentation'),
            'city' => $request->string('city'),
            'trades' => $trades,
        ]);
    }

    /** @param array<string, mixed> $user */
    private function saveOnboardingMission(Request $request, array $user): void
    {
        Auth::requireSeeker();
        $title = $request->string('title');
        $brief = $request->string('brief');
        $category = $request->string('category_name');
        if ($title === '' || $brief === '' || $category === '') {
            throw new \RuntimeException('Indiquez au moins le métier, le titre et le brief.');
        }
        if (!in_array($category, Catalog::trades(), true)) {
            throw new \RuntimeException('Choisissez un métier dans la liste.');
        }

        Mission::create((int) $user['id'], [
            'title' => $title,
            'brief' => $brief,
            'category_name' => $category,
            'volume' => Catalog::volumeHint($category) ? ($request->string('volume') ?: null) : null,
            'budget_min' => self::money($request->string('budget_min')),
            'budget_max' => self::money($request->string('budget_max')),
            'deadline' => $request->string('deadline') ?: null,
            'status' => 'open',
        ]);
    }

    public function publier(Request $request): void
    {
        $user = Auth::requireSeeker();
        View::page('publier', [
            'title' => 'Publier une recherche',
            'trades' => Catalog::trades(),
            'error' => flash('error'),
            'old' => flash('old') ?: [],
            'suggestions' => Catalog::suggestionsForTrade('Correction'),
            'publisherName' => User::displayName($user),
        ]);
    }

    public function publierSave(Request $request): void
    {
        $user = Auth::requireSeeker();
        $title = $request->string('title');
        $brief = $request->string('brief');
        $category = $request->string('category_name');
        $draft = $request->string('intent') === 'draft';

        $volume = Catalog::volumeHint($category) ? ($request->string('volume') ?: null) : null;
        $old = [
            'title' => $title,
            'brief' => $brief,
            'category_name' => $category,
            'volume' => $volume ?? '',
            'budget_min' => $request->string('budget_min'),
            'budget_max' => $request->string('budget_max'),
            'deadline' => $request->string('deadline'),
        ];

        if ($title === '' || $brief === '' || $category === '') {
            flash('error', 'Indiquez au moins le métier, le titre et le brief.');
            flash('old', $old);
            redirect('/espace/publier');
        }

        $attachmentName = null;
        $attachmentPath = null;
        try {
            $stored = store_upload(
                $request->file('attachment'),
                'missions',
                ['pdf', 'doc', 'docx', 'odt', 'txt'],
                20 * 1024 * 1024
            );
            if ($stored) {
                $attachmentPath = $stored;
                $attachmentName = (string) ($request->file('attachment')['name'] ?? 'Pièce jointe');
            }
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            flash('old', $old);
            redirect('/espace/publier');
        }

        $mission = Mission::create((int) $user['id'], [
            'title' => $title,
            'brief' => $brief,
            'category_name' => $category,
            'volume' => $volume,
            'budget_min' => self::money($request->string('budget_min')),
            'budget_max' => self::money($request->string('budget_max')),
            'deadline' => $request->string('deadline') ?: null,
            'attachment_name' => $attachmentName,
            'attachment_path' => $attachmentPath,
            'status' => $draft ? 'draft' : 'open',
        ]);

        flash('saved', $draft ? 'Brouillon enregistré.' : 'Recherche publiée : les prestataires peuvent maintenant y répondre.');
        redirect(!empty($mission['slug']) ? '/missions/' . $mission['slug'] : '/espace/missions');
    }

    public function commande(Request $request): void
    {
        Auth::requireSeeker();
        View::page('commande', ['title' => 'Commande & paiement']);
    }

    public function suivi(Request $request): void
    {
        $user = Auth::requireUser();
        try {
            $uid = (int) $user['id'];
            $byId = [];
            foreach ([...Order::forBuyer($uid), ...Order::forSeller($uid)] as $order) {
                $byId[(int) ($order['id'] ?? 0)] = $order;
            }
            $orders = array_values($byId);
        } catch (\Throwable) {
            $orders = [];
        }
        View::page('suivi', [
            'title' => 'Suivi de commande',
            'orders' => $orders,
        ]);
    }

    public function commandes(Request $request): void
    {
        $user = Auth::requireSeeker();
        try {
            $orders = Order::forBuyer((int) $user['id']);
        } catch (\Throwable) {
            $orders = [];
        }
        View::page('commandes', [
            'title' => 'Mes commandes',
            'orders' => $orders,
        ]);
    }

    public function missions(Request $request): void
    {
        $user = Auth::requireSeeker();
        try {
            $missions = Mission::forUser((int) $user['id']);
        } catch (\Throwable) {
            $missions = [];
        }
        View::page('mesmissions', [
            'title' => 'Mes recherches',
            'myMissions' => $missions,
            'saved' => flash('saved'),
        ]);
    }

    public function candidatures(Request $request): void
    {
        $user = Auth::requireOfferer();
        try {
            $applications = Application::forUser((int) $user['id']);
        } catch (\Throwable) {
            $applications = [];
        }
        View::page('candidatures', [
            'title' => 'Mes candidatures',
            'applications' => $applications,
        ]);
    }

    public function prestations(Request $request): void
    {
        $user = Auth::requireOfferer();
        try {
            $services = Service::forUser((int) $user['id']);
        } catch (\Throwable) {
            $services = [];
        }
        $billing = self::billingState((int) $user['id']);
        View::page('mesprestations', [
            'title' => 'Mes prestations',
            'myServices' => $services,
            'saved' => flash('saved'),
            'billingBlock' => $billing['block'],
            'billingWarning' => $billing['warning'],
        ]);
    }

    public function creer(Request $request): void
    {
        $user = Auth::requireOfferer();
        $billing = self::billingState((int) $user['id']);
        $quote = ['percent' => 0, 'first_free' => true, 'completed' => 0];
        try {
            $quote = Commission::quoteForSeller((int) $user['id']);
        } catch (\Throwable) {
        }
        View::page('creer', [
            'title' => 'Proposer une prestation',
            'trades' => Catalog::trades(),
            'specialties' => Catalog::specialties(),
            'commission' => (string) ($quote['first_free'] ? 0 : $quote['percent']),
            'firstMissionFree' => $quote['first_free'],
            'standardCommission' => (string) Commission::percentForSeller((int) $user['id']),
            'isFounder' => !empty($quote['founder']),
            'billingBlock' => $billing['block'],
            'error' => flash('error'),
            'old' => flash('old') ?: [],
        ]);
    }

    public function creerSave(Request $request): void
    {
        $user = Auth::requireOfferer();
        $title = $request->string('title');
        $excerpt = $request->string('excerpt');
        $category = $request->string('category_name');
        $specialty = $request->string('specialty');
        $draft = $request->string('intent') === 'draft';

        $packagesInput = [];
        foreach ($request->list('packages') as $row) {
            if (!is_array($row)) {
                continue;
            }
            $packagesInput[] = [
                'name' => trim((string) ($row['name'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
                'price' => trim((string) ($row['price'] ?? '')),
                'delay' => trim((string) ($row['delay'] ?? '')),
            ];
        }

        $old = [
            'title' => $title,
            'excerpt' => $excerpt,
            'category_name' => $category,
            'specialty' => $specialty,
            'delay' => $request->string('delay'),
            'price_from' => $request->string('price_from'),
            'packages' => $packagesInput,
        ];

        if ($title === '' || $category === '') {
            flash('error', 'Indiquez au moins le métier et le titre de la prestation.');
            flash('old', $old);
            redirect('/espace/prestations/creer');
        }
        if (!in_array($category, Catalog::trades(), true)) {
            flash('error', 'Choisissez un métier dans la liste.');
            flash('old', $old);
            redirect('/espace/prestations/creer');
        }
        if ($specialty !== '' && !in_array($specialty, Catalog::specialties(), true)) {
            flash('error', 'Choisissez une spécialité dans la liste.');
            flash('old', $old);
            redirect('/espace/prestations/creer');
        }
        if (!$draft) {
            try {
                Invoice::assertCanOffer((int) $user['id']);
            } catch (\Throwable $e) {
                flash('error', $e->getMessage());
                flash('old', $old);
                redirect('/espace/prestations/creer');
            }
        }

        $imagePath = null;
        try {
            $stored = store_upload(
                $request->file('image'),
                'prestations',
                ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                5 * 1024 * 1024
            );
            if ($stored) {
                $imagePath = $stored;
            }
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            flash('old', $old);
            redirect('/espace/prestations/creer');
        }

        $packages = [];
        foreach ($packagesInput as $row) {
            if ($row['name'] === '') {
                continue;
            }
            $packages[] = [
                'name' => $row['name'],
                'description' => $row['description'],
                'price' => self::money($row['price']) ?? 0,
                'delay' => $row['delay'],
            ];
        }

        try {
            $service = Service::create((int) $user['id'], [
                'title' => $title,
                'excerpt' => $excerpt !== '' ? $excerpt : null,
                'category_name' => $category,
                'specialty' => $specialty !== '' ? $specialty : null,
                'image_path' => $imagePath,
                'delay' => $request->string('delay') ?: null,
                'price_from' => self::money($request->string('price_from')),
                'status' => $draft ? 'draft' : 'published',
            ], $packages);
        } catch (\RuntimeException $e) {
            flash('error', $e->getMessage());
            flash('old', $old);
            redirect('/espace/prestations/creer');
        } catch (\Throwable) {
            flash('error', 'La prestation n\'a pas pu être enregistrée. Réessayez dans un instant.');
            flash('old', $old);
            redirect('/espace/prestations/creer');
        }

        flash('saved', $draft ? 'Brouillon enregistré.' : 'Prestation publiée : elle apparaît dans l\'annuaire.');
        redirect(!empty($service['slug']) ? '/prestations/' . $service['slug'] : '/espace/prestations');
    }

    public function messages(Request $request): void
    {
        Auth::requireUser();
        View::page('messagerie', [
            'title' => 'Messagerie',
            'threads' => [],
        ]);
    }

    public function notifications(Request $request): void
    {
        $user = Auth::requireUser();
        try {
            $items = Notification::forUser((int) $user['id']);
            $unread = Notification::unreadCount((int) $user['id']);
        } catch (\Throwable) {
            $items = [];
            $unread = 0;
        }
        View::page('notifications', [
            'title' => 'Notifications',
            'items' => $items,
            'unreadCount' => $unread,
            'saved' => flash('saved'),
        ]);
    }

    public function notificationsRead(Request $request): void
    {
        $user = Auth::requireUser();
        try {
            Notification::markAllRead((int) $user['id']);
        } catch (\Throwable) {
        }
        flash('saved', 'Toutes les alertes sont marquées comme lues.');
        redirect('/espace/notifications');
    }

    public function notificationOpen(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        try {
            $item = Notification::markRead((int) $id, (int) $user['id']);
        } catch (\Throwable) {
            $item = null;
        }
        redirect((string) ($item['href'] ?? '/espace/notifications'));
    }

    public function favoris(Request $request): void
    {
        $user = Auth::requireSeeker();
        try {
            $favorites = Favorite::forUser((int) $user['id']);
        } catch (\Throwable) {
            $favorites = [];
        }
        View::page('favoris', [
            'title' => 'Favoris',
            'favorites' => $favorites,
        ]);
    }

    public function avis(Request $request): void
    {
        $user = Auth::requireUser();
        $pending = [];
        try {
            $pending = Order::awaitingReviewForBuyer((int) $user['id']);
        } catch (\Throwable) {
        }
        View::page('avis', [
            'title' => 'Valider et noter',
            'pendingReviews' => $pending,
            'criteria' => Review::CRITERIA,
            'error' => flash('error'),
            'saved' => flash('saved'),
        ]);
    }

    public function avisSave(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        if (!$request->bool('accept_cgv')) {
            flash('error', 'L\'acceptation des CGV est obligatoire pour valider la mission.');
            redirect('/espace/avis');
        }
        try {
            Order::confirmByBuyer((int) $id, (int) $user['id'], [
                'quality' => (int) $request->string('quality'),
                'efficiency' => (int) $request->string('efficiency'),
                'satisfaction' => (int) $request->string('satisfaction'),
                'body' => $request->string('body'),
            ]);
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/espace/avis');
        }
        flash('saved', 'Mission validée. Merci pour votre avis : la commission prestataire peut maintenant être facturée.');
        redirect('/espace/avis');
    }

    public function vitrine(Request $request): void
    {
        $user = Auth::requireOfferer();
        try {
            $profile = Profile::ensure((int) $user['id']);
        } catch (\Throwable $e) {
            flash('error', 'La vitrine n\'est pas encore disponible : ' . $e->getMessage());
            redirect('/espace');
        }
        View::page('vitrine', [
            'title' => 'Ma vitrine',
            'profile' => $profile,
            'prenom' => $user['first_name'],
            'nom' => $user['last_name'],
            'trades' => Catalog::trades(),
            'genres' => Catalog::specialties(),
            'skillLevels' => Profile::SKILL_LEVELS,
            'langLevels' => Profile::LANG_LEVELS,
            'portfolioKinds' => Profile::PORTFOLIO_KINDS,
            'completion' => $profile['completion'] ?? 0,
            'saved' => flash('saved') ? true : false,
            'error' => flash('error'),
        ]);
    }

    public function vitrineSave(Request $request): void
    {
        $user = Auth::requireOfferer();
        $first = $request->string('first_name', $user['first_name']);
        $last = $request->string('last_name', $user['last_name']);

        $trades = array_values(array_unique(array_filter($request->list('trades'), 'is_string')));
        if (count($trades) > 3) {
            $trades = array_slice($trades, 0, 3);
        }

        try {
            $current = Profile::findByUser((int) $user['id']) ?? [];
            $allowedTrades = array_values(array_unique(array_merge(Catalog::trades(), $current['trades'] ?? [])));
            $allowedGenres = array_values(array_unique(array_merge(Catalog::specialties(), $current['genres'] ?? [])));
            $trades = array_values(array_filter($trades, static fn (string $trade): bool => in_array($trade, $allowedTrades, true)));
            $genres = array_values(array_filter(
                array_filter($request->list('genres'), 'is_string'),
                static fn (string $genre): bool => in_array($genre, $allowedGenres, true)
            ));

            $profile = Profile::save((int) $user['id'], [
                'first_name' => $first,
                'last_name' => $last,
                'title' => $request->string('title'),
                'presentation' => $request->string('presentation'),
                'city' => $request->string('city'),
                'availability' => $request->string('availability'),
                'availability_status' => $request->string('availability_status'),
                'languages' => $request->string('languages'),
                'hourly_rate' => $request->string('hourly_rate'),
                'rate_kind' => $request->string('rate_kind'),
                'rate_note' => $request->string('rate_note'),
                'website' => $request->string('website'),
                'trades' => $trades,
                'skills' => self::rows($request->list('skills'), ['label', 'niveau']),
                'tools' => self::stringList($request->string('tools')),
                'genres' => $genres,
                'languages_list' => self::rows($request->list('languages_list'), ['langue', 'niveau']),
                'experiences' => self::rows($request->list('experiences'), ['periode', 'poste', 'lieu', 'detail']),
                'education' => self::rows($request->list('education'), ['annee', 'intitule', 'ecole']),
            ]);

            User::update((int) $user['id'], [
                'first_name' => $first,
                'last_name' => $last,
            ]);
            User::storeAvatar((int) $user['id'], $request->file('avatar'));

            $items = [];
            foreach ($request->list('portfolio') as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }
                $title = trim((string) ($row['title'] ?? ''));
                if ($title === '') {
                    continue;
                }
                $imagePath = trim((string) ($row['image_path'] ?? '')) ?: null;
                $file = self::nestedFile('portfolio_file', (int) $i);
                if ($file) {
                    $stored = store_upload($file, 'portfolio', ['jpg', 'jpeg', 'png', 'webp', 'gif'], 5 * 1024 * 1024);
                    if ($stored) {
                        $imagePath = $stored;
                    }
                }
                $items[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'title' => $title,
                    'description' => trim((string) ($row['description'] ?? '')),
                    'year' => trim((string) ($row['year'] ?? '')),
                    'kind' => (string) ($row['kind'] ?? 'creation'),
                    'image_path' => $imagePath,
                    'image_url' => trim((string) ($row['image_url'] ?? '')),
                ];
            }
            PortfolioItem::replace((int) $profile['id'], $items);
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/espace/vitrine');
        }

        flash('saved', true);
        redirect('/espace/vitrine');
    }

    public function disponibiliteSave(Request $request): void
    {
        $user = Auth::requireOfferer();
        $status = Profile::normalizeStatus($request->string('availability_status'));

        try {
            Profile::setAvailabilityStatus((int) $user['id'], $status);
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/espace');
        }

        flash(
            'saved',
            $status === Profile::STATUS_BUSY
                ? 'Votre vitrine affiche Occupé : les porteurs de projet savent que votre planning est chargé.'
                : 'Votre vitrine affiche Disponible pour de nouveaux appels d\'offres.'
        );
        redirect('/espace');
    }

    public function parametres(Request $request): void
    {
        $user = Auth::requireUser();
        View::page('parametres', [
            'title' => 'Paramètres',
            'prenom' => $user['first_name'],
            'nom' => $user['last_name'],
            'avatarSrc' => user_avatar_src($user),
            'seeksChecked' => User::seeksServices($user),
            'offersChecked' => User::offersServices($user),
            'linkedProviders' => [
                'google' => (string) ($user['google_id'] ?? '') !== '',
                'facebook' => (string) ($user['facebook_id'] ?? '') !== '',
            ],
            'saved' => flash('saved') ? true : false,
            'error' => flash('error'),
        ]);
    }

    public function parametresSave(Request $request): void
    {
        $user = Auth::requireUser();
        $seeks = $request->bool('seeks_services');
        $offers = $request->bool('offers_services');

        if (!$seeks && !$offers) {
            flash('error', 'Conservez au moins un usage : chercher des prestataires ou proposer vos services.');
            redirect('/espace/parametres');
        }

        if ($offers) {
            User::ensureProfile((int) $user['id']);
        }

        try {
            User::storeAvatar((int) $user['id'], $request->file('avatar'));
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/espace/parametres');
        }

        User::update((int) $user['id'], [
            'first_name' => $request->string('first_name', $user['first_name']),
            'last_name' => $request->string('last_name', $user['last_name']),
            'seeks_services' => $seeks ? 1 : 0,
            'offers_services' => $offers ? 1 : 0,
            'role' => ($user['role'] ?? '') === 'admin'
                ? 'admin'
                : User::roleFromIntents($seeks, $offers),
        ]);
        flash('saved', true);
        redirect('/espace/parametres');
    }

    public function facturation(Request $request): void
    {
        $user = Auth::requireOfferer();
        try {
            $orders = Order::forSeller((int) $user['id']);
        } catch (\Throwable) {
            $orders = [];
        }
        try {
            $invoices = Invoice::forSeller((int) $user['id']);
        } catch (\Throwable) {
            $invoices = [];
        }
        $total = 0;
        foreach ($orders as $order) {
            if (in_array((string) ($order['status'] ?? ''), ['confirmed', 'paid'], true)) {
                $total += (int) ($order['amount'] ?? 0);
            }
        }
        $due = 0;
        foreach ($invoices as $invoice) {
            if (!empty($invoice['is_open'])) {
                $due += (int) ($invoice['amount'] ?? 0);
            }
        }
        $billing = self::billingState((int) $user['id']);
        View::page('facturation', [
            'title' => 'Facturation',
            'orders' => $orders,
            'invoices' => $invoices,
            'totalAmount' => $total,
            'dueAmount' => $due,
            'billingBlock' => $billing['block'],
            'billingWarning' => $billing['warning'],
        ]);
    }

    /** @return array{block: ?array<string, mixed>, warning: ?array<string, mixed>} */
    private static function billingState(int $userId): array
    {
        try {
            $block = Invoice::blockingInvoice($userId);
            return [
                'block' => $block,
                'warning' => $block ? null : Invoice::pendingInvoice($userId),
            ];
        } catch (\Throwable) {
            return ['block' => null, 'warning' => null];
        }
    }

    private static function money(string $value): ?int
    {
        $value = trim(str_replace(["\u{00A0}", ' '], '', $value));
        if ($value === '') {
            return null;
        }
        $normalized = str_replace(',', '.', $value);
        if (is_numeric($normalized)) {
            return (int) round((float) $normalized);
        }
        $clean = preg_replace('/[^\d]/', '', $value) ?? '';
        return $clean !== '' ? (int) $clean : null;
    }

    /**
     * @param list<mixed> $rows
     * @param list<string> $keys
     * @return list<array<string, string>>
     */
    private static function rows(array $rows, array $keys): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $item = [];
            foreach ($keys as $key) {
                $item[$key] = trim((string) ($row[$key] ?? ''));
            }
            if (implode('', $item) === '') {
                continue;
            }
            $out[] = $item;
        }
        return $out;
    }

    /** @return list<string> */
    private static function stringList(string $value): array
    {
        $parts = preg_split('/[,;\n]+/', $value) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $out[] = $part;
            }
        }
        return array_values(array_unique($out));
    }

    /** @return array<string, mixed>|null */
    private static function nestedFile(string $field, int $index): ?array
    {
        $bag = $_FILES[$field] ?? null;
        if (!is_array($bag) || !isset($bag['name']) || !is_array($bag['name'])) {
            return null;
        }
        if (!isset($bag['name'][$index]) || ($bag['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return [
            'name' => $bag['name'][$index] ?? '',
            'type' => $bag['type'][$index] ?? '',
            'tmp_name' => $bag['tmp_name'][$index] ?? '',
            'error' => $bag['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $bag['size'][$index] ?? 0,
        ];
    }
}
