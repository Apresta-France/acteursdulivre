<?php

declare(strict_types=1);

namespace Adl\Models;

use Adl\Core\Database;
use RuntimeException;

final class OrderFile
{
    public const MAX_BYTES = 20 * 1024 * 1024;
    public const MAX_PER_ORDER = 80;
    public const ALLOWED_EXT = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif', 'txt', 'doc', 'docx', 'odt'];
    public const PREVIEW_EXT = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif'];
    public const OPEN_STATUSES = ['in_progress', 'delivered', 'dispute'];
    public const VISIBLE_STATUSES = ['in_progress', 'delivered', 'confirmed', 'paid', 'cancelled', 'dispute'];

    public static function isVisible(array $order): bool
    {
        return in_array((string) ($order['status'] ?? ''), self::VISIBLE_STATUSES, true);
    }

    public static function canDeposit(array $order): bool
    {
        return in_array((string) ($order['status'] ?? ''), self::OPEN_STATUSES, true);
    }

    /** @return list<array<string, mixed>> */
    public static function forOrder(int $orderId, int $viewerId, array $order): array
    {
        $rows = Database::fetchAll(
            'SELECT f.*, u.first_name, u.last_name
             FROM order_files f
             JOIN users u ON u.id = f.user_id
             WHERE f.order_id = ?
             ORDER BY f.created_at DESC, f.id DESC',
            [$orderId]
        );

        $seen = self::seenFileIds(array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $rows));

        return array_map(
            static fn (array $row): array => self::present($row, $viewerId, $order, $seen),
            $rows
        );
    }

    public static function countForOrder(int $orderId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM order_files WHERE order_id = ? AND withdrawn_at IS NULL',
            [$orderId]
        );
        return (int) ($row['n'] ?? 0);
    }

    /**
     * @param list<int> $orderIds
     * @return array<int, int>
     */
    public static function countsByOrderIds(array $orderIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $orderIds), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::fetchAll(
            "SELECT order_id, COUNT(*) AS n FROM order_files
             WHERE order_id IN ({$in}) AND withdrawn_at IS NULL
             GROUP BY order_id",
            $ids
        );
        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['order_id']] = (int) ($row['n'] ?? 0);
        }
        return $out;
    }

    /** @return array<string, mixed> */
    public static function findForParty(int $orderId, int $fileId, int $userId, array $order): ?array
    {
        $row = Database::fetch(
            'SELECT f.*, u.first_name, u.last_name
             FROM order_files f
             JOIN users u ON u.id = f.user_id
             WHERE f.id = ? AND f.order_id = ?',
            [$fileId, $orderId]
        );
        $seen = $row ? self::seenFileIds([(int) ($row['id'] ?? 0)]) : [];
        return $row ? self::present($row, $userId, $order, $seen) : null;
    }

    /**
     * @param array<string, mixed> $upload
     * @return array<string, mixed>
     */
    public static function create(int $orderId, int $userId, array $order, array $upload, string $note = ''): array
    {
        $buyerId = (int) ($order['buyer_id'] ?? 0);
        $sellerId = (int) ($order['seller_id'] ?? 0);
        if ($userId !== $buyerId && $userId !== $sellerId) {
            throw new RuntimeException('Seuls le porteur de projet et le prestataire peuvent déposer un fichier.');
        }
        if (!self::canDeposit($order)) {
            throw new RuntimeException('L’espace de dépôt n’est ouvert que lorsque le projet est en cours.');
        }
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Choisissez un fichier à déposer.');
        }

        $count = (int) (Database::fetch(
            'SELECT COUNT(*) AS n FROM order_files WHERE order_id = ?',
            [$orderId]
        )['n'] ?? 0);
        if ($count >= self::MAX_PER_ORDER) {
            throw new RuntimeException('Le nombre maximal de dépôts pour cette commande est atteint.');
        }

        $note = trim($note);
        if (mb_strlen($note) > 400) {
            throw new RuntimeException('La note est trop longue (400 caractères maximum).');
        }

        $stored = store_private_upload($upload, 'orders/' . $orderId . '/depot', self::ALLOWED_EXT, self::MAX_BYTES);
        if ($stored === null) {
            throw new RuntimeException('Le fichier n’a pas pu être enregistré.');
        }

        $ext = strtolower(pathinfo((string) ($stored['path'] ?? ''), PATHINFO_EXTENSION));
        $mime = upload_mime_map()[$ext][0] ?? 'application/octet-stream';

        Database::query(
            'INSERT INTO order_files (order_id, user_id, file_name, file_path, file_size, mime, note, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                $orderId,
                $userId,
                (string) ($stored['name'] ?? 'fichier'),
                (string) ($stored['path'] ?? ''),
                (int) ($stored['size'] ?? 0),
                $mime,
                $note !== '' ? $note : null,
            ]
        );

        $file = self::findForParty($orderId, (int) Database::lastId(), $userId, $order);
        if (!$file) {
            throw new RuntimeException('Le fichier n’a pas pu être enregistré.');
        }

        $otherId = self::otherPartyId($order, $userId);
        if ($otherId > 0) {
            try {
                $who = User::displayName(User::find($userId) ?? []) ?: 'Votre interlocuteur';
                Notification::create(
                    $otherId,
                    'Nouveau fichier — ' . (string) ($order['num'] ?? ''),
                    $who . ' a déposé « ' . (string) $file['file_name'] . ' » dans l’espace de dépôt.',
                    '/espace/suivi/' . $orderId . '/depot',
                    'order_file',
                    'order',
                    $orderId
                );
            } catch (\Throwable) {
            }
        }

        return $file;
    }

    public static function withdraw(int $orderId, int $fileId, int $userId, array $order): void
    {
        if (!self::canDeposit($order)) {
            throw new RuntimeException('Les fichiers ne peuvent plus être retirés.');
        }
        $file = self::findForParty($orderId, $fileId, $userId, $order);
        if (!$file) {
            throw new RuntimeException('Ce fichier est introuvable.');
        }
        if ((int) ($file['user_id'] ?? 0) !== $userId) {
            throw new RuntimeException('Seul l’auteur du dépôt peut retirer ce fichier.');
        }
        if (!empty($file['is_withdrawn'])) {
            throw new RuntimeException('Ce fichier a déjà été retiré.');
        }

        $path = (string) ($file['file_path'] ?? '');
        Database::query(
            'UPDATE order_files SET withdrawn_at = NOW(), file_path = "" WHERE id = ? AND order_id = ? AND user_id = ? AND withdrawn_at IS NULL',
            [$fileId, $orderId, $userId]
        );
        if ($path !== '') {
            delete_upload($path);
        }
    }

    public static function recordAccess(int $fileId, int $userId, string $action): void
    {
        if (!in_array($action, ['view', 'download'], true) || $fileId < 1 || $userId < 1) {
            return;
        }

        $recent = Database::fetch(
            'SELECT id FROM order_file_clicks
             WHERE file_id = ? AND user_id = ? AND action = ? AND created_at > DATE_SUB(NOW(), INTERVAL 45 SECOND)
             ORDER BY id DESC LIMIT 1',
            [$fileId, $userId, $action]
        );
        if ($recent) {
            return;
        }

        Database::transaction(static function () use ($fileId, $userId, $action): void {
            Database::query(
                'INSERT INTO order_file_clicks (file_id, user_id, action, created_at) VALUES (?, ?, ?, NOW())',
                [$fileId, $userId, $action]
            );
            if ($action === 'view') {
                Database::query(
                    'UPDATE order_files SET view_count = view_count + 1, last_viewed_at = NOW() WHERE id = ? AND withdrawn_at IS NULL',
                    [$fileId]
                );
            } else {
                Database::query(
                    'UPDATE order_files SET download_count = download_count + 1, last_downloaded_at = NOW() WHERE id = ? AND withdrawn_at IS NULL',
                    [$fileId]
                );
            }
        });
    }

    /** @return list<array<string, mixed>> */
    public static function clicks(int $fileId): array
    {
        $rows = Database::fetchAll(
            'SELECT c.*, u.first_name, u.last_name
             FROM order_file_clicks c
             JOIN users u ON u.id = c.user_id
             WHERE c.file_id = ?
             ORDER BY c.id DESC
             LIMIT 40',
            [$fileId]
        );
        return array_map(static function (array $row): array {
            $row['who'] = User::displayName($row) ?: 'Membre';
            $row['when'] = format_message_when($row['created_at'] ?? null);
            $row['action_label'] = ($row['action'] ?? '') === 'download' ? 'Téléchargement' : 'Consultation';
            return $row;
        }, $rows);
    }

    /** @return list<array<string, mixed>> */
    public static function exportForUser(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT f.id, f.order_id, o.number, f.file_name, f.file_size, f.note, f.view_count, f.download_count, f.created_at, f.withdrawn_at
             FROM order_files f
             JOIN orders o ON o.id = f.order_id
             WHERE f.user_id = ? OR o.buyer_id = ? OR o.seller_id = ?
             ORDER BY f.created_at DESC',
            [$userId, $userId, $userId]
        );
        return array_map(static function (array $row): array {
            unset($row['file_path']);
            return $row;
        }, $rows);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $order
     * @param array<int, true> $seen
     * @return array<string, mixed>
     */
    private static function present(array $row, int $viewerId, array $order, array $seen = []): array
    {
        $id = (int) ($row['id'] ?? 0);
        $orderId = (int) ($row['order_id'] ?? $order['id'] ?? 0);
        $ext = strtolower(pathinfo((string) ($row['file_name'] ?? $row['file_path'] ?? ''), PATHINFO_EXTENSION));
        $withdrawn = !empty($row['withdrawn_at']);
        $uploaderId = (int) ($row['user_id'] ?? 0);
        $mine = $uploaderId === $viewerId;
        $buyerId = (int) ($order['buyer_id'] ?? 0);
        $role = $uploaderId === $buyerId ? 'Porteur de projet' : 'Prestataire';

        $row['who'] = User::displayName($row) ?: $role;
        $row['role_label'] = $role;
        $row['mine'] = $mine;
        $row['when'] = format_message_when($row['created_at'] ?? null);
        $row['size_label'] = format_bytes((int) ($row['file_size'] ?? 0));
        $row['is_withdrawn'] = $withdrawn;
        $row['can_preview'] = !$withdrawn && in_array($ext, self::PREVIEW_EXT, true);
        $row['can_delete'] = $mine && !$withdrawn && self::canDeposit($order);
        $row['ext'] = $ext;
        $row['is_image'] = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
        $row['is_pdf'] = $ext === 'pdf';
        $row['view_href'] = $withdrawn ? '' : '/espace/suivi/' . $orderId . '/depot/' . $id;
        $row['preview_href'] = $row['can_preview'] ? '/espace/suivi/' . $orderId . '/depot/' . $id . '/voir' : '';
        $row['download_href'] = $withdrawn ? '' : '/espace/suivi/' . $orderId . '/depot/' . $id . '/telecharger';
        $row['views'] = (int) ($row['view_count'] ?? 0);
        $row['downloads'] = (int) ($row['download_count'] ?? 0);
        $row['seen_by_other'] = isset($seen[$id]);
        $row['mime'] = (string) ($row['mime'] ?? '');
        if ($row['mime'] === '') {
            $row['mime'] = upload_mime_map()[$ext][0] ?? 'application/octet-stream';
        }

        return $row;
    }

    /**
     * @param list<int> $fileIds
     * @return array<int, true>
     */
    private static function seenFileIds(array $fileIds): array
    {
        $ids = array_values(array_unique(array_filter($fileIds, static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::fetchAll(
            "SELECT DISTINCT c.file_id
             FROM order_file_clicks c
             JOIN order_files f ON f.id = c.file_id
             WHERE c.file_id IN ({$in}) AND c.user_id != f.user_id",
            $ids
        );
        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['file_id']] = true;
        }
        return $out;
    }

    private static function otherPartyId(array $order, int $userId): int
    {
        $buyer = (int) ($order['buyer_id'] ?? 0);
        $seller = (int) ($order['seller_id'] ?? 0);
        if ($userId === $buyer) {
            return $seller;
        }
        if ($userId === $seller) {
            return $buyer;
        }
        return 0;
    }
}
