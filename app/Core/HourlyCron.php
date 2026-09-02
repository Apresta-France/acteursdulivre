<?php

declare(strict_types=1);

namespace Adl\Core;

use Adl\Models\Invoice;
use Adl\Models\Notification;
use Adl\Models\Profile;
use Adl\Models\ReminderSend;
use Adl\Models\Setting;
use Adl\Models\User;
use Throwable;

final class HourlyCron
{
    public const MIN_INTERVAL_SECONDS = 50 * 60;
    public const PROFILE_THRESHOLD = 80;

    private const ONBOARDING_WAIT_HOURS = 24;
    private const ONBOARDING_COOLDOWN_HOURS = 168;
    private const ONBOARDING_MAX = 4;

    private const REQUEST_WAIT_HOURS = 24;
    private const REQUEST_COOLDOWN_HOURS = 48;
    private const REQUEST_MAX = 3;

    private const PROJECT_WAIT_HOURS = 72;
    private const PROJECT_COOLDOWN_HOURS = 168;
    private const PROJECT_MAX = 4;

    /**
     * @return array{
     *     ok: bool,
     *     skipped: bool,
     *     reason?: string,
     *     retry_in?: int,
     *     ran_at?: string,
     *     stats?: array<string, int>,
     *     errors?: list<string>
     * }
     */
    public static function run(bool $force = false): array
    {
        $lockPath = ADL_ROOT . '/storage/cron-hourly.lock';
        $dir = dirname($lockPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $handle = fopen($lockPath, 'c+');
        if ($handle === false) {
            return ['ok' => false, 'skipped' => true, 'reason' => 'lock_unavailable'];
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return ['ok' => true, 'skipped' => true, 'reason' => 'already_running'];
        }

        try {
            if (!$force) {
                $tooSoon = self::secondsUntilNextRun();
                if ($tooSoon > 0) {
                    return ['ok' => true, 'skipped' => true, 'reason' => 'too_soon', 'retry_in' => $tooSoon];
                }
            }

            $stats = [
                'profile_incomplete' => 0,
                'missing_mission' => 0,
                'unanswered_application' => 0,
                'unanswered_message' => 0,
                'pending_project' => 0,
                'invoices_overdue' => 0,
                'delivery_validation' => 0,
                'stats_pruned_minute' => 0,
                'stats_pruned_uniques' => 0,
                'stats_pruned_live' => 0,
                'stats_pruned_daily' => 0,
            ];
            $errors = [];

            self::remindIncompleteProfiles($stats, $errors);
            self::remindMissingMissions($stats, $errors);
            self::remindUnansweredApplications($stats, $errors);
            self::remindUnansweredMessages($stats, $errors);
            self::remindPendingProjects($stats, $errors);
            self::remindSilentClients($stats, $errors);
            self::markOverdueInvoices($stats, $errors);
            try {
                $news = NewsletterCron::run(false);
                $stats['newsletter_sent'] = (int) (($news['stats']['sent'] ?? 0));
                $stats['newsletter_queued'] = (int) (($news['stats']['queued'] ?? 0));
                foreach ($news['errors'] ?? [] as $err) {
                    $errors[] = 'newsletter: ' . $err;
                }
            } catch (Throwable $e) {
                $errors[] = 'newsletter: ' . $e->getMessage();
            }

            try {
                $pruned = \Adl\Models\Analytics::prune();
                $stats['stats_pruned_minute'] = $pruned['pruned_minute'];
                $stats['stats_pruned_uniques'] = $pruned['pruned_uniques'];
                $stats['stats_pruned_live'] = $pruned['pruned_live'];
                $stats['stats_pruned_daily'] = $pruned['pruned_daily'];
            } catch (Throwable $e) {
                $errors[] = 'analytics: ' . $e->getMessage();
            }

            Setting::set('cron_hourly_last_run', date('c'));

            return [
                'ok' => $errors === [],
                'skipped' => false,
                'ran_at' => date('c'),
                'stats' => $stats,
                'errors' => $errors,
            ];
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private static function secondsUntilNextRun(): int
    {
        try {
            $last = Setting::get('cron_hourly_last_run');
        } catch (Throwable) {
            return 0;
        }
        if ($last === null || $last === '') {
            return 0;
        }
        $ts = strtotime($last);
        if ($ts === false) {
            return 0;
        }
        $elapsed = time() - $ts;
        return $elapsed >= self::MIN_INTERVAL_SECONDS ? 0 : self::MIN_INTERVAL_SECONDS - $elapsed;
    }

    /** @param array<string, int> $stats @param list<string> $errors */
    private static function remindIncompleteProfiles(array &$stats, array &$errors): void
    {
        $hours = self::ONBOARDING_WAIT_HOURS;
        $users = Database::fetchAll(
            "SELECT * FROM users
             WHERE offers_services = 1 AND status = 'active'
               AND created_at <= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)"
        );

        foreach ($users as $user) {
            $profile = Profile::findByUser((int) $user['id']);
            $completion = (int) ($profile['completion'] ?? 0);
            if ($completion >= self::PROFILE_THRESHOLD) {
                continue;
            }

            $missing = $profile ? Profile::missingLabels($profile) : ['presque tous les champs de la vitrine'];
            $sent = self::dispatch(
                $user,
                'profile_incomplete',
                'relance-profil',
                [
                    'prenom' => $user['first_name'],
                    'completion' => (string) $completion,
                    'manques' => self::joinFr($missing),
                    'lien' => url('/espace/vitrine'),
                ],
                'Votre vitrine n’est complétée qu’à ' . $completion . ' %',
                'Il manque encore ' . self::joinFr($missing) . '. Les vitrines précises reçoivent plus de demandes.',
                '/espace/vitrine',
                null,
                null,
                self::ONBOARDING_COOLDOWN_HOURS,
                self::ONBOARDING_MAX,
                $errors
            );
            if ($sent) {
                $stats['profile_incomplete']++;
            }
        }
    }

    /** @param array<string, int> $stats @param list<string> $errors */
    private static function remindMissingMissions(array &$stats, array &$errors): void
    {
        $hours = self::ONBOARDING_WAIT_HOURS;
        $users = Database::fetchAll(
            "SELECT u.* FROM users u
             WHERE u.seeks_services = 1 AND u.offers_services = 0 AND u.status = 'active'
               AND u.created_at <= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
               AND NOT EXISTS (
                    SELECT 1 FROM missions m
                    WHERE m.user_id = u.id AND m.status IN ('open', 'assigned', 'closed')
               )"
        );

        foreach ($users as $user) {
            $sent = self::dispatch(
                $user,
                'missing_mission',
                'relance-mission',
                [
                    'prenom' => $user['first_name'],
                    'lien' => url('/espace/publier'),
                ],
                'Publiez votre première mission',
                'Décrivez le besoin et le budget : les prestataires du métier choisi pourront y répondre.',
                '/espace/publier',
                null,
                null,
                self::ONBOARDING_COOLDOWN_HOURS,
                self::ONBOARDING_MAX,
                $errors
            );
            if ($sent) {
                $stats['missing_mission']++;
            }
        }
    }

    /** @param array<string, int> $stats @param list<string> $errors */
    private static function remindUnansweredApplications(array &$stats, array &$errors): void
    {
        $hours = self::REQUEST_WAIT_HOURS;
        try {
            $rows = Database::fetchAll(
                "SELECT a.id, a.created_at, a.price, a.status,
                        m.id AS mission_id, m.title AS mission_title, m.user_id AS owner_id,
                        u.first_name AS applicant_first, u.last_name AS applicant_last,
                        owner.email, owner.first_name, owner.last_name, owner.status AS owner_status
                 FROM applications a
                 JOIN missions m ON m.id = a.mission_id
                 JOIN users u ON u.id = a.user_id
                 JOIN users owner ON owner.id = m.user_id
                 WHERE a.status IN ('sent', 'pending', 'viewed')
                   AND m.status = 'open'
                   AND owner.status = 'active'
                   AND a.created_at <= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)"
            );
        } catch (Throwable $e) {
            $errors[] = 'candidatures : ' . $e->getMessage();
            return;
        }

        foreach ($rows as $row) {
            $owner = [
                'id' => (int) $row['owner_id'],
                'email' => $row['email'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
            ];
            $who = User::displayName([
                'first_name' => $row['applicant_first'],
                'last_name' => $row['applicant_last'],
            ]);
            $delay = time_ago($row['created_at'] ?? null) ?: 'il y a plus d’un jour';
            $price = isset($row['price']) && $row['price'] !== null && $row['price'] !== ''
                ? ' pour ' . format_euros((int) $row['price'])
                : '';
            $detail = $who . ' a proposé ses services' . $price . ' sur « ' . $row['mission_title'] . ' » (' . $delay . ').';

            $sent = self::dispatch(
                $owner,
                'unanswered_application',
                'relance-demande',
                [
                    'prenom' => $owner['first_name'],
                    'detail' => $detail,
                    'lien' => url('/espace/missions'),
                ],
                'Une candidature attend votre réponse',
                $detail,
                '/espace/missions',
                'application',
                (int) $row['id'],
                self::REQUEST_COOLDOWN_HOURS,
                self::REQUEST_MAX,
                $errors
            );
            if ($sent) {
                $stats['unanswered_application']++;
            }
        }
    }

    /** @param array<string, int> $stats @param list<string> $errors */
    private static function remindUnansweredMessages(array &$stats, array &$errors): void
    {
        $hours = self::REQUEST_WAIT_HOURS;
        try {
            $conversations = Database::fetchAll(
                "SELECT c.id, c.subject,
                        last.user_id AS last_user_id, last.created_at AS last_at
                 FROM conversations c
                 JOIN messages last ON last.id = (
                    SELECT m.id FROM messages m
                    WHERE m.conversation_id = c.id
                    ORDER BY m.id DESC LIMIT 1
                 )
                 WHERE last.created_at <= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)"
            );
        } catch (Throwable $e) {
            $errors[] = 'messages : ' . $e->getMessage();
            return;
        }

        foreach ($conversations as $conversation) {
            $waiting = Database::fetchAll(
                'SELECT u.id, u.email, u.first_name, u.last_name, u.status
                 FROM conversation_participants p
                 JOIN users u ON u.id = p.user_id
                 WHERE p.conversation_id = ? AND p.user_id != ? AND u.status = \'active\'',
                [(int) $conversation['id'], (int) $conversation['last_user_id']]
            );
            $subject = trim((string) ($conversation['subject'] ?? '')) ?: 'votre conversation';
            $delay = time_ago($conversation['last_at'] ?? null) ?: 'il y a plus d’un jour';

            foreach ($waiting as $user) {
                $sent = self::dispatch(
                    $user,
                    'unanswered_message',
                    'relance-message',
                    [
                        'prenom' => $user['first_name'],
                        'sujet' => $subject,
                        'delai' => $delay,
                        'lien' => url('/espace/messages/' . (int) $conversation['id']),
                    ],
                    'Un message attend votre réponse',
                    'Concernant « ' . $subject . ' », envoyé ' . $delay . '.',
                    '/espace/messages/' . (int) $conversation['id'],
                    'conversation',
                    (int) $conversation['id'],
                    self::REQUEST_COOLDOWN_HOURS,
                    self::REQUEST_MAX,
                    $errors
                );
                if ($sent) {
                    $stats['unanswered_message']++;
                }
            }
        }
    }

    /** @param array<string, int> $stats @param list<string> $errors */
    private static function remindPendingProjects(array &$stats, array &$errors): void
    {
        $hours = self::PROJECT_WAIT_HOURS;

        try {
            $orders = Database::fetchAll(
                "SELECT o.*,
                        buyer.email AS buyer_email, buyer.first_name AS buyer_first, buyer.last_name AS buyer_last, buyer.status AS buyer_status,
                        seller.email AS seller_email, seller.first_name AS seller_first, seller.last_name AS seller_last, seller.status AS seller_status
                 FROM orders o
                 JOIN users buyer ON buyer.id = o.buyer_id
                 JOIN users seller ON seller.id = o.seller_id
                 WHERE o.status IN ('pending', 'in_progress')
                   AND o.created_at <= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)"
            );
        } catch (Throwable $e) {
            $errors[] = 'projets : ' . $e->getMessage();
            $orders = [];
        }

        foreach ($orders as $order) {
            $title = 'Commande ' . (string) ($order['number'] ?? ('#' . $order['id']));
            $status = (string) ($order['status'] ?? 'pending');
            $detailBuyer = $title . ' est toujours en cours. Un point rapide évite les malentendus.';
            $detailSeller = $title . ' est toujours marquée « ' . $status . ' ». Tenez le porteur informé de l’avancement.';

            if (($order['buyer_status'] ?? '') === 'active') {
                $buyer = [
                    'id' => (int) $order['buyer_id'],
                    'email' => $order['buyer_email'],
                    'first_name' => $order['buyer_first'],
                    'last_name' => $order['buyer_last'],
                ];
                $link = '/espace/suivi/' . (int) $order['id'];
                if (self::dispatch(
                    $buyer,
                    'pending_project',
                    'relance-projet',
                    [
                        'prenom' => $buyer['first_name'],
                        'titre' => $title,
                        'detail' => $detailBuyer,
                        'lien' => url($link),
                    ],
                    'Projet en cours',
                    $detailBuyer,
                    $link,
                    'order',
                    (int) $order['id'],
                    self::PROJECT_COOLDOWN_HOURS,
                    self::PROJECT_MAX,
                    $errors
                )) {
                    $stats['pending_project']++;
                }
            }

            if (($order['seller_status'] ?? '') === 'active' && $status !== 'delivered') {
                $seller = [
                    'id' => (int) $order['seller_id'],
                    'email' => $order['seller_email'],
                    'first_name' => $order['seller_first'],
                    'last_name' => $order['seller_last'],
                ];
                if (self::dispatch(
                    $seller,
                    'pending_project',
                    'relance-projet',
                    [
                        'prenom' => $seller['first_name'],
                        'titre' => $title,
                        'detail' => $detailSeller,
                        'lien' => url('/espace/suivi/' . (int) $order['id']),
                    ],
                    'Projet en cours',
                    $detailSeller,
                    '/espace/suivi/' . (int) $order['id'],
                    'order',
                    (int) $order['id'],
                    self::PROJECT_COOLDOWN_HOURS,
                    self::PROJECT_MAX,
                    $errors
                )) {
                    $stats['pending_project']++;
                }
            }
        }

        try {
            $missions = Database::fetchAll(
                "SELECT m.id, m.title, m.user_id, u.email, u.first_name, u.last_name
                 FROM missions m
                 JOIN users u ON u.id = m.user_id
                 WHERE m.status = 'assigned'
                   AND u.status = 'active'
                   AND EXISTS (
                       SELECT 1 FROM orders o
                       WHERE o.mission_id = m.id
                         AND o.status IN ('pending', 'in_progress')
                         AND COALESCE(o.accepted_at, o.created_at) <= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
                   )"
            );
        } catch (Throwable $e) {
            $errors[] = 'missions : ' . $e->getMessage();
            return;
        }

        foreach ($missions as $mission) {
            $title = (string) $mission['title'];
            $detail = 'La mission « ' . $title . ' » est attribuée et toujours en cours. Vérifiez les échanges et les prochaines étapes.';
            $sent = self::dispatch(
                [
                    'id' => (int) $mission['user_id'],
                    'email' => $mission['email'],
                    'first_name' => $mission['first_name'],
                    'last_name' => $mission['last_name'],
                ],
                'pending_project',
                'relance-projet',
                [
                    'prenom' => $mission['first_name'],
                    'titre' => $title,
                    'detail' => $detail,
                    'lien' => url('/espace/missions'),
                ],
                'Mission en cours',
                $detail,
                '/espace/missions',
                'mission',
                (int) $mission['id'],
                self::PROJECT_COOLDOWN_HOURS,
                self::PROJECT_MAX,
                $errors
            );
            if ($sent) {
                $stats['pending_project']++;
            }
        }
    }

    /** @param array<string, int> $stats @param list<string> $errors */
    private static function remindSilentClients(array &$stats, array &$errors): void
    {
        $hours = 5 * 24;
        try {
            $orders = Database::fetchAll(
                "SELECT o.id, o.number, o.buyer_id,
                        buyer.email, buyer.first_name, buyer.last_name, buyer.status
                 FROM orders o
                 JOIN users buyer ON buyer.id = o.buyer_id
                 WHERE o.status = 'delivered'
                   AND o.confirmed_at IS NULL
                   AND o.delivered_at IS NOT NULL
                   AND o.delivered_at <= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
                   AND buyer.status = 'active'"
            );
        } catch (Throwable $e) {
            $errors[] = 'validations : ' . $e->getMessage();
            return;
        }

        foreach ($orders as $order) {
            $title = 'Commande ' . (string) ($order['number'] ?? ('#' . $order['id']));
            $detail = 'La livraison de ' . $title . ' attend votre validation et votre avis. Sans réponse après relance, la livraison pourra être considérée comme acceptée.';
            $buyer = User::find((int) $order['buyer_id']) ?? [
                'id' => (int) $order['buyer_id'],
                'email' => $order['email'],
                'first_name' => $order['first_name'],
                'last_name' => $order['last_name'],
            ];
            $sent = self::dispatch(
                $buyer,
                'delivery_validation',
                'relance-projet',
                [
                    'prenom' => $order['first_name'],
                    'titre' => $title,
                    'detail' => $detail,
                    'lien' => url('/espace/suivi/' . (int) $order['id']),
                ],
                'Livraison à valider',
                $detail,
                '/espace/suivi/' . (int) $order['id'],
                'order',
                (int) $order['id'],
                7 * 24,
                3,
                $errors
            );
            if ($sent) {
                $stats['delivery_validation']++;
            }
        }
    }

    /** @param array<string, int> $stats @param list<string> $errors */
    private static function markOverdueInvoices(array &$stats, array &$errors): void
    {
        try {
            Invoice::markOverdue();
            foreach (Invoice::overdueUnnotified() as $row) {
                $invoice = Invoice::present($row);
                $sellerId = (int) ($invoice['seller_id'] ?? 0);
                if ($sellerId < 1) {
                    continue;
                }
                $invoiceId = (int) ($invoice['id'] ?? 0);
                if ($invoiceId < 1) {
                    continue;
                }
                if (!ReminderSend::isDue('invoice_overdue', $sellerId, 7 * 24, 6, 'invoice', $invoiceId)) {
                    continue;
                }
                $seller = User::find($sellerId);
                if (!Notification::hasUnread($sellerId, 'invoice_overdue', 'invoice', $invoiceId)) {
                    Notification::create(
                        $sellerId,
                        'Facture échue — prestations suspendues',
                        'La facture ' . $invoice['number'] . ' n\'a pas été réglée. Vos fiches ne sont plus proposées tant que le paiement n\'est pas reçu.',
                        '/espace/facturation',
                        'invoice_overdue',
                        'invoice',
                        $invoiceId
                    );
                }
                if ($seller && User::wantsEmail($seller, 'jalons')) {
                    try {
                        Mailer::sendTemplate('facture-echue', (string) $seller['email'], [
                            'numero' => (string) ($invoice['number'] ?? ''),
                            'lien' => url('/espace/facturation'),
                        ]);
                    } catch (Throwable $e) {
                        $errors[] = 'facture-echue ' . (string) ($seller['email'] ?? '') . ' : ' . $e->getMessage();
                        continue;
                    }
                }
                ReminderSend::record('invoice_overdue', $sellerId, 'invoice', $invoiceId);
                $stats['invoices_overdue']++;
            }
        } catch (Throwable $e) {
            $errors[] = 'invoices_overdue : ' . $e->getMessage();
        }
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, string> $vars
     * @param list<string> $errors
     */
    private static function dispatch(
        array $user,
        string $kind,
        string $emailSlug,
        array $vars,
        string $title,
        string $body,
        string $link,
        ?string $subjectType,
        ?int $subjectId,
        int $cooldownHours,
        int $maxSends,
        array &$errors
    ): bool {
        $userId = (int) $user['id'];
        if ($userId < 1) {
            return false;
        }
        $full = User::find($userId);
        if ($full) {
            $user = array_merge($user, $full);
        }
        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '') {
            return false;
        }

        $channel = match ($kind) {
            'unanswered_message' => 'messages',
            'pending_project', 'delivery_validation' => 'jalons',
            'unanswered_application', 'missing_mission', 'profile_incomplete' => 'missions',
            default => null,
        };

        if (!ReminderSend::isDue($kind, $userId, $cooldownHours, $maxSends, $subjectType, $subjectId)) {
            return false;
        }

        try {
            if (!Notification::hasUnread($userId, $kind, $subjectType, $subjectId)) {
                Notification::create($userId, $title, $body, $link, $kind, $subjectType, $subjectId);
            }
        } catch (Throwable $e) {
            $errors[] = $kind . ' notification #' . $userId . ' : ' . $e->getMessage();
        }

        if ($channel !== null && !User::wantsEmail($user, $channel)) {
            ReminderSend::record($kind, $userId, $subjectType, $subjectId);
            return true;
        }

        try {
            Mailer::sendTemplate($emailSlug, $email, $vars);
            ReminderSend::record($kind, $userId, $subjectType, $subjectId);
            return true;
        } catch (Throwable $e) {
            ReminderSend::record($kind, $userId, $subjectType, $subjectId);
            $errors[] = $kind . ' e-mail ' . $email . ' : ' . $e->getMessage();
            return false;
        }
    }

    /** @param list<string> $items */
    private static function joinFr(array $items): string
    {
        $items = array_values(array_filter($items, static fn (string $v): bool => $v !== ''));
        $n = count($items);
        if ($n === 0) {
            return 'quelques informations';
        }
        if ($n === 1) {
            return $items[0];
        }
        $last = array_pop($items);
        return implode(', ', $items) . ' et ' . $last;
    }
}
