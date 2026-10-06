<?php

declare(strict_types=1);

/**
 * Coche la lettre d'information pour les comptes créés avant octobre 2026,
 * et les confirme comme abonnés — même effet que la case du compte.
 */
return static function (PDO $pdo): void {
    $userCols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('notify_newsletter', $userCols, true)) {
        return;
    }
    $subCols = $pdo->query('SHOW COLUMNS FROM newsletter_subscribers')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('status', $subCols, true)) {
        return;
    }

    $pdo->exec(
        'UPDATE users
         SET notify_newsletter = 1
         WHERE created_at < "2026-10-01 00:00:00"
           AND deleted_at IS NULL'
    );

    $users = $pdo->query(
        'SELECT id, email FROM users
         WHERE created_at < "2026-10-01 00:00:00"
           AND deleted_at IS NULL
           AND email <> ""'
    )->fetchAll(PDO::FETCH_ASSOC);

    $find = $pdo->prepare(
        'SELECT id, status, unsub_token, user_id FROM newsletter_subscribers WHERE LOWER(email) = ? LIMIT 1'
    );
    $linkUser = $pdo->prepare(
        'UPDATE newsletter_subscribers SET user_id = ? WHERE id = ? AND user_id IS NULL'
    );
    $confirm = $pdo->prepare(
        'UPDATE newsletter_subscribers
         SET status = "confirmed",
             confirm_token = NULL,
             unsub_token = IF(unsub_token IS NULL OR unsub_token = "", ?, unsub_token),
             user_id = COALESCE(user_id, ?),
             confirmed_at = COALESCE(confirmed_at, NOW()),
             unsubscribed_at = NULL
         WHERE id = ?'
    );
    $insert = $pdo->prepare(
        'INSERT INTO newsletter_subscribers
            (email, created_at, status, confirm_token, unsub_token, user_id, source, confirmed_at)
         VALUES (?, NOW(), "confirmed", NULL, ?, ?, "account", NOW())'
    );

    foreach ($users as $user) {
        $email = strtolower(trim((string) $user['email']));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }
        $find->execute([$email]);
        $row = $find->fetch(PDO::FETCH_ASSOC);
        $find->closeCursor();
        if ($row && ($row['status'] ?? '') === 'confirmed' && trim((string) ($row['unsub_token'] ?? '')) !== '') {
            if ((int) ($row['user_id'] ?? 0) === 0) {
                $linkUser->execute([(int) $user['id'], (int) $row['id']]);
            }
            continue;
        }
        if ($row) {
            $confirm->execute([bin2hex(random_bytes(16)), (int) $user['id'], (int) $row['id']]);
            continue;
        }
        $insert->execute([$email, bin2hex(random_bytes(16)), (int) $user['id']]);
    }
};
