<?php

declare(strict_types=1);

use Adl\Models\Profile;

return static function (PDO $pdo): void {
    Profile::ensureVerificationReminderTemplate();
};
