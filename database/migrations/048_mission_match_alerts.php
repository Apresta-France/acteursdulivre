<?php

declare(strict_types=1);

use Adl\Models\EmailTemplate;

return static function (PDO $pdo): void {
    $cols = array_column($pdo->query('SHOW COLUMNS FROM missions')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    if (!in_array('match_alerted_at', $cols, true)) {
        $pdo->exec('ALTER TABLE missions ADD COLUMN match_alerted_at DATETIME NULL AFTER status');
        $pdo->exec(
            "UPDATE missions
             SET match_alerted_at = COALESCE(created_at, NOW())
             WHERE status IN ('open', 'assigned', 'closed')
               AND match_alerted_at IS NULL"
        );
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS mission_match_alerts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            mission_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_mission_user (mission_id, user_id),
            INDEX idx_user_sent (user_id, sent_at),
            FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    EmailTemplate::ensure(
        'nouvelle-mission-metier',
        'Nouvelle recherche correspondant au métier',
        'Nouvelle recherche {{ metier }} : {{ titre }}',
        '<p>Bonjour {{ prenom }},</p><p>Une recherche vient d’être publiée dans l’un de vos métiers&nbsp;: <strong>{{ titre }}</strong> ({{ metier }}).</p><p>Budget&nbsp;: {{ budget }}.</p><p>Vous n’avez pas encore de mission attribuée : c’est le moment de proposer votre approche.</p><p><a href="{{ lien }}">Voir la recherche</a></p>',
        'prenom, metier, titre, budget, lien'
    );
};
