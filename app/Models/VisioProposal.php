<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Core\Mailer;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final class VisioProposal
{
    public const PAGE_PREFIX = '/visio/acteur-du-livre/';
    private const MEET_BASE = 'https://kmeet.infomaniak.com/acteur-du-livre-';
    private const LEAD_SECONDS = 1800;
    private const GAP_SECONDS = 900;
    private const HORIZON_SECONDS = 120 * 86400;
    private const JOIN_GRACE_SECONDS = 7200;

    /**
     * @param array<string, mixed> $thread
     * @param array<string, string> $old
     * @return array<string, mixed>
     */
    public static function panel(array $thread, int $userId, array $old = []): array
    {
        $conversationId = (int) ($thread['id'] ?? 0);
        $empty = self::emptyPanel($conversationId);
        if ($conversationId < 1 || $userId < 1) {
            return $empty;
        }

        $provider = self::isProvider($thread, $userId);
        $client = self::isClient($thread, $userId);
        if (!$provider && !$client) {
            return $empty;
        }

        [$min, $max] = self::inputWindow();
        $latest = self::latest($conversationId);
        $latestStatus = is_array($latest) ? (string) ($latest['status'] ?? '') : '';
        $canPropose = $provider && self::canOpenProposal($conversationId);
        $old = self::oldSlots($old);

        $panel = $empty;
        $panel['revision'] = self::revision($conversationId);
        $panel['canPropose'] = $canPropose;
        $panel['openForm'] = $canPropose && (
            $old['slot1'] !== '' || $old['slot2'] !== '' || $old['slot3'] !== '' || $latestStatus === 'none'
        );
        $panel['proposeUrl'] = '/espace/messages/' . $conversationId . '/visio';
        $panel['respondUrl'] = '/espace/messages/' . $conversationId . '/visio/reponse';
        $panel['min'] = $min;
        $panel['max'] = $max;
        $panel['old'] = $old;
        $panel['providerName'] = $provider
            ? User::displayName(User::find($userId) ?? [])
            : (string) ($thread['other']['name'] ?? '');
        $panel['clientName'] = $client
            ? User::displayName(User::find($userId) ?? [])
            : (string) ($thread['other']['name'] ?? '');
        $panel['withName'] = $provider ? $panel['clientName'] : $panel['providerName'];

        if ($latest === null) {
            $panel['show'] = $canPropose;
            $panel['mode'] = $canPropose ? 'propose' : 'idle';
            return $panel;
        }

        $view = self::present($latest);
        $panel['slots'] = $view['slots'];
        $panel['chosenLabel'] = $view['chosen_label'];
        $panel['meetUrl'] = $view['meet_url'];
        $panel['reason'] = $view['reason'];
        $status = (string) $view['status'];

        if ($status === 'pending') {
            $panel['show'] = true;
            $panel['mode'] = $client ? 'pending-client' : 'pending-provider';
            $panel['canRespond'] = $client;
            return $panel;
        }
        if ($status === 'accepted') {
            $panel['show'] = true;
            $panel['mode'] = 'accepted';
            return $panel;
        }
        if ($status === 'none') {
            $panel['show'] = true;
            $panel['mode'] = $provider ? 'none-provider' : 'none-client';
            return $panel;
        }
        if ($status === 'refused') {
            $panel['show'] = true;
            $panel['mode'] = $provider ? 'refused-provider' : 'refused-client';
            return $panel;
        }

        $panel['show'] = $canPropose;
        $panel['mode'] = $canPropose ? 'propose' : 'idle';
        return $panel;
    }

    /**
     * @param array<string, mixed> $proposal
     * @param array{selected?: int, intention?: string} $query
     * @return array<string, mixed>
     */
    public static function publicPanel(array $proposal, string $token, bool $providerLocked, array $query = []): array
    {
        $conversationId = (int) ($proposal['conversation_id'] ?? 0);
        $panel = self::emptyPanel($conversationId);
        $view = self::present($proposal);
        $status = (string) $view['status'];
        $panel['show'] = true;
        $panel['public'] = true;
        $panel['revision'] = self::revision($conversationId);
        $panel['respondUrl'] = self::PAGE_PREFIX . $token;
        $panel['slots'] = $view['slots'];
        $panel['chosenLabel'] = $view['chosen_label'];
        $panel['meetUrl'] = $view['meet_url'];
        $panel['reason'] = $view['reason'];
        $panel['providerName'] = (string) ($proposal['provider_name'] ?? '');
        $panel['clientName'] = (string) ($proposal['client_name'] ?? '');
        $panel['withName'] = $panel['providerName'];
        $panel['selected'] = (int) ($query['selected'] ?? 0);
        $panel['intention'] = (string) ($query['intention'] ?? '');
        $panel['hint'] = self::newerHint($proposal);

        if ($providerLocked && $status === 'pending') {
            $panel['mode'] = 'provider-locked';
            return $panel;
        }
        if ($status === 'pending') {
            $panel['mode'] = 'pending-client';
            $panel['canRespond'] = true;
            return $panel;
        }
        if ($status === 'accepted') {
            $panel['mode'] = 'accepted';
            return $panel;
        }
        if ($status === 'none') {
            $panel['mode'] = 'none-client';
            return $panel;
        }
        if ($status === 'refused') {
            $panel['mode'] = 'refused-client';
            return $panel;
        }
        $panel['mode'] = 'idle';
        return $panel;
    }

    public static function revision(int $conversationId): string
    {
        if ($conversationId < 1) {
            return '0';
        }
        $row = Database::fetch(
            'SELECT id, status, responded_at FROM visio_proposals WHERE conversation_id = ? ORDER BY id DESC LIMIT 1',
            [$conversationId]
        );
        if (!$row) {
            return '0';
        }
        return (int) $row['id'] . ':' . (string) $row['status'] . ':' . (string) ($row['responded_at'] ?? '');
    }

    /**
     * @param list<string> $rawSlots
     */
    public static function propose(int $conversationId, int $userId, array $rawSlots): void
    {
        $thread = Conversation::findForUser($conversationId, $userId);
        if (!$thread) {
            throw new RuntimeException('Cette conversation est introuvable.');
        }
        if (!self::isProvider($thread, $userId)) {
            throw new RuntimeException('Seul le prestataire peut proposer une visio à son client.');
        }
        $clientId = (int) ($thread['other']['id'] ?? 0);
        $client = User::find($clientId);
        if (!$client || ($client['status'] ?? '') !== 'active' || !empty($client['deleted_at'])) {
            throw new RuntimeException('Ce correspondant n’est plus joignable.');
        }

        $slots = self::parseSlots($rawSlots);
        $token = bin2hex(random_bytes(24));
        $labels = array_map(static fn (DateTimeImmutable $dt): string => self::label($dt), $slots);
        $lien = url(self::PAGE_PREFIX . $token);
        $providerName = User::displayName(User::find($userId) ?? []);
        $html = self::emailSlots($lien, $labels);

        Database::transaction(static function () use ($conversationId, $userId, $clientId, $slots, $token, $labels, $html, $lien, $providerName, $client): void {
            $locked = Database::fetch('SELECT id FROM conversations WHERE id = ? FOR UPDATE', [$conversationId]);
            if (!$locked) {
                throw new RuntimeException('Cette conversation est introuvable.');
            }
            if (!self::canOpenProposal($conversationId)) {
                throw new RuntimeException('Une visio est déjà proposée ou encore prévue. Attendez la réponse, ou la fin du rendez-vous.');
            }

            Database::query(
                'INSERT INTO visio_proposals
                    (conversation_id, provider_id, client_id, token_hash, status, slot1_at, slot2_at, slot3_at, created_at)
                 VALUES (?, ?, ?, ?, "pending", ?, ?, ?, NOW())',
                [
                    $conversationId,
                    $userId,
                    $clientId,
                    hash('sha256', $token),
                    $slots[0]->format('Y-m-d H:i:s'),
                    $slots[1]->format('Y-m-d H:i:s'),
                    $slots[2]->format('Y-m-d H:i:s'),
                ]
            );

            $body = 'Je vous propose une visio. Trois créneaux : ' . implode(' ; ', $labels) . '.';
            Conversation::send($conversationId, $userId, $body, null, null, false);
            Notification::create(
                $clientId,
                'Proposition de visio',
                $providerName . ' vous propose trois créneaux.',
                '/espace/messages/' . $conversationId,
                'visio',
                'conversation',
                $conversationId
            );

            Database::afterCommit(static function () use ($client, $providerName, $html, $lien): void {
                self::mail('visio-proposition', $client, [
                    'prestataire' => $providerName !== '' ? $providerName : 'Votre prestataire',
                    'creneaux_html' => $html,
                    'lien' => $lien,
                ]);
            });
        });
    }

    public static function respondInThread(int $conversationId, int $userId, string $action, ?int $slot, string $reason): void
    {
        $thread = Conversation::findForUser($conversationId, $userId);
        if (!$thread) {
            throw new RuntimeException('Cette conversation est introuvable.');
        }
        $pending = Database::fetch(
            'SELECT * FROM visio_proposals WHERE conversation_id = ? AND status = "pending" ORDER BY id DESC LIMIT 1',
            [$conversationId]
        );
        if (!$pending) {
            throw new RuntimeException('Aucune proposition de visio en attente.');
        }
        self::respond((int) $pending['id'], $action, $slot, $reason, $userId);
    }

    public static function respondByToken(string $token, string $action, ?int $slot, string $reason): void
    {
        $row = self::rowByToken($token);
        if (!$row) {
            throw new RuntimeException('Cette proposition de visio est introuvable.');
        }
        self::respond((int) $row['id'], $action, $slot, $reason, (int) $row['client_id']);
    }

    /** @return array<string, mixed>|null */
    public static function findByToken(string $token): ?array
    {
        $row = self::rowByToken($token);
        if (!$row) {
            return null;
        }
        $row['provider_name'] = User::displayName(User::find((int) $row['provider_id']) ?? []);
        $row['client_name'] = User::displayName(User::find((int) $row['client_id']) ?? []);
        return self::present($row);
    }

    public static function deleteForConversation(int $conversationId): void
    {
        if ($conversationId < 1) {
            return;
        }
        Database::query('DELETE FROM visio_proposals WHERE conversation_id = ?', [$conversationId]);
        Database::query(
            'DELETE FROM notifications WHERE kind = "visio" AND subject_type = "conversation" AND subject_id = ?',
            [$conversationId]
        );
    }

    /**
     * Le prestataire de l’échange propose la visio. La commande, la prestation
     * ou la mission l’emportent sur qui a ouvert le fil : un vendeur qui a
     * écrit le premier reste le prestataire, même si le client propose aussi
     * des services.
     *
     * @param array<string, mixed> $thread
     */
    public static function isProvider(array $thread, int $userId): bool
    {
        if ($userId < 1) {
            return false;
        }
        $otherId = (int) ($thread['other']['id'] ?? 0);
        if ($otherId < 1 || $otherId === $userId) {
            return false;
        }

        return self::providerUserId($thread, $userId, $otherId) === $userId;
    }

    /**
     * @param array<string, mixed> $thread
     */
    private static function providerUserId(array $thread, int $userId, int $otherId): int
    {
        $linked = self::linkedProviderId($thread, $userId, $otherId);
        if ($linked > 0) {
            return $linked;
        }

        $meOffers = User::offersServices(User::find($userId));
        $otherOffers = User::offersServices(User::find($otherId));
        if ($meOffers && !$otherOffers) {
            return $userId;
        }
        if ($otherOffers && !$meOffers) {
            return $otherId;
        }
        if (!$meOffers) {
            return 0;
        }

        $initiator = self::initiatorId($thread);
        if ($initiator === $userId) {
            return $otherId;
        }
        if ($initiator === $otherId) {
            return $userId;
        }

        return 0;
    }

    /**
     * @param array<string, mixed> $thread
     */
    private static function linkedProviderId(array $thread, int $userId, int $otherId): int
    {
        $orderId = (int) ($thread['order_id'] ?? 0);
        if ($orderId > 0) {
            $order = Database::fetch('SELECT buyer_id, seller_id FROM orders WHERE id = ?', [$orderId]);
            $seller = (int) ($order['seller_id'] ?? 0);
            $buyer = (int) ($order['buyer_id'] ?? 0);
            if ($seller > 0 && ($seller === $userId || $seller === $otherId) && ($buyer === $userId || $buyer === $otherId)) {
                return $seller;
            }
        }

        $serviceId = (int) ($thread['service_id'] ?? 0);
        if ($serviceId > 0) {
            $service = Database::fetch('SELECT user_id FROM services WHERE id = ?', [$serviceId]);
            $owner = (int) ($service['user_id'] ?? 0);
            if ($owner === $userId || $owner === $otherId) {
                return $owner;
            }
        }

        $missionId = (int) ($thread['mission_id'] ?? 0);
        if ($missionId > 0) {
            $mission = Database::fetch('SELECT user_id FROM missions WHERE id = ?', [$missionId]);
            $owner = (int) ($mission['user_id'] ?? 0);
            if ($owner === $userId) {
                return $otherId;
            }
            if ($owner === $otherId) {
                return $userId;
            }
        }

        return 0;
    }

    /** @param array<string, mixed> $thread */
    private static function initiatorId(array $thread): int
    {
        $initiator = (int) ($thread['started_by'] ?? 0);
        if ($initiator > 0) {
            return $initiator;
        }
        $first = Database::fetch(
            'SELECT user_id FROM messages WHERE conversation_id = ? ORDER BY id ASC LIMIT 1',
            [(int) ($thread['id'] ?? 0)]
        );

        return (int) ($first['user_id'] ?? 0);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function present(array $row): array
    {
        $now = new DateTimeImmutable('now', self::tz());
        $slots = [];
        foreach ([1, 2, 3] as $n) {
            $dt = new DateTimeImmutable((string) $row['slot' . $n . '_at'], self::tz());
            $slots[] = [
                'n' => $n,
                'label' => self::label($dt),
                'past' => $dt <= $now,
            ];
        }
        $chosen = (int) ($row['chosen_slot'] ?? 0);
        $row['slots'] = $slots;
        $row['chosen_label'] = ($chosen >= 1 && $chosen <= 3) ? $slots[$chosen - 1]['label'] : '';
        $row['meet_url'] = trim((string) ($row['meet_url'] ?? ''));
        $row['reason'] = trim((string) ($row['refusal_reason'] ?? ''));
        $row['status'] = (string) ($row['status'] ?? '');
        return $row;
    }

    private static function respond(int $proposalId, string $action, ?int $slot, string $reason, int $actorId): void
    {
        $action = trim($action);
        if (!in_array($action, ['accept', 'none', 'refuse'], true)) {
            throw new RuntimeException('Choisissez une réponse.');
        }
        $reason = trim($reason);
        if ($action === 'refuse') {
            $length = mb_strlen($reason);
            if ($length < 10) {
                throw new RuntimeException('Expliquez le motif du refus (10 caractères minimum).');
            }
            if ($length > 1000) {
                throw new RuntimeException('Le motif est trop long (1 000 caractères maximum).');
            }
        }

        $payload = Database::transaction(static function () use ($proposalId, $action, $slot, $reason, $actorId): array {
            $row = Database::fetch('SELECT * FROM visio_proposals WHERE id = ? FOR UPDATE', [$proposalId]);
            if (!$row) {
                throw new RuntimeException('Cette proposition de visio est introuvable.');
            }
            if ((int) $row['client_id'] !== $actorId) {
                throw new RuntimeException('Seul le client peut répondre à cette proposition.');
            }
            if ((string) $row['status'] !== 'pending') {
                throw new RuntimeException('Cette proposition a déjà reçu une réponse.');
            }

            $presented = self::present($row);
            $conversationId = (int) $row['conversation_id'];
            $providerId = (int) $row['provider_id'];
            $client = User::find($actorId);
            $provider = User::find($providerId);
            $clientName = User::displayName($client ?? []);
            $providerName = User::displayName($provider ?? []);
            $threadPath = '/espace/messages/' . $conversationId;
            $lien = url($threadPath);

            if ($action === 'accept') {
                $n = (int) $slot;
                if ($n < 1 || $n > 3) {
                    throw new RuntimeException('Choisissez l’un des trois créneaux.');
                }
                if (!empty($presented['slots'][$n - 1]['past'])) {
                    throw new RuntimeException('Ce créneau est déjà passé. Choisissez-en un autre, ou indiquez qu’aucun ne convient.');
                }
                $meet = self::allocateMeetUrl();
                $label = (string) $presented['slots'][$n - 1]['label'];
                Database::query(
                    'UPDATE visio_proposals
                     SET status = "accepted", chosen_slot = ?, meet_url = ?, responded_at = NOW()
                     WHERE id = ?',
                    [$n, $meet, $proposalId]
                );
                Conversation::send($conversationId, $actorId, 'Je retiens le créneau du ' . $label . ".\n" . $meet, null, null, false);
                Notification::create(
                    $providerId,
                    'Visio confirmée',
                    ($clientName !== '' ? $clientName : 'Le client') . ' a retenu le ' . $label . '.',
                    $threadPath,
                    'visio',
                    'conversation',
                    $conversationId
                );
                return [
                    'kind' => 'accept',
                    'client' => $client,
                    'provider' => $provider,
                    'clientName' => $clientName,
                    'providerName' => $providerName,
                    'label' => $label,
                    'meet' => $meet,
                    'lien' => $lien,
                    'reason' => '',
                ];
            }

            if ($action === 'none') {
                Database::query(
                    'UPDATE visio_proposals SET status = "none", responded_at = NOW() WHERE id = ?',
                    [$proposalId]
                );
                Conversation::send(
                    $conversationId,
                    $actorId,
                    'Aucun des créneaux proposés ne me convient. Vous pouvez m’en proposer d’autres.',
                    null,
                    null,
                    false
                );
                Notification::create(
                    $providerId,
                    'Aucun créneau ne convient',
                    ($clientName !== '' ? $clientName : 'Le client') . ' indique qu’aucun créneau ne convient.',
                    $threadPath,
                    'visio',
                    'conversation',
                    $conversationId
                );
                return [
                    'kind' => 'none',
                    'client' => $client,
                    'provider' => $provider,
                    'clientName' => $clientName,
                    'providerName' => $providerName,
                    'label' => '',
                    'meet' => '',
                    'lien' => $lien,
                    'reason' => '',
                ];
            }

            Database::query(
                'UPDATE visio_proposals SET status = "refused", refusal_reason = ?, responded_at = NOW() WHERE id = ?',
                [$reason, $proposalId]
            );
            Conversation::send($conversationId, $actorId, "Je ne souhaite pas de visio.\n\n" . $reason, null, null, false);
            $excerpt = mb_strlen($reason) > 160 ? mb_substr($reason, 0, 157) . '…' : $reason;
            Notification::create(
                $providerId,
                'Visio refusée',
                $excerpt,
                $threadPath,
                'visio',
                'conversation',
                $conversationId
            );
            return [
                'kind' => 'refuse',
                'client' => $client,
                'provider' => $provider,
                'clientName' => $clientName,
                'providerName' => $providerName,
                'label' => '',
                'meet' => '',
                'lien' => $lien,
                'reason' => $reason,
            ];
        });

        Database::afterCommit(static function () use ($payload): void {
            $clientName = $payload['clientName'] !== '' ? $payload['clientName'] : 'Le client';
            $providerName = $payload['providerName'] !== '' ? $payload['providerName'] : 'Votre prestataire';
            if ($payload['kind'] === 'accept') {
                self::mail('visio-acceptee', $payload['provider'], [
                    'client' => $clientName,
                    'quand' => $payload['label'],
                    'lien_visio' => $payload['meet'],
                    'lien' => $payload['lien'],
                ]);
                self::mail('visio-lien', $payload['client'], [
                    'prestataire' => $providerName,
                    'quand' => $payload['label'],
                    'lien_visio' => $payload['meet'],
                    'lien' => $payload['lien'],
                ]);
                return;
            }
            if ($payload['kind'] === 'none') {
                self::mail('visio-aucun-creneau', $payload['provider'], [
                    'client' => $clientName,
                    'lien' => $payload['lien'],
                ]);
                return;
            }
            self::mail('visio-refusee', $payload['provider'], [
                'client' => $clientName,
                'message' => $payload['reason'],
                'lien' => $payload['lien'],
            ]);
        });
    }

    /** @param array<string, mixed>|null $user */
    private static function mail(string $slug, ?array $user, array $vars): void
    {
        if ($user === null) {
            return;
        }
        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        if (($user['status'] ?? '') !== 'active' || !empty($user['deleted_at'])) {
            return;
        }
        if (!isset($vars['prenom']) || $vars['prenom'] === '') {
            $vars['prenom'] = (string) ($user['first_name'] ?? '');
        }
        try {
            Mailer::sendTemplate($slug, $email, $vars);
        } catch (\Throwable) {
        }
    }

    /** @param list<string> $labels */
    private static function emailSlots(string $pageUrl, array $labels): string
    {
        $html = '';
        foreach ($labels as $i => $label) {
            $href = e($pageUrl . '?creneau=' . ($i + 1));
            $html .= '<p style="margin:0 0 12px;"><a href="' . $href . '" style="color:#022746;font-weight:600;">' . e($label) . '</a></p>';
        }
        $html .= '<p style="margin:18px 0 8px;"><a href="' . e($pageUrl . '?intention=aucun') . '" style="color:#022746;">Aucun créneau ne me convient</a></p>';
        $html .= '<p style="margin:0;"><a href="' . e($pageUrl . '?intention=refuser') . '" style="color:#022746;">Refuser la visio</a></p>';
        return $html;
    }

    /** @param list<string> $rawSlots */
    private static function parseSlots(array $rawSlots): array
    {
        if (count($rawSlots) !== 3) {
            throw new RuntimeException('Indiquez trois dates et heures à venir.');
        }
        $tz = self::tz();
        $slots = [];
        foreach ($rawSlots as $raw) {
            $raw = trim($raw);
            if (!preg_match('/\A(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2})(?::\d{2})?\z/', $raw, $m)) {
                throw new RuntimeException('Indiquez trois dates et heures à venir.');
            }
            $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $m[1] . ' ' . $m[2], $tz);
            if (!$dt || $dt->format('Y-m-d H:i') !== $m[1] . ' ' . $m[2]) {
                throw new RuntimeException('Indiquez trois dates et heures à venir.');
            }
            $slots[] = $dt;
        }
        $now = time();
        foreach ($slots as $dt) {
            if ($dt->getTimestamp() < $now + self::LEAD_SECONDS) {
                throw new RuntimeException('Chaque créneau doit commencer au moins 30 minutes après maintenant (heure de Paris).');
            }
            if ($dt->getTimestamp() > $now + self::HORIZON_SECONDS) {
                throw new RuntimeException('Un créneau ne peut pas être à plus de quatre mois.');
            }
        }
        for ($i = 0; $i < 3; $i++) {
            for ($j = $i + 1; $j < 3; $j++) {
                if (abs($slots[$i]->getTimestamp() - $slots[$j]->getTimestamp()) < self::GAP_SECONDS) {
                    throw new RuntimeException('Espacez les trois créneaux d’au moins 15 minutes.');
                }
            }
        }
        usort($slots, static fn (DateTimeImmutable $a, DateTimeImmutable $b): int => $a <=> $b);
        return $slots;
    }

    private static function allocateMeetUrl(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
        $max = strlen($alphabet) - 1;
        for ($try = 0; $try < 6; $try++) {
            $suffix = '';
            for ($i = 0; $i < 10; $i++) {
                $suffix .= $alphabet[random_int(0, $max)];
            }
            $url = self::MEET_BASE . $suffix;
            $exists = Database::fetch('SELECT id FROM visio_proposals WHERE meet_url = ?', [$url]);
            if (!$exists) {
                return $url;
            }
        }
        throw new RuntimeException('Impossible de créer le lien de visio. Réessayez.');
    }

    private static function canOpenProposal(int $conversationId): bool
    {
        $pending = Database::fetch(
            'SELECT id FROM visio_proposals WHERE conversation_id = ? AND status = "pending" LIMIT 1',
            [$conversationId]
        );
        if ($pending) {
            return false;
        }
        $accepted = Database::fetch(
            'SELECT * FROM visio_proposals WHERE conversation_id = ? AND status = "accepted" ORDER BY id DESC LIMIT 1',
            [$conversationId]
        );
        if (!$accepted) {
            return true;
        }
        $n = (int) ($accepted['chosen_slot'] ?? 0);
        if ($n < 1 || $n > 3) {
            return false;
        }
        $dt = new DateTimeImmutable((string) $accepted['slot' . $n . '_at'], self::tz());
        return $dt->getTimestamp() + self::JOIN_GRACE_SECONDS <= time();
    }

    /** @param array<string, mixed> $proposal */
    private static function newerHint(array $proposal): string
    {
        if ((string) ($proposal['status'] ?? '') === 'pending') {
            return '';
        }
        $newer = Database::fetch(
            'SELECT id FROM visio_proposals WHERE conversation_id = ? AND id > ? AND status = "pending" LIMIT 1',
            [(int) ($proposal['conversation_id'] ?? 0), (int) ($proposal['id'] ?? 0)]
        );
        if (!$newer) {
            return '';
        }
        return 'Une nouvelle proposition vous a été envoyée. Ouvrez le dernier e-mail, ou la messagerie.';
    }

    /** @return array<string, mixed>|null */
    private static function latest(int $conversationId): ?array
    {
        return Database::fetch(
            'SELECT * FROM visio_proposals WHERE conversation_id = ? ORDER BY id DESC LIMIT 1',
            [$conversationId]
        );
    }

    /** @return array<string, mixed>|null */
    private static function rowByToken(string $token): ?array
    {
        $token = strtolower(trim($token));
        if (!preg_match('/\A[a-f0-9]{48}\z/', $token)) {
            return null;
        }
        return Database::fetch(
            'SELECT * FROM visio_proposals WHERE token_hash = ?',
            [hash('sha256', $token)]
        );
    }

    /**
     * @param array<string, mixed> $thread
     */
    private static function isClient(array $thread, int $userId): bool
    {
        if ($userId < 1 || self::isProvider($thread, $userId)) {
            return false;
        }
        $otherId = (int) ($thread['other']['id'] ?? 0);
        if ($otherId < 1) {
            return false;
        }
        $swapped = $thread;
        $swapped['other'] = ['id' => $userId];
        return self::isProvider($swapped, $otherId);
    }

    /** @param array<string, string> $old
     *  @return array{slot1: string, slot2: string, slot3: string}
     */
    private static function oldSlots(array $old): array
    {
        return [
            'slot1' => trim((string) ($old['slot1'] ?? '')),
            'slot2' => trim((string) ($old['slot2'] ?? '')),
            'slot3' => trim((string) ($old['slot3'] ?? '')),
        ];
    }

    /** @return array{0: string, 1: string} */
    private static function inputWindow(): array
    {
        $tz = self::tz();
        $min = (new DateTimeImmutable('now', $tz))->modify('+' . self::LEAD_SECONDS . ' seconds');
        $extra = (15 - ((int) $min->format('i') % 15)) % 15;
        if ($extra !== 0) {
            $min = $min->modify('+' . $extra . ' minutes');
        }
        $min = $min->setTime((int) $min->format('H'), (int) $min->format('i'), 0);
        $max = (new DateTimeImmutable('now', $tz))->modify('+120 days');
        return [$min->format('Y-m-d\TH:i'), $max->format('Y-m-d\TH:i')];
    }

    private static function label(DateTimeImmutable $dt): string
    {
        $days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
        $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        return $days[(int) $dt->format('w')]
            . ' ' . $dt->format('j') . ' ' . $months[(int) $dt->format('n') - 1]
            . ', ' . $dt->format('G') . ' h ' . $dt->format('i');
    }

    private static function tz(): DateTimeZone
    {
        return new DateTimeZone(date_default_timezone_get() ?: 'Europe/Paris');
    }

    /** @return array<string, mixed> */
    private static function emptyPanel(int $conversationId): array
    {
        return [
            'show' => false,
            'public' => false,
            'mode' => 'idle',
            'canPropose' => false,
            'canRespond' => false,
            'openForm' => false,
            'proposeUrl' => '',
            'respondUrl' => '',
            'min' => '',
            'max' => '',
            'old' => ['slot1' => '', 'slot2' => '', 'slot3' => ''],
            'slots' => [],
            'selected' => 0,
            'intention' => '',
            'chosenLabel' => '',
            'meetUrl' => '',
            'reason' => '',
            'providerName' => '',
            'clientName' => '',
            'withName' => '',
            'hint' => '',
            'revision' => $conversationId > 0 ? '0' : '0',
        ];
    }
}
