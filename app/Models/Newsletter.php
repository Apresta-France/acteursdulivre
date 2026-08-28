<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use RuntimeException;

final class Newsletter
{
    public static function subscribe(string $email): void
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Indiquez une adresse e-mail valide.');
        }
        Database::query(
            'INSERT INTO newsletter_subscribers (email, created_at) VALUES (?, NOW())
             ON DUPLICATE KEY UPDATE email = email',
            [$email]
        );
    }
}
