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
use Adl\Models\Conversation;
use Adl\Models\Favorite;
use Adl\Models\Invoice;
use Adl\Models\LegalAcceptance;
use Adl\Models\Mission;
use Adl\Models\Newsletter;
use Adl\Models\Notification;
use Adl\Models\Order;
use Adl\Models\OrderFile;
use Adl\Models\OrderMilestone;
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
        $commissionRate = null;

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
                $commissionRate = Commission::accountState((int) $user['id']);
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
            'commissionRate' => $commissionRate,
            'jalonTodos' => self::jalonTodos((int) $user['id']),
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

        if ($intent === 'later') {
            flash('saved', 'Vous pourrez reprendre ces étapes depuis votre tableau de bord.');
            redirect('/espace');
        }
        if ($step === 'apercu') {
            User::markOnboardingDone((int) $user['id']);
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
            flash('error', user_error_message($e));
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
        if (!in_array($category, Catalog::trades(), true)) {
            flash('error', 'Choisissez un métier dans la liste.');
            flash('old', $old);
            redirect('/espace/publier');
        }

        $attachment = ['name' => null, 'path' => null];
        try {
            $attachment = self::missionAttachmentFromRequest($request);
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
            flash('old', $old);
            redirect('/espace/publier');
        }

        try {
            $mission = Mission::create((int) $user['id'], [
                'title' => $title,
                'brief' => $brief,
                'category_name' => $category,
                'volume' => $volume,
                'budget_min' => self::money($request->string('budget_min')),
                'budget_max' => self::money($request->string('budget_max')),
                'deadline' => $request->string('deadline') ?: null,
                'attachment_name' => $attachment['name'],
                'attachment_path' => $attachment['path'],
                'status' => $draft ? 'draft' : 'open',
            ]);
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
            flash('old', $old);
            redirect('/espace/publier');
        }

        flash('saved', $draft ? 'Brouillon enregistré.' : 'Recherche publiée : les prestataires peuvent maintenant y répondre.');
        redirect(!empty($mission['slug']) ? '/missions/' . $mission['slug'] : '/espace/missions');
    }

    public function publierEdit(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        $mission = Mission::findForUser((int) $id, (int) $user['id']);
        if (!$mission) {
            not_found('Cette recherche est introuvable.');
        }
        if (!in_array((string) ($mission['status'] ?? ''), ['draft', 'open'], true)) {
            flash('error', 'Cette recherche ne peut plus être modifiée.');
            redirect((string) ($mission['href'] ?? '/espace/missions'));
        }

        $old = flash('old') ?: [
            'title' => (string) ($mission['title'] ?? ''),
            'brief' => (string) ($mission['brief'] ?? ''),
            'category_name' => (string) ($mission['category_name'] ?? ''),
            'volume' => (string) ($mission['volume'] ?? ''),
            'budget_min' => $mission['budget_min'] !== null && $mission['budget_min'] !== ''
                ? (string) $mission['budget_min']
                : '',
            'budget_max' => $mission['budget_max'] !== null && $mission['budget_max'] !== ''
                ? (string) $mission['budget_max']
                : '',
            'deadline' => substr((string) ($mission['deadline'] ?? ''), 0, 10),
        ];

        View::page('publier', [
            'title' => 'Modifier la recherche',
            'editing' => true,
            'missionId' => (int) $mission['id'],
            'missionStatus' => (string) ($mission['status'] ?? 'draft'),
            'existingAttachment' => (string) ($mission['attachment_name'] ?? ''),
            'trades' => Catalog::trades(),
            'error' => flash('error'),
            'old' => $old,
            'suggestions' => Catalog::suggestionsForTrade((string) ($old['category_name'] ?? '')),
            'publisherName' => User::displayName($user),
        ]);
    }

    public function publierEditSave(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        $mission = Mission::findForUser((int) $id, (int) $user['id']);
        if (!$mission) {
            not_found('Cette recherche est introuvable.');
        }
        if (!in_array((string) ($mission['status'] ?? ''), ['draft', 'open'], true)) {
            flash('error', 'Cette recherche ne peut plus être modifiée.');
            redirect((string) ($mission['href'] ?? '/espace/missions'));
        }

        $title = $request->string('title');
        $brief = $request->string('brief');
        $category = $request->string('category_name');
        $draft = $request->string('intent') === 'draft';
        $publish = !$draft;
        if ($publish && !User::seeksServices($user)) {
            Auth::requireSeeker();
        }

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
        $back = '/espace/publier/' . (int) $id;

        if ($title === '' || $brief === '' || $category === '') {
            flash('error', 'Indiquez au moins le métier, le titre et le brief.');
            flash('old', $old);
            redirect($back);
        }
        if (!in_array($category, Catalog::trades(), true)) {
            flash('error', 'Choisissez un métier dans la liste.');
            flash('old', $old);
            redirect($back);
        }

        $attachment = [
            'name' => $mission['attachment_name'] ?? null,
            'path' => $mission['attachment_path'] ?? null,
        ];
        try {
            $attachment = self::missionAttachmentFromRequest(
                $request,
                isset($mission['attachment_path']) ? (string) $mission['attachment_path'] : null,
                isset($mission['attachment_name']) ? (string) $mission['attachment_name'] : null
            );
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
            flash('old', $old);
            redirect($back);
        }

        $status = (string) ($mission['status'] ?? 'draft');
        if ($status === 'draft') {
            $status = $draft ? 'draft' : 'open';
        }

        try {
            $updated = Mission::update((int) $id, (int) $user['id'], [
                'title' => $title,
                'brief' => $brief,
                'category_name' => $category,
                'volume' => $volume,
                'budget_min' => self::money($request->string('budget_min')),
                'budget_max' => self::money($request->string('budget_max')),
                'deadline' => $request->string('deadline') ?: null,
                'attachment_name' => $attachment['name'],
                'attachment_path' => $attachment['path'],
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
            flash('old', $old);
            redirect($back);
        }

        $nowOpen = ($updated['status'] ?? '') === 'open';
        if ($status === 'open' && ($mission['status'] ?? '') === 'draft') {
            flash('saved', 'Recherche publiée : les prestataires peuvent maintenant y répondre.');
        } elseif ($nowOpen) {
            flash('saved', 'Recherche mise à jour.');
        } else {
            flash('saved', 'Brouillon enregistré.');
        }
        redirect(!empty($updated['slug']) ? '/missions/' . $updated['slug'] : '/espace/missions');
    }

    public function publierPublish(Request $request, string $id): void
    {
        $user = Auth::requireSeeker();
        try {
            $mission = Mission::publishForUser((int) $id, (int) $user['id']);
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
            redirect('/espace/publier/' . (int) $id);
        }
        flash('saved', 'Recherche publiée : les prestataires peuvent maintenant y répondre.');
        redirect(!empty($mission['slug']) ? '/missions/' . $mission['slug'] : '/espace/missions');
    }

    public function commande(Request $request): void
    {
        $user = Auth::requireUser();
        $slug = $request->string('prestation');
        $service = null;
        if ($slug !== '') {
            try {
                $service = Service::findBySlug($slug);
            } catch (\Throwable) {
                $service = null;
            }
        }
        if (
            !$service
            || ($service['status'] ?? '') !== 'published'
            || !User::isPublicOfferer((int) ($service['user_id'] ?? 0))
        ) {
            View::page('commande', [
                'title' => 'Confirmer la commande',
                'service' => null,
                'error' => flash('error'),
            ]);
            return;
        }
        if ((int) $service['user_id'] === (int) $user['id']) {
            flash('error', 'Vous ne pouvez pas commander votre propre prestation.');
            redirect((string) $service['href']);
        }

        $old = flash('old') ?: [];
        $packageId = (int) ($old['package_id'] ?? $request->int('formule') ?? 0);
        $selected = null;
        foreach ($service['packages'] ?? [] as $package) {
            if ($packageId && (int) ($package['id'] ?? 0) === $packageId) {
                $selected = $package;
                break;
            }
        }
        if (!$selected && ($service['packages'] ?? []) !== []) {
            $selected = $service['packages'][0];
        }
        $selectedOptionIds = [];
        $rawOptionIds = $old['option_ids'] ?? $old['options'] ?? $request->list('options');
        foreach ($rawOptionIds as $id) {
            if (is_array($id)) {
                continue;
            }
            $id = (int) $id;
            if ($id > 0) {
                $selectedOptionIds[] = $id;
            }
        }

        View::page('commande', [
            'title' => 'Confirmer la commande',
            'service' => $service,
            'selectedPackage' => $selected,
            'selectedOptionIds' => array_values(array_unique($selectedOptionIds)),
            'old' => $old,
            'error' => flash('error'),
        ]);
    }

    public function commandeSave(Request $request): void
    {
        $user = Auth::requireUser();
        $service = Service::find((int) $request->string('service_id'));
        if (
            !$service
            || ($service['status'] ?? '') !== 'published'
            || !User::isPublicOfferer((int) ($service['user_id'] ?? 0))
        ) {
            flash('error', 'Cette prestation n\'est plus disponible.');
            redirect('/prestations');
        }
        if ((int) $service['user_id'] === (int) $user['id']) {
            flash('error', 'Vous ne pouvez pas commander votre propre prestation.');
            redirect((string) $service['href']);
        }

        $packageId = $request->int('package_id');
        $selected = null;
        foreach ($service['packages'] ?? [] as $package) {
            if ($packageId && (int) ($package['id'] ?? 0) === $packageId) {
                $selected = $package;
                break;
            }
        }
        if (($service['packages'] ?? []) !== [] && !$selected) {
            flash('error', 'Choisissez une formule encore disponible.');
            flash('old', $request->all());
            redirect('/espace/commande?prestation=' . rawurlencode((string) $service['slug']));
        }
        $pickedOptions = Service::pickOptions($service, $request->list('options'));
        $amount = $selected
            ? (int) ($selected['price'] ?? 0)
            : (int) ($service['price_from'] ?? 0);
        foreach ($pickedOptions as $option) {
            $amount += (int) ($option['price'] ?? 0);
        }
        $brief = $request->string('brief');

        try {
            $existing = Order::findPendingForService((int) $user['id'], (int) $service['id']);
            if ($existing) {
                Conversation::open((int) $user['id'], (int) $service['user_id'], [
                    'subject' => (string) $service['title'],
                    'order_id' => (int) $existing['id'],
                    'service_id' => (int) $service['id'],
                ]);
                $currentCode = (string) ($existing['current_milestone']['code'] ?? 'quote');
                $sameCart = (int) ($existing['amount'] ?? 0) === $amount
                    && (string) ($existing['brief'] ?? '') === $brief
                    && (string) ($existing['package_name'] ?? '') === (string) ($selected['name'] ?? '');
                if ($currentCode !== 'quote') {
                    flash('saved', 'Cette commande est déjà ouverte. Consultez le devis et les jalons dans le suivi.');
                    redirect('/espace/suivi/' . (int) $existing['id']);
                }
                if (!$sameCart) {
                    Order::updatePendingDetails((int) $existing['id'], [
                        'amount' => $amount,
                        'brief' => $brief,
                        'package_name' => $selected['name'] ?? null,
                        'options' => $pickedOptions,
                    ]);
                    flash('saved', 'Votre demande a été mise à jour. Le prestataire envoie le devis depuis le suivi.');
                } else {
                    flash('saved', 'Cette commande est déjà ouverte. Le prestataire envoie le devis depuis le suivi.');
                }
                redirect('/espace/suivi/' . (int) $existing['id']);
            }
            $order = Order::create([
                'buyer_id' => (int) $user['id'],
                'seller_id' => (int) $service['user_id'],
                'service_id' => (int) $service['id'],
                'amount' => $amount,
                'brief' => $brief,
                'package_name' => $selected['name'] ?? null,
                'options' => $pickedOptions,
            ]);
            Conversation::open((int) $user['id'], (int) $service['user_id'], [
                'subject' => (string) $service['title'],
                'order_id' => (int) $order['id'],
                'service_id' => (int) $service['id'],
            ]);
            if ($brief !== '') {
                $thread = Conversation::open((int) $user['id'], (int) $service['user_id'], [
                    'order_id' => (int) $order['id'],
                    'service_id' => (int) $service['id'],
                ]);
                Conversation::send((int) $thread['id'], (int) $user['id'], $brief, notify: false);
            }
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
            flash('old', $request->all());
            redirect('/espace/commande?prestation=' . rawurlencode((string) $service['slug']));
        }

        flash('saved', 'Commande ouverte. Le prestataire envoie le devis, vous suivez les jalons ici : le règlement se fait entre vous, hors de la plateforme.');
        redirect('/espace/suivi/' . (int) $order['id']);
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
        try {
            $counts = OrderFile::countsByOrderIds(array_map(static fn (array $o): int => (int) ($o['id'] ?? 0), $orders));
        } catch (\Throwable) {
            $counts = [];
        }
        foreach ($orders as &$order) {
            $oid = (int) ($order['id'] ?? 0);
            $order['file_count'] = $counts[$oid] ?? 0;
            $order['depot_open'] = OrderFile::canDeposit($order);
        }
        unset($order);
        View::page('suivi', [
            'title' => 'Suivi de commande',
            'orders' => $orders,
            'saved' => flash('saved'),
            'error' => flash('error'),
        ]);
    }

    public function suiviShow(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        $order = self::requireSuiviOrder((int) $id, $user);
        $uid = (int) $user['id'];
        $tabs = self::suiviTabs($order);
        View::page('suivi-detail', [
            'title' => 'Suivi ' . $order['num'],
            'order' => $order,
            'milestones' => $order['milestones'] ?? [],
            'action' => OrderMilestone::actionFor($order, $uid),
            'isBuyer' => (int) $order['buyer_id'] === $uid,
            'isSeller' => (int) $order['seller_id'] === $uid,
            'threadHref' => $tabs['threadHref'],
            'depotCount' => $tabs['depotCount'],
            'depotOpen' => $tabs['depotOpen'],
            'suiviTab' => 'jalons',
            'saved' => flash('saved'),
            'error' => flash('error'),
        ]);
    }

    public function suiviDepotIndex(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        $order = self::requireSuiviOrder((int) $id, $user);
        $uid = (int) $user['id'];
        $tabs = self::suiviTabs($order);
        $depotFiles = [];
        try {
            $depotFiles = OrderFile::forOrder((int) $order['id'], $uid, $order);
        } catch (\Throwable) {
        }
        View::page('suivi-depot-list', [
            'title' => 'Fichiers ' . $order['num'],
            'order' => $order,
            'threadHref' => $tabs['threadHref'],
            'depotCount' => $tabs['depotCount'],
            'depotOpen' => $tabs['depotOpen'],
            'depotFiles' => $depotFiles,
            'suiviTab' => 'fichiers',
            'saved' => flash('saved'),
            'error' => flash('error'),
        ]);
    }

    public function suiviDepotStore(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        $order = self::requireSuiviOrder((int) $id, $user);
        try {
            $file = OrderFile::create((int) $order['id'], (int) $user['id'], $order, $request->file('document'), $request->string('note'));
            $ping = Conversation::jalonPings()['vault'] ?? 'Nouveau fichier dans l’espace de dépôt.';
            self::pingOrderThread((int) $order['id'], (int) $user['id'], $ping . ' ' . (string) ($file['file_name'] ?? ''), false);
            flash('saved', 'Fichier déposé. L’autre partie a été prévenue.');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
        redirect('/espace/suivi/' . (int) $id . '/depot');
    }

    public function suiviDepotShow(Request $request, string $id, string $fid): void
    {
        $user = Auth::requireUser();
        $order = self::requireSuiviOrder((int) $id, $user);
        $file = OrderFile::findForParty((int) $order['id'], (int) $fid, (int) $user['id'], $order);
        if (!$file) {
            not_found('Ce fichier est introuvable.');
        }
        if (empty($file['is_withdrawn'])) {
            OrderFile::recordAccess((int) $file['id'], (int) $user['id'], 'view');
            $file = OrderFile::findForParty((int) $order['id'], (int) $fid, (int) $user['id'], $order) ?? $file;
        }
        $tabs = self::suiviTabs($order);
        View::page('suivi-depot', [
            'title' => (string) ($file['file_name'] ?? 'Fichier'),
            'order' => $order,
            'file' => $file,
            'clicks' => OrderFile::clicks((int) $file['id']),
            'isBuyer' => (int) $order['buyer_id'] === (int) $user['id'],
            'threadHref' => $tabs['threadHref'],
            'depotCount' => $tabs['depotCount'],
            'depotOpen' => $tabs['depotOpen'],
            'suiviTab' => 'fichiers',
            'error' => flash('error'),
            'saved' => flash('saved'),
        ]);
    }

    public function suiviDepotView(Request $request, string $id, string $fid): void
    {
        $file = self::requireSuiviDepotFile((int) $id, (int) $fid);
        if (empty($file['can_preview']) || ($file['file_path'] ?? '') === '') {
            not_found('Ce fichier ne peut pas être prévisualisé.');
        }
        send_any_upload((string) $file['file_path'], (string) $file['file_name'], (string) $file['mime'], true);
    }

    public function suiviDepotDownload(Request $request, string $id, string $fid): void
    {
        $user = Auth::requireUser();
        $file = self::requireSuiviDepotFile((int) $id, (int) $fid, $user);
        if (($file['file_path'] ?? '') === '' || !empty($file['is_withdrawn'])) {
            not_found('Ce fichier n’est plus disponible.');
        }
        OrderFile::recordAccess((int) $file['id'], (int) $user['id'], 'download');
        send_any_upload((string) $file['file_path'], (string) $file['file_name'], (string) $file['mime']);
    }

    public function suiviDepotWithdraw(Request $request, string $id, string $fid): void
    {
        $user = Auth::requireUser();
        $order = self::requireSuiviOrder((int) $id, $user);
        try {
            OrderFile::withdraw((int) $order['id'], (int) $fid, (int) $user['id'], $order);
            flash('saved', 'Fichier retiré. L’historique du dépôt est conservé.');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
        redirect('/espace/suivi/' . (int) $id . '/depot');
    }

    /**
     * @param array<string, mixed> $order
     * @return array{threadHref: string, depotCount: int, depotOpen: bool}
     */
    private static function suiviTabs(array $order): array
    {
        $threadHref = '/espace/messages';
        try {
            $thread = Conversation::open((int) $order['buyer_id'], (int) $order['seller_id'], [
                'order_id' => (int) $order['id'],
            ]);
            $threadHref = (string) ($thread['href'] ?? $threadHref);
        } catch (\Throwable) {
        }
        $depotCount = 0;
        try {
            $depotCount = OrderFile::countForOrder((int) $order['id']);
        } catch (\Throwable) {
        }

        return [
            'threadHref' => $threadHref,
            'depotCount' => $depotCount,
            'depotOpen' => OrderFile::canDeposit($order),
        ];
    }

    /** @param array<string, mixed> $user */
    private static function requireSuiviOrder(int $id, array $user): array
    {
        $order = Order::findForUser($id, (int) $user['id']);
        if (!$order && ($user['role'] ?? '') === 'admin') {
            $order = Order::find($id);
        }
        if (!$order) {
            not_found('Cette commande est introuvable.');
        }
        return $order;
    }

    /** @param array<string, mixed>|null $user */
    private static function requireSuiviDepotFile(int $orderId, int $fileId, ?array $user = null): array
    {
        $user ??= Auth::requireUser();
        $order = self::requireSuiviOrder($orderId, $user);
        $file = OrderFile::findForParty((int) $order['id'], $fileId, (int) $user['id'], $order);
        if (!$file) {
            not_found('Ce fichier est introuvable.');
        }
        return $file;
    }

    public function suiviAccept(Request $request, string $id): void
    {
        flash('error', 'Le démarrage passe par l’envoi et l’acceptation du devis, dans le suivi de commande.');
        redirect('/espace/suivi/' . (int) $id);
    }

    public function suiviDeliver(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        try {
            OrderMilestone::complete((int) $id, (int) $user['id'], 'deliver');
            flash('saved', OrderMilestone::flashFor('deliver'));
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
        redirect('/espace/suivi/' . (int) $id);
    }

    public function suiviJalon(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        $code = $request->string('code');
        try {
            if (!Order::findForUser((int) $id, (int) $user['id']) && ($user['role'] ?? '') !== 'admin') {
                throw new \RuntimeException('Cette commande est introuvable.');
            }
            $file = self::storeMilestoneFile($request, (int) $id, $code);
            OrderMilestone::complete((int) $id, (int) $user['id'], $code, [
                'amount' => self::money($request->string('amount')),
                'deposit_amount' => self::money($request->string('deposit_amount')),
                'delay' => $request->string('delay'),
                'note' => $request->string('note'),
                'file_name' => $file['name'] ?? null,
                'file_path' => $file['path'] ?? null,
            ]);
            self::pingJalonThread((int) $id, (int) $user['id'], $code, $file);
            flash('saved', OrderMilestone::flashFor($code));
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
        redirect('/espace/suivi/' . (int) $id);
    }

    public function suiviRefuseQuote(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        $note = $request->string('note');
        try {
            OrderMilestone::refuseQuote((int) $id, (int) $user['id'], $note);
            $ping = Conversation::jalonPings()['quote_refused'] ?? 'Devis refusé. Vous pouvez en proposer un nouveau dans le suivi.';
            if ($note !== '') {
                $ping .= "\n\n" . $note;
            }
            self::pingOrderThread((int) $id, (int) $user['id'], $ping, false);
            flash('saved', 'Devis refusé. Le prestataire peut proposer un nouveau devis. Précisez-lui ce qui ne convenait pas dans la messagerie si besoin.');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
        redirect('/espace/suivi/' . (int) $id);
    }

    public function suiviCancel(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        try {
            OrderMilestone::cancelByBuyer((int) $id, (int) $user['id']);
            self::pingOrderThread(
                (int) $id,
                (int) $user['id'],
                Conversation::jalonPings()['order_cancelled'] ?? 'Commande annulée.',
                false
            );
            flash('saved', 'Commande annulée. Le dossier est clôturé.');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
        redirect('/espace/suivi/' . (int) $id);
    }

    public function suiviDispute(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        try {
            Order::openDispute((int) $id, (int) $user['id'], $request->string('reason'));
            flash('saved', 'Litige ouvert. L\'équipe de médiation a été prévenue.');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
        redirect('/espace/suivi/' . (int) $id);
    }

    public function suiviFile(Request $request, string $id, string $code): void
    {
        $user = Auth::requireUser();
        $order = Order::findForUser((int) $id, (int) $user['id']);
        if (!$order && ($user['role'] ?? '') === 'admin') {
            $order = Order::find((int) $id);
        }
        if (!$order) {
            not_found('Cette commande est introuvable.');
        }
        $row = null;
        foreach ($order['milestones'] ?? [] as $step) {
            if (($step['code'] ?? '') === $code) {
                $row = $step;
                break;
            }
        }
        $path = trim((string) ($row['file_path'] ?? ''));
        if ($path === '') {
            not_found('Aucune pièce jointe.');
        }
        $name = trim((string) ($row['file_name'] ?? '')) ?: 'document';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimes = upload_mime_map();
        send_any_upload($path, $name, $mimes[$ext][0] ?? 'application/octet-stream');
    }

    public function commandes(Request $request): void
    {
        $user = Auth::requireUser();
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
        $user = Auth::requireUser();
        try {
            $missions = Mission::forUser((int) $user['id']);
        } catch (\Throwable) {
            $missions = [];
        }
        if (!User::seeksServices($user) && $missions === []) {
            flash('error', 'Cette action est disponible si vous cherchez des prestataires. Vous pouvez l\'activer dans vos paramètres.');
            redirect('/espace');
        }
        View::page('mesmissions', [
            'title' => 'Mes recherches',
            'myMissions' => $missions,
            'saved' => flash('saved'),
            'error' => flash('error'),
        ]);
    }

    public function missionDelete(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        $mission = Mission::findForUser((int) $id, (int) $user['id']);
        if (!$mission) {
            not_found('Cette recherche est introuvable.');
        }

        try {
            Mission::deleteForUser((int) $id, (int) $user['id']);
        } catch (\RuntimeException $e) {
            flash('error', user_error_message($e));
            redirect('/espace/missions');
        }

        flash('saved', 'Recherche supprimée.');
        redirect('/espace/missions');
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
            'error' => flash('error'),
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
            'trades' => self::offererTrades((int) $user['id']),
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
        $excerpt = sanitize_rich_html($request->string('excerpt'));
        $category = $request->string('category_name');
        $specialty = $request->string('specialty');
        $draft = $request->string('intent') === 'draft';

        $packagesInput = self::packageInput($request);
        $optionsInput = self::optionInput($request);

        $old = [
            'title' => $title,
            'excerpt' => $excerpt,
            'category_name' => $category,
            'specialty' => $specialty,
            'delay' => $request->string('delay'),
            'price_from' => $request->string('price_from'),
            'packages' => $packagesInput,
            'options' => $optionsInput,
        ];

        $allowedTrades = self::offererTrades((int) $user['id']);
        if ($title === '' || $category === '') {
            flash('error', 'Indiquez au moins le métier et le titre de la prestation.');
            flash('old', $old);
            redirect('/espace/prestations/creer');
        }
        if ($allowedTrades === []) {
            flash('error', 'Indiquez d\'abord vos métiers sur votre vitrine, puis choisissez-en un pour cette prestation.');
            flash('old', $old);
            redirect('/espace/prestations/creer');
        }
        if (!in_array($category, $allowedTrades, true)) {
            flash('error', 'Choisissez un métier parmi ceux de votre vitrine.');
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
                flash('error', user_error_message($e));
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
            flash('error', user_error_message($e));
            flash('old', $old);
            redirect('/espace/prestations/creer');
        }

        $packages = self::packagesFromInput($packagesInput);
        $options = self::optionsFromInput($optionsInput);
        if (self::incompletePricedRows($packagesInput, ['description', 'delay'])) {
            flash('error', 'Chaque formule doit avoir un nom et un prix.');
            flash('old', $old);
            redirect('/espace/prestations/creer');
        }
        if (self::incompletePricedRows($optionsInput)) {
            flash('error', 'Chaque option doit avoir un nom et un prix.');
            flash('old', $old);
            redirect('/espace/prestations/creer');
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
            ], $packages, $options);
        } catch (\RuntimeException $e) {
            flash('error', user_error_message($e));
            flash('old', $old);
            redirect('/espace/prestations/creer');
        } catch (\Throwable) {
            flash('error', 'La prestation n\'a pas pu être enregistrée. Réessayez dans un instant.');
            flash('old', $old);
            redirect('/espace/prestations/creer');
        }

        flash('saved', $draft ? 'Brouillon enregistré.' : 'Prestation publiée : elle apparaît dans l\'annuaire.');
        if ($draft && !empty($service['id'])) {
            redirect('/espace/prestations/' . (int) $service['id'] . '/modifier');
        }
        redirect(!empty($service['slug']) ? '/prestations/' . $service['slug'] : '/espace/prestations');
    }

    public function prestationEdit(Request $request, string $id): void
    {
        $user = Auth::requireOfferer();
        $service = Service::find((int) $id);
        if (!$service || (int) ($service['user_id'] ?? 0) !== (int) $user['id']) {
            not_found('Cette prestation est introuvable.');
        }
        $billing = self::billingState((int) $user['id']);
        $quote = ['percent' => 0, 'first_free' => true, 'completed' => 0];
        try {
            $quote = Commission::quoteForSeller((int) $user['id']);
        } catch (\Throwable) {
        }
        $old = flash('old') ?: [
            'title' => $service['title'] ?? '',
            'excerpt' => $service['excerpt'] ?? '',
            'category_name' => $service['category_name'] ?? '',
            'specialty' => $service['specialty'] ?? '',
            'delay' => $service['delay'] ?? '',
            'price_from' => $service['price_from'] ?? '',
            'packages' => array_map(static function (array $p): array {
                $id = (int) ($p['id'] ?? 0);
                return [
                    'id' => $id > 0 ? (string) $id : '',
                    'name' => $p['name'] ?? '',
                    'description' => $p['description'] ?? '',
                    'price' => $p['price'] ?? '',
                    'delay' => $p['delay'] ?? '',
                ];
            }, $service['packages'] ?? []),
            'options' => array_map(static function (array $o): array {
                $id = (int) ($o['id'] ?? 0);
                return [
                    'id' => $id > 0 ? (string) $id : '',
                    'name' => $o['name'] ?? '',
                    'price' => $o['price'] ?? '',
                ];
            }, $service['options'] ?? []),
        ];
        View::page('creer', [
            'title' => 'Modifier la prestation',
            'editing' => true,
            'serviceId' => (int) $service['id'],
            'trades' => self::offererTrades((int) $user['id'], (string) ($service['category_name'] ?? '')),
            'specialties' => array_values(array_unique(array_filter(array_merge(
                Catalog::specialties(),
                [(string) ($service['specialty'] ?? '')]
            )))),
            'commission' => (string) ($quote['first_free'] ? 0 : $quote['percent']),
            'firstMissionFree' => $quote['first_free'],
            'standardCommission' => (string) Commission::percentForSeller((int) $user['id']),
            'isFounder' => !empty($quote['founder']),
            'billingBlock' => $billing['block'],
            'error' => flash('error'),
            'old' => $old,
        ]);
    }

    public function prestationEditSave(Request $request, string $id): void
    {
        $user = Auth::requireOfferer();
        $service = Service::find((int) $id);
        if (!$service || (int) ($service['user_id'] ?? 0) !== (int) $user['id']) {
            not_found('Cette prestation est introuvable.');
        }

        $title = $request->string('title');
        $excerpt = sanitize_rich_html($request->string('excerpt'));
        $category = $request->string('category_name');
        $specialty = $request->string('specialty');
        $draft = $request->string('intent') === 'draft';

        $packagesInput = self::packageInput($request);
        $optionsInput = self::optionInput($request);
        $old = [
            'title' => $title,
            'excerpt' => $excerpt,
            'category_name' => $category,
            'specialty' => $specialty,
            'delay' => $request->string('delay'),
            'price_from' => $request->string('price_from'),
            'packages' => $packagesInput,
            'options' => $optionsInput,
        ];

        $allowedTrades = self::offererTrades((int) $user['id'], (string) ($service['category_name'] ?? ''));
        if ($title === '' || $category === '') {
            flash('error', 'Indiquez au moins le métier et le titre de la prestation.');
            flash('old', $old);
            redirect('/espace/prestations/' . (int) $id . '/modifier');
        }
        if ($allowedTrades === []) {
            flash('error', 'Indiquez d\'abord vos métiers sur votre vitrine, puis choisissez-en un pour cette prestation.');
            flash('old', $old);
            redirect('/espace/prestations/' . (int) $id . '/modifier');
        }
        if (!in_array($category, $allowedTrades, true)) {
            flash('error', 'Choisissez un métier parmi ceux de votre vitrine.');
            flash('old', $old);
            redirect('/espace/prestations/' . (int) $id . '/modifier');
        }
        $allowedSpecialties = array_values(array_unique(array_merge(
            Catalog::specialties(),
            array_filter([(string) ($service['specialty'] ?? '')])
        )));
        if ($specialty !== '' && !in_array($specialty, $allowedSpecialties, true)) {
            flash('error', 'Choisissez une spécialité dans la liste.');
            flash('old', $old);
            redirect('/espace/prestations/' . (int) $id . '/modifier');
        }

        $imagePath = $service['image_path'] ?? null;
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
            flash('error', user_error_message($e));
            flash('old', $old);
            redirect('/espace/prestations/' . (int) $id . '/modifier');
        }

        $packages = self::packagesFromInput($packagesInput);
        $options = self::optionsFromInput($optionsInput);
        if (self::incompletePricedRows($packagesInput, ['description', 'delay'])) {
            flash('error', 'Chaque formule doit avoir un nom et un prix.');
            flash('old', $old);
            redirect('/espace/prestations/' . (int) $id . '/modifier');
        }
        if (self::incompletePricedRows($optionsInput)) {
            flash('error', 'Chaque option doit avoir un nom et un prix.');
            flash('old', $old);
            redirect('/espace/prestations/' . (int) $id . '/modifier');
        }

        try {
            $updated = Service::update((int) $id, (int) $user['id'], [
                'title' => $title,
                'excerpt' => $excerpt !== '' ? $excerpt : null,
                'category_name' => $category,
                'specialty' => $specialty !== '' ? $specialty : null,
                'image_path' => $imagePath,
                'delay' => $request->string('delay') ?: null,
                'price_from' => self::money($request->string('price_from')),
                'status' => $draft ? 'draft' : 'published',
            ], $packages, $options);
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
            flash('old', $old);
            redirect('/espace/prestations/' . (int) $id . '/modifier');
        }

        flash('saved', $draft ? 'Brouillon enregistré.' : 'Prestation mise à jour.');
        if ($draft) {
            redirect('/espace/prestations/' . (int) $id . '/modifier');
        }
        redirect(!empty($updated['slug']) ? '/prestations/' . $updated['slug'] : '/espace/prestations');
    }

    public function prestationDelete(Request $request, string $id): void
    {
        $user = Auth::requireOfferer();
        $service = Service::find((int) $id);
        if (!$service || (int) ($service['user_id'] ?? 0) !== (int) $user['id']) {
            not_found('Cette prestation est introuvable.');
        }

        try {
            Service::deleteForUser((int) $id, (int) $user['id']);
        } catch (\RuntimeException $e) {
            flash('error', user_error_message($e));
            redirect('/espace/prestations');
        }

        flash('saved', 'Prestation supprimée.');
        redirect('/espace/prestations');
    }

    public function messages(Request $request): void
    {
        $user = Auth::requireUser();
        $avec = $request->int('avec');
        if ($avec && $avec === (int) $user['id']) {
            flash('error', 'Vous ne pouvez pas vous écrire à vous-même.');
            $avec = null;
        }
        if ($avec) {
            $context = [
                'subject' => $request->string('sujet'),
                'service_id' => $request->int('prestation'),
                'mission_id' => $request->int('mission'),
            ];
            try {
                $thread = Conversation::findBetween((int) $user['id'], $avec, $context);
                if (!$thread && $request->isPost()) {
                    $thread = Conversation::open((int) $user['id'], $avec, $context);
                }
                if ($thread) {
                    redirect('/espace/messages/' . (int) $thread['id']);
                }
            } catch (\Throwable $e) {
                flash('error', user_error_message($e));
            }
        }
        try {
            $threads = Conversation::forUser((int) $user['id']);
        } catch (\Throwable) {
            $threads = [];
        }
        View::page('messagerie', [
            'title' => 'Messagerie',
            'threads' => $threads,
            'thread' => null,
            'messages' => [],
            'saved' => flash('saved'),
            'error' => flash('error'),
        ]);
    }

    public function messageShow(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        $thread = Conversation::findForUser((int) $id, (int) $user['id']);
        if (!$thread) {
            not_found('Cette conversation est introuvable.');
        }
        Conversation::markRead((int) $id, (int) $user['id']);
        View::page('messagerie', [
            'title' => (string) ($thread['other']['name'] ?? 'Messagerie'),
            'threads' => Conversation::forUser((int) $user['id']),
            'thread' => $thread,
            'messages' => Conversation::messages((int) $id),
            'quoteHref' => self::quoteManageHref($thread, (int) $user['id']),
            'alreadyReported' => Conversation::hasOpenReport((int) $id, (int) $user['id']),
            'saved' => flash('saved'),
            'error' => flash('error'),
        ]);
    }

    public function messageSync(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        $thread = Conversation::findForUser((int) $id, (int) $user['id']);
        if (!$thread) {
            json_response(['error' => 'Cette conversation est introuvable.'], 404);
        }
        Conversation::markRead((int) $id, (int) $user['id']);
        $after = max(0, (int) ($request->int('after', 0) ?? 0));
        $incoming = Conversation::messages((int) $id, $after);
        $payload = [];
        foreach ($incoming as $msg) {
            $body = trim((string) ($msg['body'] ?? ''));
            $preview = $body !== ''
                ? $body
                : (!empty($msg['file_label']) ? 'Pièce jointe : ' . $msg['file_label'] : '');
            if (mb_strlen($preview) > 70) {
                $preview = mb_strimwidth($preview, 0, 70, '…');
            }
            $payload[] = [
                'id' => (int) ($msg['id'] ?? 0),
                'html' => inbox_message_html($msg, (int) $user['id']),
                'preview' => $preview,
                'when' => (string) ($msg['when'] ?? ''),
                'created_at' => (string) ($msg['created_iso'] ?? ''),
            ];
        }
        header('Cache-Control: no-store');
        json_response(['messages' => $payload]);
    }

    public function messageReport(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        try {
            Conversation::report((int) $id, (int) $user['id'], $request->string('reason'), $request->string('body'));
            flash('saved', 'Signalement reçu. L\'équipe de modération le traitera.');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
        redirect('/espace/messages/' . (int) $id);
    }

    public function messageSend(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        try {
            Conversation::send((int) $id, (int) $user['id'], $request->string('body'), $request->file('attachment'));
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
        redirect('/espace/messages/' . (int) $id);
    }

    public function messageFile(Request $request, string $id, string $mid): void
    {
        $user = Auth::requireUser();
        try {
            $file = Conversation::attachmentForUser((int) $id, (int) $mid, (int) $user['id']);
        } catch (\Throwable $e) {
            not_found($e->getMessage());
        }
        send_private_file($file['path'], $file['name'], $file['mime']);
    }

    public function applicationCreate(Request $request, string $slug): void
    {
        $user = Auth::requireOfferer();
        $mission = Mission::findBySlug($slug);
        if (!$mission) {
            not_found('Cette recherche n\'est plus disponible.');
        }
        try {
            Application::create(
                (int) $mission['id'],
                (int) $user['id'],
                self::money($request->string('price')),
                $request->string('delay') ?: null,
                $request->string('message')
            );
            flash('saved', 'Candidature envoyée. Le porteur de projet a été prévenu.');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
            flash('old', $request->all());
            redirect('/missions/' . rawurlencode($slug));
        }
        redirect('/espace/candidatures');
    }

    public function applicationAccept(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        try {
            $order = Application::accept((int) $id, (int) $user['id']);
            flash('saved', 'Candidature acceptée. La commande est ouverte.');
            redirect('/espace/suivi/' . (int) $order['id']);
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
            $app = Application::find((int) $id);
            $slug = (string) ($app['slug'] ?? '');
            redirect($slug !== '' ? '/missions/' . rawurlencode($slug) : '/espace/missions');
        }
    }

    public function applicationReject(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        try {
            Application::reject((int) $id, (int) $user['id']);
            flash('saved', 'Candidature écartée.');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
        $back = $request->string('back');
        redirect($back !== '' ? $back : '/espace/missions');
    }

    public function favorisToggle(Request $request, string $id): void
    {
        $user = Auth::requireSeeker();
        try {
            $on = Favorite::toggle((int) $user['id'], (int) $id);
            flash('saved', $on ? 'Prestation ajoutée aux favoris.' : 'Prestation retirée des favoris.');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
        $back = $request->string('back');
        redirect($back !== '' ? $back : '/espace/favoris');
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
            'error' => flash('error'),
        ]);
    }

    public function notificationsRead(Request $request): void
    {
        $user = Auth::requireUser();
        try {
            Notification::markAllRead((int) $user['id']);
            flash('saved', 'Toutes les alertes sont marquées comme lues.');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
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
        if (!is_array($item)) {
            redirect('/espace/notifications');
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
            'saved' => flash('saved'),
            'error' => flash('error'),
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
            $oid = (int) $id;
            Order::confirmByBuyer($oid, (int) $user['id'], [
                'quality' => self::reviewScore($request, 'quality', $oid),
                'efficiency' => self::reviewScore($request, 'efficiency', $oid),
                'satisfaction' => self::reviewScore($request, 'satisfaction', $oid),
                'body' => $request->string('body'),
            ]);
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
            redirect('/espace/avis');
        }
        flash('saved', 'Mission validée. Merci pour votre avis : la facture de commission est le dernier jalon du prestataire.');
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
            'socialNetworks' => Profile::SOCIAL_NETWORKS,
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
            $catalogTrades = Catalog::trades();
            foreach ($current['trades'] ?? [] as $kept) {
                $kept = (string) $kept;
                if ($kept !== '' && !in_array($kept, $catalogTrades, true) && !in_array($kept, $trades, true)) {
                    $trades[] = $kept;
                }
            }
            $genres = array_values(array_filter(
                array_filter($request->list('genres'), 'is_string'),
                static fn (string $genre): bool => in_array($genre, $allowedGenres, true)
            ));
            $catalogGenres = Catalog::specialties();
            foreach ($current['genres'] ?? [] as $kept) {
                $kept = (string) $kept;
                if ($kept !== '' && !in_array($kept, $catalogGenres, true) && !in_array($kept, $genres, true)) {
                    $genres[] = $kept;
                }
            }

            $profile = Profile::save((int) $user['id'], [
                'first_name' => $first,
                'last_name' => $last,
                'title' => $request->string('title'),
                'name_mode' => $request->string('name_mode'),
                'public_name' => $request->string('public_name'),
                'presentation' => $request->string('presentation'),
                'does' => $request->string('does'),
                'does_not' => $request->string('does_not'),
                'city' => $request->string('city'),
                'work_mode' => $request->string('work_mode'),
                'availability' => $request->string('availability'),
                'availability_status' => $request->string('availability_status'),
                'response_time' => $request->string('response_time'),
                'hourly_rate' => $request->string('hourly_rate'),
                'rate_kind' => $request->string('rate_kind'),
                'rate_note' => $request->string('rate_note'),
                'website' => $request->string('website'),
                'socials' => self::rows($request->list('socials'), ['network', 'url']),
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
            flash('error', user_error_message($e));
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
            flash('error', user_error_message($e));
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
            'email' => (string) ($user['email'] ?? ''),
            'avatarSrc' => user_avatar_src($user),
            'seeksChecked' => User::seeksServices($user),
            'offersChecked' => User::offersServices($user),
            'notifyMessages' => !isset($user['notify_messages']) || (int) $user['notify_messages'] === 1,
            'notifyJalons' => !isset($user['notify_jalons']) || (int) $user['notify_jalons'] === 1,
            'notifyMissions' => !isset($user['notify_missions']) || (int) $user['notify_missions'] === 1,
            'notifyNewsletter' => !empty($user['notify_newsletter'])
                || (($sub = Newsletter::findByEmail((string) ($user['email'] ?? '')))
                    && ($sub['status'] ?? '') === Newsletter::STATUS_CONFIRMED),
            'companyName' => (string) ($user['company_name'] ?? ''),
            'legalForm' => (string) ($user['legal_form'] ?? ''),
            'legalForms' => User::legalForms(),
            'siren' => (string) ($user['siren'] ?? ''),
            'siret' => (string) ($user['siret'] ?? ''),
            'vatNumber' => (string) ($user['vat_number'] ?? ''),
            'vatExempt' => !empty($user['vat_exempt']),
            'einvoiceRouting' => (string) ($user['einvoice_routing'] ?? ''),
            'billingAddress' => (string) ($user['billing_address'] ?? ''),
            'iban' => (string) ($user['iban'] ?? ''),
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

        $email = strtolower($request->string('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Merci de renseigner un e-mail valide.');
            redirect('/espace/parametres');
        }
        $taken = User::findByEmail($email);
        if ($taken && (int) $taken['id'] !== (int) $user['id']) {
            flash('error', 'Un compte existe déjà avec cet e-mail.');
            redirect('/espace/parametres');
        }

        $wasOffering = User::offersServices($user);
        if ($offers && !$wasOffering && !$request->bool('charte_ia')) {
            flash('error', 'Pour proposer vos services, l\'engagement sans IA générative est obligatoire.');
            redirect('/espace/parametres');
        }

        if ($offers) {
            User::ensureProfile((int) $user['id']);
        }

        try {
            User::storeAvatar((int) $user['id'], $request->file('avatar'));
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
            redirect('/espace/parametres');
        }

        User::update((int) $user['id'], [
            'first_name' => $request->string('first_name', $user['first_name']),
            'last_name' => $request->string('last_name', $user['last_name']),
            'email' => $email,
            'seeks_services' => $seeks ? 1 : 0,
            'offers_services' => $offers ? 1 : 0,
            'role' => ($user['role'] ?? '') === 'admin'
                ? 'admin'
                : User::roleFromIntents($seeks, $offers),
        ]);
        if ($offers && !$wasOffering) {
            try {
                LegalAcceptance::record((int) $user['id'], 'charte_ia', 'parametres');
            } catch (\Throwable) {
            }
        }
        flash('saved', true);
        redirect('/espace/parametres');
    }

    public function parametresNotifs(Request $request): void
    {
        $user = Auth::requireUser();
        try {
            $wantNews = $request->bool('notify_newsletter');
            User::update((int) $user['id'], [
                'notify_messages' => $request->bool('notify_messages') ? 1 : 0,
                'notify_jalons' => $request->bool('notify_jalons') ? 1 : 0,
                'notify_missions' => $request->bool('notify_missions') ? 1 : 0,
                'notify_newsletter' => $wantNews ? 1 : 0,
            ]);
            try {
                if ($wantNews) {
                    Newsletter::subscribe((string) $user['email'], 'account', (int) $user['id'], true);
                } else {
                    Newsletter::unsubscribeEmail((string) $user['email']);
                }
            } catch (\Throwable) {
            }
            flash('saved', true);
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
        redirect('/espace/parametres');
    }

    public function parametresBilling(Request $request): void
    {
        $user = Auth::requireUser();
        $siren = preg_replace('/\D+/', '', $request->string('siren')) ?? '';
        $siret = preg_replace('/\D+/', '', $request->string('siret')) ?? '';
        $vat = strtoupper(preg_replace('/\s+/', '', $request->string('vat_number')) ?? '');
        $iban = strtoupper(preg_replace('/\s+/', '', $request->string('iban')) ?? '');
        $routing = strtoupper(preg_replace('/\s+/', '', $request->string('einvoice_routing')) ?? '');
        $legalForm = $request->string('legal_form');
        if ($siren === '' && strlen($siret) === 14) {
            $siren = substr($siret, 0, 9);
        }
        if ($siren !== '' && !preg_match('/^\d{9}$/', $siren)) {
            flash('error', 'Le SIREN doit contenir 9 chiffres.');
            redirect('/espace/parametres');
        }
        if ($siret !== '' && !preg_match('/^\d{14}$/', $siret)) {
            flash('error', 'Le SIRET doit contenir 14 chiffres.');
            redirect('/espace/parametres');
        }
        if ($siren !== '' && $siret !== '' && !str_starts_with($siret, $siren)) {
            flash('error', 'Le SIRET doit commencer par le SIREN.');
            redirect('/espace/parametres');
        }
        if ($vat !== '' && !preg_match('/^[A-Z]{2}[A-Z0-9]{2,13}$/', $vat)) {
            flash('error', 'Le numéro de TVA n\'est pas au bon format.');
            redirect('/espace/parametres');
        }
        if ($iban !== '' && !preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $iban)) {
            flash('error', 'L\'IBAN n\'est pas au bon format.');
            redirect('/espace/parametres');
        }
        if ($routing !== '' && !preg_match('/^[A-Z0-9._-]{1,50}$/', $routing)) {
            flash('error', 'Le code de routage n\'est pas au bon format.');
            redirect('/espace/parametres');
        }
        if ($legalForm !== '' && !isset(User::legalForms()[$legalForm])) {
            flash('error', 'La forme juridique n\'est pas reconnue.');
            redirect('/espace/parametres');
        }
        User::update((int) $user['id'], [
            'company_name' => $request->string('company_name') ?: null,
            'legal_form' => $legalForm !== '' ? $legalForm : null,
            'siren' => $siren !== '' ? $siren : null,
            'siret' => $siret !== '' ? $siret : null,
            'vat_number' => $vat !== '' ? $vat : null,
            'vat_exempt' => $request->bool('vat_exempt') ? 1 : 0,
            'einvoice_routing' => $routing !== '' ? $routing : null,
            'billing_address' => $request->string('billing_address') ?: null,
            'iban' => $iban !== '' ? $iban : null,
        ]);
        flash('saved', true);
        redirect('/espace/parametres');
    }

    public function parametresExport(Request $request): void
    {
        $user = Auth::requireUser();
        $payload = User::exportPayload((int) $user['id']);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="acteursdulivre-donnees-' . (int) $user['id'] . '.json"');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    public function parametresClose(Request $request): void
    {
        $user = Auth::requireUser();
        $confirm = $request->string('confirm');
        if ($confirm !== 'CLOTURER') {
            flash('error', 'Saisissez CLOTURER pour confirmer la fermeture du compte.');
            redirect('/espace/parametres');
        }
        try {
            User::closeAccount((int) $user['id']);
            Auth::logout();
            flash('saved', 'Votre compte a été clôturé.');
            redirect('/');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
            redirect('/espace/parametres');
        }
    }

    public function parametresPassword(Request $request): void
    {
        $user = Auth::requireUser();
        $current = $request->string('current_password');
        $password = $request->string('password');
        $confirm = $request->string('password_confirmation');
        $hash = (string) ($user['password'] ?? '');
        if ($hash !== '' && !password_verify($current, $hash)) {
            flash('error', 'Le mot de passe actuel est incorrect.');
            redirect('/espace/parametres');
        }
        if (strlen($password) < 8) {
            flash('error', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
            redirect('/espace/parametres');
        }
        if ($password !== $confirm) {
            flash('error', 'Les deux mots de passe ne correspondent pas.');
            redirect('/espace/parametres');
        }
        User::setPassword((int) $user['id'], $password);
        flash('saved', true);
        redirect('/espace/parametres');
    }

    public function vitrineVerification(Request $request): void
    {
        $user = Auth::requireOfferer();
        try {
            Profile::storeVerificationDoc((int) $user['id'], $request->file('justificatif'), $request->string('note'));
            flash('saved', 'Justificatif envoyé. L\'équipe le vérifiera sous peu.');
        } catch (\Throwable $e) {
            flash('error', user_error_message($e));
        }
        redirect('/espace/vitrine');
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
        $commissionRate = null;
        try {
            $commissionRate = Commission::accountState((int) $user['id']);
        } catch (\Throwable) {
        }
        View::page('facturation', [
            'title' => 'Facturation',
            'orders' => $orders,
            'invoices' => $invoices,
            'totalAmount' => $total,
            'dueAmount' => $due,
            'billingBlock' => $billing['block'],
            'billingWarning' => $billing['warning'],
            'commissionRate' => $commissionRate,
            'needsSiren' => trim((string) ($user['siren'] ?? '')) === ''
                && trim((string) ($user['siret'] ?? '')) === '',
        ]);
    }

    public function facturePdf(Request $request, string $id): void
    {
        $user = Auth::requireOfferer();
        $invoice = Invoice::findForSeller((int) $id, (int) $user['id']);
        if (!$invoice) {
            not_found('Cette facture est introuvable.');
        }
        View::render('pages/facture-pdf', [
            'title' => 'Facture ' . $invoice['number'],
            'invoice' => $invoice,
            'seller' => $user,
        ], null);
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

    /** @return list<array<string, mixed>> */
    private static function jalonTodos(int $userId): array
    {
        try {
            return OrderMilestone::dueActions($userId);
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array{name: ?string, path: ?string} */
    private static function storeMilestoneFile(Request $request, int $orderId, string $code): array
    {
        $file = $request->file('document');
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['name' => null, 'path' => null];
        }
        $ext = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'odt'];
        $stored = store_private_upload($file, 'orders/' . $orderId, $ext, 8 * 1024 * 1024);
        if ($stored === null) {
            return ['name' => null, 'path' => null];
        }
        return [
            'name' => (string) ($stored['name'] ?? $file['name'] ?? 'Document'),
            'path' => (string) ($stored['path'] ?? ''),
        ];
    }

    /** @param array<string, mixed> $thread */
    private static function quoteManageHref(array $thread, int $userId): string
    {
        $orderId = (int) ($thread['order_id'] ?? 0);
        if ($orderId < 1) {
            return '';
        }
        try {
            $order = Order::findForUser($orderId, $userId);
        } catch (\Throwable) {
            return '';
        }
        if (!$order || !OrderMilestone::quoteNeedsManagement($order)) {
            return '';
        }

        return '/espace/suivi/' . $orderId;
    }

    /** @param array{name?: ?string, path?: ?string} $file */
    private static function pingJalonThread(int $orderId, int $userId, string $code, array $file = []): void
    {
        $order = Order::findForUser($orderId, $userId);
        if (!$order) {
            return;
        }
        $body = Conversation::jalonPings()[$code] ?? null;
        if ($body === null) {
            return;
        }
        try {
            $thread = Conversation::open((int) $order['buyer_id'], (int) $order['seller_id'], [
                'order_id' => $orderId,
            ]);
            $publicUpload = $code === 'deliver' && trim((string) ($file['path'] ?? '')) !== ''
                ? $file
                : null;
            Conversation::send((int) $thread['id'], $userId, $body, null, $publicUpload);
        } catch (\Throwable) {
        }
    }

    private static function pingOrderThread(int $orderId, int $userId, string $body, bool $notify = false): void
    {
        $order = Order::findForUser($orderId, $userId);
        if (!$order) {
            return;
        }
        try {
            $thread = Conversation::open((int) $order['buyer_id'], (int) $order['seller_id'], [
                'order_id' => $orderId,
            ]);
            Conversation::send((int) $thread['id'], $userId, $body, null, null, $notify);
        } catch (\Throwable) {
        }
    }

    /** @return list<string> */
    private static function offererTrades(int $userId, string $keep = ''): array
    {
        $profile = null;
        try {
            $profile = Profile::findByUser($userId);
        } catch (\Throwable) {
        }
        $chosen = [];
        foreach ($profile['trades'] ?? [] as $trade) {
            if (is_string($trade) && $trade !== '') {
                $chosen[] = $trade;
            }
        }
        $chosen = array_values(array_unique($chosen));
        if ($keep !== '' && !in_array($keep, $chosen, true)) {
            $chosen[] = $keep;
        }
        return $chosen;
    }

    /**
     * @return list<array{id: string, name: string, description: string, price: string, delay: string}>
     */
    private static function packageInput(Request $request): array
    {
        $out = [];
        foreach ($request->list('packages') as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int) ($row['id'] ?? 0);
            $out[] = [
                'id' => $id > 0 ? (string) $id : '',
                'name' => trim((string) ($row['name'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
                'price' => trim((string) ($row['price'] ?? '')),
                'delay' => trim((string) ($row['delay'] ?? '')),
            ];
        }
        return $out;
    }

    /**
     * @param list<array{id?: string, name: string, description: string, price: string, delay: string}> $input
     * @return list<array{id: int, name: string, description: string, price: int, delay: string}>
     */
    private static function packagesFromInput(array $input): array
    {
        $packages = [];
        foreach ($input as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $price = self::money((string) ($row['price'] ?? ''));
            if ($name === '' || $price === null) {
                continue;
            }
            $packages[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => $name,
                'description' => $row['description'] ?? '',
                'price' => $price,
                'delay' => $row['delay'] ?? '',
            ];
        }
        return $packages;
    }

    /**
     * @param list<array<string, string>> $input
     * @param list<string> $extraKeys
     */
    private static function incompletePricedRows(array $input, array $extraKeys = []): bool
    {
        foreach ($input as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $priceRaw = trim((string) ($row['price'] ?? ''));
            $extra = '';
            foreach ($extraKeys as $key) {
                $extra .= trim((string) ($row[$key] ?? ''));
            }
            if ($name === '' && $priceRaw === '' && $extra === '') {
                continue;
            }
            if ($name === '' || self::money($priceRaw) === null) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return list<array{id: string, name: string, price: string}>
     */
    private static function optionInput(Request $request): array
    {
        $out = [];
        foreach ($request->list('options') as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int) ($row['id'] ?? 0);
            $out[] = [
                'id' => $id > 0 ? (string) $id : '',
                'name' => trim((string) ($row['name'] ?? '')),
                'price' => trim((string) ($row['price'] ?? '')),
            ];
        }
        return $out;
    }

    /**
     * @param list<array{id?: string, name: string, price: string}> $input
     * @return list<array{id: int, name: string, price: int}>
     */
    private static function optionsFromInput(array $input): array
    {
        $options = [];
        foreach ($input as $row) {
            $name = $row['name'] ?? '';
            $price = self::money((string) ($row['price'] ?? ''));
            if ($name === '' || $price === null) {
                continue;
            }
            $options[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => $name,
                'price' => $price,
            ];
        }
        return $options;
    }

    /**
     * @return array{name: ?string, path: ?string}
     */
    private static function missionAttachmentFromRequest(
        Request $request,
        ?string $currentPath = null,
        ?string $currentName = null
    ): array {
        $stored = store_private_upload(
            $request->file('attachment'),
            'missions',
            ['pdf', 'doc', 'docx', 'odt', 'txt'],
            20 * 1024 * 1024
        );
        if ($stored) {
            if ($currentPath) {
                delete_upload($currentPath);
            }
            $original = upload_safe_name((string) ($request->file('attachment')['name'] ?? ''));
            return [
                'name' => $original !== '' ? $original : (string) $stored['name'],
                'path' => (string) $stored['path'],
            ];
        }
        if ($request->string('remove_attachment') === '1') {
            if ($currentPath) {
                delete_upload($currentPath);
            }
            return ['name' => null, 'path' => null];
        }
        return ['name' => $currentName, 'path' => $currentPath];
    }

    private static function reviewScore(Request $request, string $key, int $orderId): int
    {
        $value = $request->input($key);
        if (is_array($value)) {
            return (int) ($value[$orderId] ?? $value[(string) $orderId] ?? 0);
        }
        return (int) $value;
    }

    private static function money(string $value): ?int
    {
        $value = trim(str_replace(["\u{00A0}", ' '], '', $value));
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{1,3}(?:\.\d{3})+(?:,\d+)?$/', $value)) {
            $value = str_replace('.', '', $value);
        }
        $normalized = str_replace(',', '.', $value);
        if (is_numeric($normalized)) {
            $amount = (int) round((float) $normalized);
            return $amount < 0 ? null : $amount;
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
