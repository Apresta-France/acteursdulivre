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
use Adl\Models\Mission;
use Adl\Models\Notification;
use Adl\Models\Order;
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

        $packageId = $request->int('formule');
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

        $old = flash('old') ?: [];
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
        $pickedOptions = Service::pickOptions($service, $request->list('options'));
        $amount = $selected
            ? (int) ($selected['price'] ?? 0)
            : (int) ($service['price_from'] ?? 0);
        foreach ($pickedOptions as $option) {
            $amount += (int) ($option['price'] ?? 0);
        }
        $brief = $request->string('brief');

        try {
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
                Conversation::send((int) $thread['id'], (int) $user['id'], $brief);
            }
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
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
        $order = Order::findForUser((int) $id, (int) $user['id']);
        if (!$order && ($user['role'] ?? '') === 'admin') {
            $order = Order::find((int) $id);
        }
        if (!$order) {
            not_found('Cette commande est introuvable.');
        }
        $thread = null;
        try {
            $thread = Conversation::open((int) $order['buyer_id'], (int) $order['seller_id'], [
                'order_id' => (int) $order['id'],
            ]);
        } catch (\Throwable) {
        }
        View::page('suivi-detail', [
            'title' => 'Suivi ' . $order['num'],
            'order' => $order,
            'milestones' => $order['milestones'] ?? [],
            'action' => OrderMilestone::actionFor($order, (int) $user['id']),
            'isBuyer' => (int) $order['buyer_id'] === (int) $user['id'],
            'isSeller' => (int) $order['seller_id'] === (int) $user['id'],
            'threadHref' => $thread['href'] ?? '/espace/messages',
            'saved' => flash('saved'),
            'error' => flash('error'),
        ]);
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
            flash('error', $e->getMessage());
        }
        redirect('/espace/suivi/' . (int) $id);
    }

    public function suiviJalon(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        $code = $request->string('code');
        try {
            $file = self::storeMilestoneFile($request, (int) $id, $code);
            OrderMilestone::complete((int) $id, (int) $user['id'], $code, [
                'amount' => self::money($request->string('amount')),
                'deposit_amount' => self::money($request->string('deposit_amount')),
                'delay' => $request->string('delay'),
                'note' => $request->string('note'),
                'file_name' => $file['name'] ?? null,
                'file_path' => $file['path'] ?? null,
            ]);
            self::pingJalonThread((int) $id, (int) $user['id'], $code);
            flash('saved', OrderMilestone::flashFor($code));
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/espace/suivi/' . (int) $id);
    }

    public function suiviRefuseQuote(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        try {
            OrderMilestone::refuseQuote((int) $id, (int) $user['id']);
            flash('saved', 'Devis refusé. La commande est clôturée. Vous pouvez en ouvrir une autre ou écrire au prestataire.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
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
            flash('error', $e->getMessage());
        }
        redirect('/espace/suivi/' . (int) $id);
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

        $packages = self::packagesFromInput($packagesInput);
        $options = self::optionsFromInput($optionsInput);
        if (self::incompletePricedRows($packagesInput, ['description', 'delay'])) {
            flash('error', 'Chaque formule doit avoir un nom et un prix.');
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
                return [
                    'name' => $p['name'] ?? '',
                    'description' => $p['description'] ?? '',
                    'price' => $p['price'] ?? '',
                    'delay' => $p['delay'] ?? '',
                ];
            }, $service['packages'] ?? []),
            'options' => array_map(static function (array $o): array {
                return [
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
            'specialties' => Catalog::specialties(),
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
            flash('error', $e->getMessage());
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
            flash('error', $e->getMessage());
            flash('old', $old);
            redirect('/espace/prestations/' . (int) $id . '/modifier');
        }

        flash('saved', $draft ? 'Brouillon enregistré.' : 'Prestation mise à jour.');
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
            flash('error', $e->getMessage());
            $from = $request->string('from');
            redirect($from === 'edit'
                ? '/espace/prestations/' . (int) $id . '/modifier'
                : '/espace/prestations');
        }

        flash('saved', 'Prestation supprimée.');
        redirect('/espace/prestations');
    }

    public function messages(Request $request): void
    {
        $user = Auth::requireUser();
        $avec = $request->int('avec');
        if ($avec) {
            try {
                $thread = Conversation::open((int) $user['id'], $avec, [
                    'subject' => $request->string('sujet'),
                    'service_id' => $request->int('prestation'),
                    'mission_id' => $request->int('mission'),
                ]);
                redirect('/espace/messages/' . (int) $thread['id']);
            } catch (\Throwable $e) {
                flash('error', $e->getMessage());
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
            'saved' => flash('saved'),
            'error' => flash('error'),
        ]);
    }

    public function messageSend(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        try {
            Conversation::send((int) $id, (int) $user['id'], $request->string('body'), $request->file('attachment'));
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
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
            flash('error', $e->getMessage());
            flash('old', $request->all());
            redirect('/missions/' . rawurlencode($slug));
        }
        redirect('/espace/candidatures');
    }

    public function applicationAccept(Request $request, string $id): void
    {
        $user = Auth::requireSeeker();
        try {
            $order = Application::accept((int) $id, (int) $user['id']);
            flash('saved', 'Candidature acceptée. La commande est ouverte.');
            redirect('/espace/suivi/' . (int) $order['id']);
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/espace/missions');
        }
    }

    public function applicationReject(Request $request, string $id): void
    {
        $user = Auth::requireSeeker();
        try {
            Application::reject((int) $id, (int) $user['id']);
            flash('saved', 'Candidature écartée.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
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
            flash('error', $e->getMessage());
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
            'notifyMessages' => !isset($user['notify_messages']) || (int) $user['notify_messages'] === 1,
            'notifyJalons' => !isset($user['notify_jalons']) || (int) $user['notify_jalons'] === 1,
            'notifyMissions' => !isset($user['notify_missions']) || (int) $user['notify_missions'] === 1,
            'notifyNewsletter' => !empty($user['notify_newsletter']),
            'companyName' => (string) ($user['company_name'] ?? ''),
            'siret' => (string) ($user['siret'] ?? ''),
            'vatNumber' => (string) ($user['vat_number'] ?? ''),
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

    public function parametresNotifs(Request $request): void
    {
        $user = Auth::requireUser();
        try {
            User::update((int) $user['id'], [
                'notify_messages' => $request->bool('notify_messages') ? 1 : 0,
                'notify_jalons' => $request->bool('notify_jalons') ? 1 : 0,
                'notify_missions' => $request->bool('notify_missions') ? 1 : 0,
                'notify_newsletter' => $request->bool('notify_newsletter') ? 1 : 0,
            ]);
            flash('saved', true);
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/espace/parametres');
    }

    public function parametresBilling(Request $request): void
    {
        $user = Auth::requireUser();
        $siret = preg_replace('/\s+/', '', $request->string('siret')) ?? '';
        $vat = strtoupper(preg_replace('/\s+/', '', $request->string('vat_number')) ?? '');
        $iban = strtoupper(preg_replace('/\s+/', '', $request->string('iban')) ?? '');
        if ($siret !== '' && !preg_match('/^\d{14}$/', $siret)) {
            flash('error', 'Le SIRET doit contenir 14 chiffres.');
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
        User::update((int) $user['id'], [
            'company_name' => $request->string('company_name') ?: null,
            'siret' => $siret !== '' ? $siret : null,
            'vat_number' => $vat !== '' ? $vat : null,
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
            flash('error', $e->getMessage());
            redirect('/espace/parametres');
        }
    }

    public function parametresPassword(Request $request): void
    {
        $user = Auth::requireUser();
        if (User::isOauthOnly($user)) {
            flash('error', 'Ce compte se connecte avec Google ou Facebook. Ajoutez un mot de passe uniquement si vous en créez un nouveau.');
        }
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
            flash('error', 'Les deux mot de passe ne correspondent pas.');
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
            flash('error', $e->getMessage());
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
        $stored = store_upload($file, 'orders/' . $orderId, $ext, 8 * 1024 * 1024);
        if ($stored === null) {
            return ['name' => null, 'path' => null];
        }
        return [
            'name' => (string) ($file['name'] ?? 'Document'),
            'path' => $stored,
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

    private static function pingJalonThread(int $orderId, int $userId, string $code): void
    {
        $order = Order::findForUser($orderId, $userId);
        if (!$order) {
            return;
        }
        $messages = [
            'quote' => 'Devis envoyé dans le suivi de commande.',
            'quote_accept' => 'Devis accepté. Nous continuons les jalons dans le suivi.',
            'deposit_invoice' => 'Facture d’acompte déposée dans le suivi de commande.',
            'deposit_paid' => 'J’ai réglé l’acompte hors plateforme : je le confirme dans le suivi.',
            'deposit_ack' => 'Acompte bien reçu, je démarre la mission.',
            'deliver' => 'Prestation livrée : voir le suivi de commande.',
            'final_invoice' => 'Facture de solde déposée dans le suivi de commande.',
            'final_paid' => 'J’ai réglé le solde hors plateforme : je le confirme dans le suivi.',
        ];
        $body = $messages[$code] ?? null;
        if ($body === null) {
            return;
        }
        try {
            $thread = Conversation::open((int) $order['buyer_id'], (int) $order['seller_id'], [
                'order_id' => $orderId,
            ]);
            Conversation::send((int) $thread['id'], $userId, $body);
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
     * @return list<array{name: string, description: string, price: string, delay: string}>
     */
    private static function packageInput(Request $request): array
    {
        $out = [];
        foreach ($request->list('packages') as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'name' => trim((string) ($row['name'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
                'price' => trim((string) ($row['price'] ?? '')),
                'delay' => trim((string) ($row['delay'] ?? '')),
            ];
        }
        return $out;
    }

    /**
     * @param list<array{name: string, description: string, price: string, delay: string}> $input
     * @return list<array{name: string, description: string, price: int, delay: string}>
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
     * @return list<array{name: string, price: string}>
     */
    private static function optionInput(Request $request): array
    {
        $out = [];
        foreach ($request->list('options') as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'name' => trim((string) ($row['name'] ?? '')),
                'price' => trim((string) ($row['price'] ?? '')),
            ];
        }
        return $out;
    }

    /**
     * @param list<array{name: string, price: string}> $input
     * @return list<array{name: string, price: int}>
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
                'name' => $name,
                'price' => $price,
            ];
        }
        return $options;
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
