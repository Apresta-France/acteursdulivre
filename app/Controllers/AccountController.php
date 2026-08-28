<?php

declare(strict_types=1);

namespace Adl\Controllers;

use Adl\Core\Auth;
use Adl\Core\Request;
use Adl\Core\View;
use Adl\Data\Catalog;
use Adl\Models\Application;
use Adl\Models\Favorite;
use Adl\Models\Mission;
use Adl\Models\Notification;
use Adl\Models\Order;
use Adl\Models\PortfolioItem;
use Adl\Models\Profile;
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

        try {
            if (User::offersServices($user)) {
                $profile = Profile::findByUser((int) $user['id']);
                if ($profile) {
                    $profileCompletion = (int) ($profile['completion'] ?? 0);
                    $profileHref = Profile::publicHref($profile);
                    $availabilityStatus = Profile::normalizeStatus($profile['availability_status'] ?? null);
                    $availabilityNote = trim((string) ($profile['availability'] ?? ''));
                }
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
        ]);
    }

    public function publier(Request $request): void
    {
        $user = Auth::requireSeeker();
        View::page('publier', [
            'title' => 'Publier une mission',
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

        $old = [
            'title' => $title,
            'brief' => $brief,
            'category_name' => $category,
            'volume' => $request->string('volume'),
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
            'volume' => $request->string('volume') ?: null,
            'budget_min' => self::money($request->string('budget_min')),
            'budget_max' => self::money($request->string('budget_max')),
            'deadline' => $request->string('deadline') ?: null,
            'attachment_name' => $attachmentName,
            'attachment_path' => $attachmentPath,
            'status' => $draft ? 'draft' : 'open',
        ]);

        flash('saved', $draft ? 'Brouillon enregistré.' : 'Mission publiée : les prestataires peuvent maintenant y répondre.');
        redirect(!empty($mission['slug']) ? '/missions/' . $mission['slug'] : '/espace/missions');
    }

    public function commande(Request $request): void
    {
        Auth::requireSeeker();
        View::page('commande', ['title' => 'Commande & paiement']);
    }

    public function suivi(Request $request): void
    {
        Auth::requireUser();
        View::page('suivi', ['title' => 'Suivi de commande']);
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
            'title' => 'Mes missions',
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
        View::page('mesprestations', [
            'title' => 'Mes prestations',
            'myServices' => $services,
            'saved' => flash('saved'),
        ]);
    }

    public function creer(Request $request): void
    {
        Auth::requireOfferer();
        View::page('creer', [
            'title' => 'Créer une prestation',
            'trades' => Catalog::trades(),
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
        $draft = $request->string('intent') === 'draft';

        $old = [
            'title' => $title,
            'excerpt' => $excerpt,
            'category_name' => $category,
            'delay' => $request->string('delay'),
            'price_from' => $request->string('price_from'),
        ];

        if ($title === '' || $category === '') {
            flash('error', 'Indiquez au moins le métier et le titre de la prestation.');
            flash('old', $old);
            redirect('/espace/prestations/creer');
        }

        $packages = [];
        foreach ($request->list('packages') as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $packages[] = [
                'name' => $name,
                'description' => trim((string) ($row['description'] ?? '')),
                'price' => self::money((string) ($row['price'] ?? '')) ?? 0,
                'delay' => trim((string) ($row['delay'] ?? '')),
            ];
        }

        $service = Service::create((int) $user['id'], [
            'title' => $title,
            'excerpt' => $excerpt !== '' ? $excerpt : null,
            'category_name' => $category,
            'delay' => $request->string('delay') ?: null,
            'price_from' => self::money($request->string('price_from')),
            'status' => $draft ? 'draft' : 'published',
        ], $packages);

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
        Auth::requireUser();
        View::page('avis', ['title' => 'Laisser un avis']);
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
            'trades' => Profile::TRADES,
            'genres' => Profile::GENRES,
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
                'genres' => array_values(array_filter($request->list('genres'), 'is_string')),
                'languages_list' => self::rows($request->list('languages_list'), ['langue', 'niveau']),
                'experiences' => self::rows($request->list('experiences'), ['periode', 'poste', 'lieu', 'detail']),
                'education' => self::rows($request->list('education'), ['annee', 'intitule', 'ecole']),
            ]);

            User::update((int) $user['id'], [
                'first_name' => $first,
                'last_name' => $last,
            ]);

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
                : 'Votre vitrine affiche Disponible pour de nouvelles missions.'
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
            'seeksChecked' => User::seeksServices($user),
            'offersChecked' => User::offersServices($user),
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
        $total = 0;
        foreach ($orders as $order) {
            $total += (int) ($order['amount'] ?? 0);
        }
        View::page('facturation', [
            'title' => 'Facturation',
            'orders' => $orders,
            'totalAmount' => $total,
        ]);
    }

    private static function money(string $value): ?int
    {
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
