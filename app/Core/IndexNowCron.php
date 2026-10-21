<?php

declare(strict_types=1);

namespace Adl\Core;

use Adl\Data\SalonHubs;

/** Soumet à IndexNow l'agenda et les fiches de salons récentes, au plus toutes les 6 heures. */
final class IndexNowCron
{
    public static function run(bool $force = false): array
    {
        $stamp = ADL_ROOT . '/storage/cache/indexnow-last.txt';
        if (!$force && is_file($stamp) && (time() - (int) filemtime($stamp)) < 21600) {
            return ['ok' => true, 'skipped' => true, 'reason' => 'recent'];
        }

        $urls = ['/', '/salons', '/maisons-edition'];
        try {
            foreach (SalonHubs::nav() as $item) {
                $urls[] = (string) $item['href'];
            }
        } catch (\Throwable) {
        }
        try {
            $rows = Database::fetchAll(
                'SELECT slug FROM salons WHERE slug != "" AND created_at >= (NOW() - INTERVAL 14 DAY) ORDER BY id DESC LIMIT 200'
            );
            foreach ($rows as $row) {
                $urls[] = '/salons/' . (string) ($row['slug'] ?? '');
            }
        } catch (\Throwable) {
        }

        $result = IndexNow::submit($urls);
        $dir = dirname($stamp);
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        @file_put_contents($stamp, (string) time());

        return [
            'ok' => true,
            'submitted' => (int) ($result['submitted'] ?? 0),
            'reached' => !empty($result['ok']),
        ];
    }
}
