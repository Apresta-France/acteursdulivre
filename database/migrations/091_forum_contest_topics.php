<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        'ALTER TABLE forum_topics
            ADD COLUMN contest_id INT UNSIGNED NULL AFTER salon_id,
            ADD UNIQUE KEY uq_forum_topic_contest (contest_id),
            ADD CONSTRAINT fk_forum_topics_contest
                FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE SET NULL'
    );
};
