<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        'ALTER TABLE forum_topics
            ADD COLUMN article_id INT UNSIGNED NULL AFTER category_id,
            ADD UNIQUE KEY uq_forum_topic_article (article_id),
            ADD CONSTRAINT fk_forum_topics_article
                FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE SET NULL'
    );
};
