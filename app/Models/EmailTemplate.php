<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;

final class EmailTemplate
{
    public static function all(): array
    {
        return Database::fetchAll('SELECT * FROM email_templates ORDER BY name ASC');
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM email_templates WHERE id = ?', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM email_templates WHERE slug = ?', [$slug]);
    }

    public static function update(int $id, string $subject, string $body): void
    {
        Database::query(
            'UPDATE email_templates SET subject = ?, body_html = ?, updated_at = NOW() WHERE id = ?',
            [$subject, $body, $id]
        );
    }

    public static function ensure(string $slug, string $name, string $subject, string $body, string $variables): void
    {
        if (self::findBySlug($slug)) {
            return;
        }
        Database::query(
            'INSERT INTO email_templates (slug, name, subject, body_html, variables) VALUES (?, ?, ?, ?, ?)',
            [$slug, $name, $subject, $body, $variables]
        );
    }
}
