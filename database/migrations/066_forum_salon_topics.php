<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        'ALTER TABLE forum_topics
            ADD COLUMN salon_id INT UNSIGNED NULL AFTER article_id,
            ADD UNIQUE KEY uq_forum_topic_salon (salon_id),
            ADD CONSTRAINT fk_forum_topics_salon
                FOREIGN KEY (salon_id) REFERENCES salons(id) ON DELETE SET NULL'
    );
};
