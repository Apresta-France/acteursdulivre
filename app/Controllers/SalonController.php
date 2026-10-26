<?php

declare(strict_types=1);

namespace Adl\Controllers;

use Adl\Core\Auth;
use Adl\Core\BotGuard;
use Adl\Core\Request;
use Adl\Core\View;
use Adl\Data\Seo;
use Adl\Data\Share;
use Adl\Models\Analytics;
use Adl\Models\Salon;
use Adl\Models\SalonClaim;
use Throwable;

final class SalonController
{
    // ------------------------------------------------------------------
    // Revendication d'une fiche existante par l'organisateur
    // ------------------------------------------------------------------

    public function claimForm(Request $request, string $slug): void
    {
        $user = Auth::requireUser();
        $salon = null;
        try {
            $salon = Salon::find($slug);
        } catch (Throwable) {
        }
        if (!$salon) {
            not_found('Ce salon n\'est pas dans l\'agenda.');
        }

        View::page('salon-revendiquer', [
            'title' => 'Revendiquer la fiche ' . $salon['name'],
            'salon' => $salon,
            'pendingClaim' => $this->safePendingClaim((int) $salon['id'], (int) $user['id']),
            'alreadyOwned' => !empty($salon['owner_user_id']),
            'isOwner' => (int) ($salon['owner_user_id'] ?? 0) === (int) $user['id'],
            'suggestedEmail' => str_contains((string) $user['email'], '@') ? (string) $user['email'] : '',
            'error' => flash('error'),
            'meta' => Seo::build(
                'Revendiquer la fiche ' . $salon['name'],
                'Prenez la main sur la fiche de votre salon ou festival.',
                Share::absolute($salon['href'] . '/revendiquer'),
                'website',
                null,
                ['robots' => Seo::ROBOTS_NONE]
            ),
        ]);
        unset($_SESSION['_old']);
    }

    public function claim(Request $request, string $slug): void
    {
        $user = Auth::requireUser();
        $salon = null;
        try {
            $salon = Salon::find($slug);
        } catch (Throwable) {
        }
        if (!$salon) {
            not_found('Ce salon n\'est pas dans l\'agenda.');
        }
        $back = $salon['href'] . '/revendiquer';
        $guard = BotGuard::consume('salon-claim', $request);
        if ($guard !== BotGuard::OK) {
            flash('error', BotGuard::RETRY_MESSAGE);
            $_SESSION['_old'] = $request->all();
            redirect($back);
        }
        if (rate_limited('salon-claim-user', (string) $user['id'], 6, 3600)) {
            flash('error', BotGuard::TOO_MANY_MESSAGE);
            $_SESSION['_old'] = $request->all();
            redirect($back);
        }
        if (!$request->bool('attest')) {
            flash('error', 'Merci de confirmer que vous êtes habilité à représenter cette manifestation.');
            $_SESSION['_old'] = $request->all();
            redirect($back);
        }
        try {
            SalonClaim::create($salon, $user, [
                'role_title' => $request->string('role_title'),
                'company_email' => $request->string('company_email'),
                'phone' => $request->string('phone'),
                'message' => $request->string('message'),
            ]);
            Analytics::action('salon_revendication');
            unset($_SESSION['_old']);
            flash('saved', 'Demande envoyée. L\'équipe vérifie chaque demande à la main et vous prévient par e-mail dès qu\'elle est traitée.');
            redirect($salon['href']);
        } catch (Throwable $e) {
            flash('error', user_error_message($e));
            $_SESSION['_old'] = $request->all();
            redirect($back);
        }
    }

    // ------------------------------------------------------------------
    // Espace membre : gérer sa fiche salon
    // ------------------------------------------------------------------

    public function espace(Request $request): void
    {
        $user = Auth::requireUser();
        $salon = null;
        $claims = [];
        try {
            $salon = Salon::findForOwner((int) $user['id']);
            $claims = SalonClaim::forUser((int) $user['id']);
        } catch (Throwable) {
        }
        $categories = [];
        try {
            $categories = Salon::categoryLabels();
        } catch (Throwable) {
        }
        View::page('espace-salon', [
            'title' => 'Mon salon',
            'salon' => $salon,
            'claims' => $claims,
            'categories' => $categories,
            'error' => flash('error'),
            'saved' => flash('saved'),
            'meta' => Seo::build(
                'Mon salon',
                'Gérez la fiche de votre salon dans l\'agenda.',
                Share::absolute('/espace/salon'),
                'website',
                null,
                ['robots' => Seo::ROBOTS_NONE]
            ),
        ]);
    }

    public function espaceSave(Request $request): void
    {
        $user = Auth::requireUser();
        $salon = null;
        try {
            $salon = Salon::findForOwner((int) $user['id']);
        } catch (Throwable) {
        }
        if (!$salon) {
            flash('error', 'Aucune fiche ne vous est attribuée pour le moment.');
            redirect('/espace/salon');
        }
        try {
            $data = [
                'name' => $request->string('name'),
                'category' => $request->string('category'),
                'starts_on' => $request->string('starts_on'),
                'ends_on' => $request->string('ends_on'),
                'dates_confirmed' => $request->bool('dates_confirmed'),
                'city' => $request->string('city'),
                'region' => $request->string('region'),
                'country' => $request->string('country'),
                'venue' => $request->string('venue'),
                'website' => $request->string('website'),
                'organizer' => $request->string('organizer'),
                'ticket' => $request->string('ticket'),
                'audience' => $request->string('audience'),
                'contact' => $request->string('contact'),
                'socials' => $request->string('socials'),
                'description' => $request->string('description'),
                'program' => $request->string('program'),
                'exhibitors_list' => $request->string('exhibitors_list'),
            ];
            $logo = store_upload($request->file('logo'), 'salons', ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
            if ($logo !== null) {
                if (!empty($salon['logo_path'])) {
                    delete_upload((string) $salon['logo_path']);
                }
                $data['logo_path'] = $logo;
            } elseif ($request->bool('remove_logo') && !empty($salon['logo_path'])) {
                delete_upload((string) $salon['logo_path']);
                $data['logo_path'] = null;
            }
            Salon::updateFromOwner((int) $salon['id'], $data);
            flash('saved', 'Fiche mise à jour.');
        } catch (Throwable $e) {
            flash('error', user_error_message($e));
        }
        redirect('/espace/salon');
    }

    /** @return array<string, mixed>|null */
    private function safePendingClaim(int $salonId, int $userId): ?array
    {
        try {
            return SalonClaim::pendingFor($salonId, $userId);
        } catch (Throwable) {
            return null;
        }
    }
}
