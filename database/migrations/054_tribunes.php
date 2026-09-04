<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        'ALTER TABLE articles
            ADD COLUMN author_id INT UNSIGNED NULL AFTER id,
            ADD COLUMN submission_status VARCHAR(20) NOT NULL DEFAULT "approved" AFTER body,
            ADD COLUMN moderation_note TEXT NULL AFTER submission_status,
            ADD COLUMN submitted_at DATETIME NULL AFTER moderation_note,
            ADD COLUMN moderated_at DATETIME NULL AFTER submitted_at,
            ADD INDEX idx_articles_author (author_id),
            ADD INDEX idx_articles_submission (submission_status, submitted_at),
            ADD CONSTRAINT fk_articles_author
                FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL'
    );

    $templates = [
        [
            'tribune-validee',
            'Tribune validée',
            'Votre tribune « {{ titre }} » est publiée',
            '<p>Bonjour {{ prenom }},</p><p>Votre tribune <strong>{{ titre }}</strong> a été validée et publiée dans le journal d’Acteurs du Livre.</p><p><a href="{{ lien_article }}">Lire l’article</a></p>',
            'prenom, titre, lien_article',
        ],
        [
            'tribune-refusee',
            'Tribune refusée',
            'Décision concernant votre tribune « {{ titre }} »',
            '<p>Bonjour {{ prenom }},</p><p>Votre tribune <strong>{{ titre }}</strong> n’a pas été retenue en l’état.</p><p><strong>Motif :</strong><br>{{ motif }}</p><p>Vous pouvez la modifier puis la soumettre de nouveau depuis votre espace.</p><p><a href="{{ lien_tribune }}">Reprendre ma tribune</a></p>',
            'prenom, titre, motif, lien_tribune',
        ],
    ];
    $insert = $pdo->prepare(
        'INSERT INTO email_templates (slug, name, subject, body_html, variables)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE slug = VALUES(slug)'
    );
    foreach ($templates as $template) {
        $insert->execute($template);
    }
};
