<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Data\LegalPages;

final class LegalAcceptance
{
    /**
     * @param list<string> $documents
     */
    public static function recordMany(int $userId, array $documents, string $context, ?string $ip = null, ?string $version = null): void
    {
        foreach ($documents as $document) {
            self::record($userId, $document, $context, $ip, $version);
        }
    }

    public static function record(int $userId, string $document, string $context, ?string $ip = null, ?string $version = null): void
    {
        $version = $version ?? LegalPages::VERSION;
        $ip = $ip ?? self::clientIp();

        Database::query(
            'INSERT INTO legal_acceptances (user_id, document, version, context, ip, accepted_at)
             VALUES (?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE accepted_at = VALUES(accepted_at), ip = VALUES(ip)',
            [$userId, $document, $version, $context, $ip]
        );
    }

    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        try {
            return Database::fetchAll(
                'SELECT document, version, context, accepted_at FROM legal_acceptances
                 WHERE user_id = ? ORDER BY accepted_at DESC',
                [$userId]
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public static function has(int $userId, string $document, ?string $version = null): bool
    {
        $version = $version ?? LegalPages::VERSION;
        $row = Database::fetch(
            'SELECT id FROM legal_acceptances WHERE user_id = ? AND document = ? AND version = ? LIMIT 1',
            [$userId, $document, $version]
        );
        return $row !== null;
    }

    public static function clientIp(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        return is_string($ip) && $ip !== '' ? $ip : null;
    }
}
