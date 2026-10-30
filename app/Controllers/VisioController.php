<?php

declare(strict_types=1);

namespace Adl\Controllers;

use Adl\Core\Auth;
use Adl\Core\Request;
use Adl\Core\View;
use Adl\Models\VisioProposal;
use Throwable;

final class VisioController
{
    public function propose(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        $conversationId = (int) $id;
        $back = '/espace/messages/' . $conversationId;
        try {
            if (rate_limited('visio-propose', (string) $user['id'], 20, 3600)) {
                throw new \RuntimeException('Trop de propositions de visio. Réessayez plus tard.');
            }
            VisioProposal::propose($conversationId, (int) $user['id'], [
                $request->string('slot1'),
                $request->string('slot2'),
                $request->string('slot3'),
            ]);
            flash('saved', 'Proposition envoyée. Votre client reçoit un e-mail pour choisir un créneau.');
        } catch (Throwable $e) {
            flash('error', user_error_message($e));
            $_SESSION['_visio_old'] = [
                'slot1' => $request->string('slot1'),
                'slot2' => $request->string('slot2'),
                'slot3' => $request->string('slot3'),
            ];
        }
        redirect($back);
    }

    public function respondInThread(Request $request, string $id): void
    {
        $user = Auth::requireUser();
        $conversationId = (int) $id;
        try {
            if (rate_limited('visio-reply-user', (string) $user['id'], 20, 3600)) {
                throw new \RuntimeException('Trop de tentatives. Réessayez plus tard.');
            }
            $action = $request->string('action');
            VisioProposal::respondInThread(
                $conversationId,
                (int) $user['id'],
                $action,
                $request->int('slot'),
                $request->string('reason')
            );
            flash('saved', self::savedMessage($action));
        } catch (Throwable $e) {
            flash('error', user_error_message($e));
        }
        redirect('/espace/messages/' . $conversationId);
    }

    public function show(Request $request, string $token): void
    {
        $proposal = VisioProposal::findByToken($token);
        if (!$proposal) {
            not_found('Cette proposition de visio est introuvable.');
        }
        $this->page($request, $token, $proposal);
    }

    public function respond(Request $request, string $token): void
    {
        $proposal = VisioProposal::findByToken($token);
        if (!$proposal) {
            not_found('Cette proposition de visio est introuvable.');
        }
        $me = Auth::id();
        if ($me !== null && $me === (int) ($proposal['provider_id'] ?? 0)) {
            flash('error', 'Ce lien est destiné à votre client.');
            redirect(VisioProposal::PAGE_PREFIX . $token);
        }
        $action = $request->string('action');
        try {
            if (rate_limited('visio-reply', $token, 20, 3600)) {
                throw new \RuntimeException('Trop de tentatives. Réessayez plus tard.');
            }
            VisioProposal::respondByToken($token, $action, $request->int('slot'), $request->string('reason'));
            flash('saved', self::savedMessage($action));
        } catch (Throwable $e) {
            flash('error', user_error_message($e));
        }
        $back = VisioProposal::PAGE_PREFIX . $token;
        $slot = $request->int('slot');
        if ($action === 'accept' && $slot) {
            $back .= '?creneau=' . $slot;
        } elseif ($action === 'none') {
            $back .= '?intention=aucun';
        } elseif ($action === 'refuse') {
            $back .= '?intention=refuser';
        }
        redirect($back);
    }

    /** @param array<string, mixed> $proposal */
    private function page(Request $request, string $token, array $proposal): void
    {
        $me = Auth::id();
        $locked = $me !== null && $me === (int) ($proposal['provider_id'] ?? 0);
        $status = (string) ($proposal['status'] ?? '');
        $title = match ($status) {
            'accepted' => 'Visio confirmée',
            'none' => 'Créneaux de visio',
            'refused' => 'Visio refusée',
            default => 'Choisir un créneau de visio',
        };
        View::page('visio', [
            'title' => $title,
            'description' => 'Répondre à une proposition de visio.',
            'visio' => VisioProposal::publicPanel($proposal, $token, $locked, [
                'selected' => (int) ($request->int('creneau') ?? 0),
                'intention' => $request->string('intention'),
            ]),
            'saved' => flash('saved'),
            'error' => flash('error'),
        ]);
    }

    private static function savedMessage(string $action): string
    {
        return match ($action) {
            'accept' => 'Créneau retenu. Le lien de visio est prêt.',
            'none' => 'Le prestataire est informé et peut proposer d’autres créneaux.',
            'refuse' => 'Refus envoyé au prestataire.',
            default => 'Réponse enregistrée.',
        };
    }
}
