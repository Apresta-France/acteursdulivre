<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use Adl\Core\Mailer;
use Adl\Data\ArticleHtml;

final class Article
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public static function find(int $id): ?array
    {
        $row = Database::fetch('SELECT * FROM articles WHERE id = ?', [$id]);
        return $row ? self::present($row) : null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $row = Database::fetch('SELECT * FROM articles WHERE slug = ?', [$slug]);
        return $row ? self::present($row) : null;
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        $row = Database::fetch(
            'SELECT * FROM articles WHERE id = ? AND author_id = ?',
            [$id, $userId]
        );
        return $row ? self::present($row) : null;
    }

    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM articles WHERE author_id = ? ORDER BY created_at DESC',
            [$userId]
        );
        return array_map([self::class, 'present'], $rows);
    }

    /** @return list<array<string, mixed>> */
    public static function submitted(): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM articles
             WHERE author_id IS NOT NULL AND submission_status IN ("pending", "rejected", "approved")
             ORDER BY CASE submission_status WHEN "pending" THEN 0 ELSE 1 END, submitted_at DESC, created_at DESC
             LIMIT 100'
        );
        return array_map([self::class, 'present'], $rows);
    }

    /** @param array<string, mixed> $data */
    public static function save(?int $id, array $data): int
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new \InvalidArgumentException('Le titre est obligatoire.');
        }
        $category = trim((string) ($data['category'] ?? '')) ?: 'Journal';
        $excerpt = trim((string) ($data['excerpt'] ?? '')) ?: null;
        $body = sanitize_rich_html((string) ($data['body'] ?? ''));
        $published = !empty($data['published']);
        $current = $id ? self::find($id) : null;
        $slugSource = trim((string) ($data['slug'] ?? '')) ?: $title;
        $slug = unique_slug(
            $slugSource,
            static fn (string $candidate): bool => Database::fetch(
                'SELECT id FROM articles WHERE slug = ? AND id != ?',
                [$candidate, $id ?? 0]
            ) !== null
        );
        $publishedAt = $published
            ? (($current['published_at'] ?? null) ?: date('Y-m-d H:i:s'))
            : null;
        $imagePath = array_key_exists('image_path', $data)
            ? (trim((string) ($data['image_path'] ?? '')) ?: null)
            : ($current['image_path'] ?? null);
        $imageAlt = trim((string) ($data['image_alt'] ?? '')) ?: null;

        if ($id && $current) {
            if (
                $imagePath !== ($current['image_path'] ?? null)
                && !str_starts_with((string) ($current['image_path'] ?? ''), 'img/')
            ) {
                self::deleteImageFile((string) ($current['image_path'] ?? ''));
            }
            Database::query(
                'UPDATE articles SET title = ?, slug = ?, category = ?, excerpt = ?, image_path = ?, image_alt = ?, body = ?, published_at = ? WHERE id = ?',
                [$title, $slug, $category, $excerpt, $imagePath, $imageAlt, $body, $publishedAt, $id]
            );
            return $id;
        }

        Database::query(
            'INSERT INTO articles (title, slug, category, excerpt, image_path, image_alt, body, published_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [$title, $slug, $category, $excerpt, $imagePath, $imageAlt, $body, $publishedAt]
        );
        return (int) Database::lastId();
    }

    /** @param array<string, mixed> $data */
    public static function saveForUser(?int $id, int $userId, array $data): int
    {
        $current = $id ? self::findForUser($id, $userId) : null;
        if ($id && !$current) {
            throw new \RuntimeException('Tribune introuvable.');
        }
        if ($current && !in_array((string) $current['submission_status'], [self::STATUS_DRAFT, self::STATUS_REJECTED], true)) {
            throw new \RuntimeException('Cette tribune ne peut plus être modifiée pendant ou après sa modération.');
        }

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new \InvalidArgumentException('Le titre est obligatoire.');
        }
        $excerpt = trim((string) ($data['excerpt'] ?? '')) ?: null;
        $body = sanitize_rich_html((string) ($data['body'] ?? ''));
        $imagePath = array_key_exists('image_path', $data)
            ? (trim((string) ($data['image_path'] ?? '')) ?: null)
            : ($current['image_path'] ?? null);
        $imageAlt = trim((string) ($data['image_alt'] ?? '')) ?: null;
        $slug = unique_slug(
            $title,
            static fn (string $candidate): bool => Database::fetch(
                'SELECT id FROM articles WHERE slug = ? AND id != ?',
                [$candidate, $id ?? 0]
            ) !== null
        );

        if ($current) {
            if (
                $imagePath !== ($current['image_path'] ?? null)
                && !str_starts_with((string) ($current['image_path'] ?? ''), 'img/')
            ) {
                self::deleteImageFile((string) ($current['image_path'] ?? ''));
            }
            Database::query(
                'UPDATE articles
                 SET title = ?, slug = ?, category = "Tribune", excerpt = ?, image_path = ?, image_alt = ?, body = ?
                 WHERE id = ? AND author_id = ?',
                [$title, $slug, $excerpt, $imagePath, $imageAlt, $body, $id, $userId]
            );
            return $id;
        }

        Database::query(
            'INSERT INTO articles
                (author_id, title, slug, category, excerpt, image_path, image_alt, body, submission_status, created_at)
             VALUES (?, ?, ?, "Tribune", ?, ?, ?, ?, "draft", NOW())',
            [$userId, $title, $slug, $excerpt, $imagePath, $imageAlt, $body]
        );
        return (int) Database::lastId();
    }

    public static function submit(int $id, int $userId): void
    {
        $article = self::findForUser($id, $userId);
        if (!$article || !in_array((string) $article['submission_status'], [self::STATUS_DRAFT, self::STATUS_REJECTED], true)) {
            throw new \RuntimeException('Cette tribune ne peut pas être soumise.');
        }
        if (trim(strip_tags((string) ($article['body'] ?? ''))) === '') {
            throw new \InvalidArgumentException('Écrivez le contenu de la tribune avant de la soumettre.');
        }
        Database::query(
            'UPDATE articles
             SET submission_status = "pending", moderation_note = NULL, submitted_at = NOW(), moderated_at = NULL, published_at = NULL
             WHERE id = ? AND author_id = ?',
            [$id, $userId]
        );
        Notification::markSubjectRead($userId, 'tribune_rejected', 'article', $id);
    }

    public static function moderate(int $id, string $status, string $note = ''): void
    {
        if (!in_array($status, [self::STATUS_APPROVED, self::STATUS_REJECTED], true)) {
            throw new \InvalidArgumentException('Décision de modération inconnue.');
        }
        $article = self::find($id);
        if (!$article || empty($article['author_id'])) {
            throw new \RuntimeException('Cette tribune est introuvable.');
        }
        $note = trim($note);
        if ($status === self::STATUS_REJECTED && $note === '') {
            throw new \InvalidArgumentException('Indiquez le motif du refus.');
        }

        Database::query(
            'UPDATE articles
             SET submission_status = ?, moderation_note = ?, moderated_at = NOW(), published_at = ?
             WHERE id = ? AND author_id IS NOT NULL',
            [
                $status,
                $note !== '' ? $note : null,
                $status === self::STATUS_APPROVED ? date('Y-m-d H:i:s') : null,
                $id,
            ]
        );

        $userId = (int) $article['author_id'];
        $approved = $status === self::STATUS_APPROVED;
        $link = $approved ? '/journal/' . $article['slug'] : '/espace/tribune/' . $id;
        try {
            Notification::create(
                $userId,
                $approved ? 'Votre tribune est publiée' : 'Votre tribune est à reprendre',
                $approved
                    ? '« ' . $article['title'] . ' » a été validée et publiée dans le journal.'
                    : '« ' . $article['title'] . ' » n’a pas été retenue en l’état. Motif : ' . $note,
                $link,
                $approved ? 'tribune_approved' : 'tribune_rejected',
                'article',
                $id
            );
        } catch (\Throwable) {
        }

        $user = User::find($userId);
        Mailer::notify($user, 'transactional', $approved ? 'tribune-validee' : 'tribune-refusee', [
            'titre' => (string) $article['title'],
            'motif' => $note,
            'lien_article' => \Adl\Data\Share::absolute('/journal/' . $article['slug']),
            'lien_tribune' => \Adl\Data\Share::absolute('/espace/tribune/' . $id),
        ]);
    }

    public static function deleteForUser(int $id, int $userId): void
    {
        $article = self::findForUser($id, $userId);
        if (!$article || !in_array((string) $article['submission_status'], [self::STATUS_DRAFT, self::STATUS_REJECTED], true)) {
            throw new \RuntimeException('Cette tribune ne peut pas être supprimée.');
        }
        self::delete($id);
    }

    public static function delete(int $id): void
    {
        $current = self::find($id);
        Database::query('DELETE FROM articles WHERE id = ?', [$id]);
        if ($current && !str_starts_with((string) ($current['image_path'] ?? ''), 'img/')) {
            self::deleteImageFile((string) ($current['image_path'] ?? ''));
        }
    }

    /** @var list<string> */
    private const CATEGORY_ORDER = ['Tarifs', 'Contrats', 'Métier', 'Fabrication', 'Diffusion', 'Plateforme'];

    public const PER_PAGE = 9;

    /** @return list<array<string, mixed>> */
    public static function published(): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM articles
             WHERE published_at IS NOT NULL AND published_at <= NOW()
             ORDER BY published_at DESC'
        );
        return array_map([self::class, 'present'], $rows);
    }

    public static function countPublished(): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM articles
             WHERE published_at IS NOT NULL AND published_at <= NOW()'
        );
        return (int) ($row['n'] ?? 0);
    }

    public static function countPendingSubmissions(): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM articles
             WHERE author_id IS NOT NULL AND submission_status = "pending"'
        );
        return (int) ($row['n'] ?? 0);
    }

    public static function countPublishedTribunes(): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM articles
             WHERE author_id IS NOT NULL
               AND submission_status = "approved"
               AND published_at IS NOT NULL
               AND published_at <= NOW()'
        );
        return (int) ($row['n'] ?? 0);
    }

    /**
     * @return list<array{label: string, n: int}>
     */
    public static function publishedCategories(): array
    {
        $rows = Database::fetchAll(
            'SELECT category AS label, COUNT(*) AS n FROM articles
             WHERE published_at IS NOT NULL AND published_at <= NOW()
               AND category IS NOT NULL AND TRIM(category) != \'\'
             GROUP BY category'
        );
        $byLabel = [];
        foreach ($rows as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $byLabel[$label] = ['label' => $label, 'n' => (int) ($row['n'] ?? 0)];
        }
        $out = [];
        foreach (self::CATEGORY_ORDER as $label) {
            if (isset($byLabel[$label])) {
                $out[] = $byLabel[$label];
                unset($byLabel[$label]);
            }
        }
        ksort($byLabel, SORT_NATURAL | SORT_FLAG_CASE);
        return array_merge($out, array_values($byLabel));
    }

    /** @param list<string>|null $valid */
    public static function resolveCategory(string $raw, ?array $valid = null): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        $valid = $valid ?? array_column(self::publishedCategories(), 'label');
        $needle = slugify($raw);
        foreach ($valid as $label) {
            if (strcasecmp($label, $raw) === 0 || slugify($label) === $needle) {
                return $label;
            }
        }
        return '';
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public static function searchPublished(string $q, string $category = '', int $page = 1, int $perPage = self::PER_PAGE): array
    {
        $perPage = max(1, min(48, $perPage));
        $filter = self::publishedFilter($q, $category);
        $count = Database::fetch(
            'SELECT COUNT(*) AS n FROM articles WHERE ' . $filter['sql'],
            $filter['params']
        );
        $total = (int) ($count['n'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;
        $rows = $total === 0
            ? []
            : Database::fetchAll(
                'SELECT * FROM articles WHERE ' . $filter['sql']
                . ' ORDER BY published_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
                $filter['params']
            );

        return [
            'items' => array_map([self::class, 'present'], $rows),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    /**
     * @return list<array{href: string, title: string, subtitle: string, kind_label: string, meta: string}>
     */
    public static function suggestPublished(string $q, string $category = '', int $limit = 8): array
    {
        $found = self::searchPublished($q, $category, 1, max(1, min(12, $limit)));
        $out = [];
        foreach ($found['items'] as $item) {
            $out[] = [
                'href' => (string) ($item['href'] ?? '/journal'),
                'title' => (string) ($item['title'] ?? ''),
                'subtitle' => (string) ($item['chapo'] ?? ''),
                'kind_label' => (string) ($item['cat'] ?? 'Journal'),
                'meta' => (string) ($item['read'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * @return array{sql: string, params: list<mixed>}
     */
    private static function publishedFilter(string $q, string $category): array
    {
        $where = ['published_at IS NOT NULL', 'published_at <= NOW()'];
        $params = [];
        $category = trim($category);
        if ($category !== '') {
            $where[] = 'category = ?';
            $params[] = $category;
        }
        $q = trim($q);
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(title LIKE ? OR excerpt LIKE ? OR category LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        return ['sql' => implode(' AND ', $where), 'params' => $params];
    }

    /** @return list<array<string, mixed>> */
    public static function preview(int $limit = 3, string $category = ''): array
    {
        $category = trim($category);
        if ($category === '') {
            return array_slice(self::published(), 0, $limit);
        }
        $found = self::searchPublished('', $category, 1, max(1, $limit));
        return array_slice($found['items'], 0, $limit);
    }

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $rows = Database::fetchAll('SELECT * FROM articles ORDER BY created_at DESC');
        return array_map([self::class, 'present'], $rows);
    }

    /** @param array<string, mixed> $row */
    private static function present(array $row): array
    {
        $prepared = ArticleHtml::enhance(sanitize_rich_html((string) ($row['body'] ?? '')));
        $body = $prepared['html'];
        $words = max(1, str_word_count(strip_tags($body)));
        $imagePath = trim((string) ($row['image_path'] ?? ''));
        $row['body'] = $body;
        $row['body_html'] = $body;
        $row['toc'] = $prepared['toc'];
        $row['faqs'] = $prepared['faqs'];
        $row['cat'] = (string) ($row['category'] ?? 'Journal');
        $row['chapo'] = (string) ($row['excerpt'] ?? '');
        $row['read'] = max(1, (int) ceil($words / 200)) . ' min';
        $row['word_count'] = $words;
        $row['href'] = '/journal/' . $row['slug'];
        $row['img'] = $imagePath !== '' ? article_image_url($imagePath) : photo(((int) ($row['id'] ?? 0)) % 6);
        $row['has_cover'] = $imagePath !== '';
        $row['image_alt'] = trim((string) ($row['image_alt'] ?? '')) ?: (string) ($row['title'] ?? '');
        $row['slotId'] = 'jr-' . ($row['id'] ?? '0');
        $publishedAt = (string) ($row['published_at'] ?? '');
        $publishedTs = $publishedAt !== '' ? strtotime($publishedAt) : false;
        $row['published'] = $publishedTs !== false && $publishedTs <= time();
        $row['scheduled'] = $publishedTs !== false && $publishedTs > time();
        $submissionStatus = (string) ($row['submission_status'] ?? self::STATUS_APPROVED);
        $row['submission_status'] = $submissionStatus;
        $row['status'] = !empty($row['author_id'])
            ? self::statusLabel($submissionStatus)
            : ($row['published'] ? 'Publié' : ($row['scheduled'] ? 'Programmé' : 'Brouillon'));
        $row['status_label'] = $row['status'];
        $row['can_edit'] = in_array($submissionStatus, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
        $row['author_name'] = '';
        if (!empty($row['author_id'])) {
            $author = User::find((int) $row['author_id']);
            $row['author_name'] = $author ? User::displayName($author) : '';
        }
        $row['when'] = $row['published_at'] ? format_deadline(substr((string) $row['published_at'], 0, 10)) : '';
        $row['live'] = true;
        return $row;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'En modération',
            self::STATUS_APPROVED => 'Publiée',
            self::STATUS_REJECTED => 'À reprendre',
            default => 'Brouillon',
        };
    }

    private static function deleteImageFile(string $path): void
    {
        $path = trim(str_replace(['\\', "\0"], '/', $path));
        if ($path === '' || str_contains($path, '..') || str_starts_with($path, 'img/')) {
            return;
        }
        $full = ADL_ROOT . '/public/uploads/' . ltrim($path, '/');
        $root = realpath(ADL_ROOT . '/public/uploads');
        $real = realpath($full);
        if ($root === false || $real === false || !str_starts_with($real, $root) || !is_file($real)) {
            return;
        }
        @unlink($real);
    }
}
