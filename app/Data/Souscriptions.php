<?php

declare(strict_types=1);

namespace Adl\Data;

use Adl\Models\Souscription;

final class Souscriptions
{
    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $items = [];
        foreach (self::raw() as $item) {
            $items[] = self::present($item);
        }

        return $items;
    }

    /** @return array{featured: ?array<string, mixed>, items: list<array<string, mixed>>, counts: array<string, int>} */
    public static function listing(string $type = ''): array
    {
        $all = self::all();
        $open = array_values(array_filter($all, static fn (array $item): bool => !empty($item['open'])));
        $closed = array_values(array_filter($all, static fn (array $item): bool => empty($item['open'])));

        $pool = match ($type) {
            'souscription' => array_values(array_filter(
                $open,
                static fn (array $item): bool => ($item['kind'] ?? '') === 'souscription'
            )),
            'vente' => array_values(array_filter(
                $open,
                static fn (array $item): bool => in_array($item['kind'] ?? '', ['prevente', 'vente'], true)
            )),
            'sponsorise' => array_values(array_filter(
                $open,
                static fn (array $item): bool => ($item['kind'] ?? '') === 'sponsorise'
            )),
            'terminees' => $closed,
            default => $open,
        };

        $featured = null;
        $items = $pool;
        if ($type !== 'terminees') {
            foreach ($pool as $i => $item) {
                if (!empty($item['featured'])) {
                    $featured = $item;
                    unset($items[$i]);
                    $items = array_values($items);
                    break;
                }
            }
        }

        return [
            'featured' => $featured,
            'items' => $items,
            'counts' => [
                'open' => count($open),
                'souscription' => count(array_filter($open, static fn (array $item): bool => $item['kind'] === 'souscription')),
                'vente' => count(array_filter($open, static fn (array $item): bool => in_array($item['kind'], ['prevente', 'vente'], true))),
                'sponsorise' => count(array_filter($open, static fn (array $item): bool => $item['kind'] === 'sponsorise')),
                'closed' => count($closed),
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function preview(int $limit = 3): array
    {
        $open = array_values(array_filter(self::all(), static fn (array $item): bool => !empty($item['open'])));
        usort($open, static function (array $a, array $b): int {
            $feat = ((int) !empty($b['featured'])) <=> ((int) !empty($a['featured']));
            if ($feat !== 0) {
                return $feat;
            }

            return strcmp((string) $a['closes'], (string) $b['closes']);
        });

        return array_slice($open, 0, $limit);
    }

    /** @return array<string, mixed>|null */
    public static function find(string $slug): ?array
    {
        foreach (self::all() as $item) {
            if ($item['slug'] === $slug) {
                return $item;
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    public static function others(string $slug, int $limit = 3): array
    {
        $items = [];
        foreach (self::all() as $item) {
            if ($item['slug'] === $slug || empty($item['open'])) {
                continue;
            }
            $items[] = $item;
            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    /** @param array<string, mixed> $item */
    private static function present(array $item): array
    {
        $item['href'] = '/souscriptions/' . $item['slug'];
        $host = trim((string) ($item['host'] ?? ''));
        $closes = trim((string) ($item['closes'] ?? ''));
        $sponsoredOpen = ($item['kind'] ?? '') === 'sponsorise' && ($item['status'] ?? '') === 'open';
        if ($closes === '') {
            $item['open'] = $sponsoredOpen;
            $item['days_left'] = $sponsoredOpen ? 9999 : -1;
            $item['when'] = $sponsoredOpen ? 'En cours' : 'Clôturée';
            $item['closes_label'] = '';
            if ($host === '') {
                $item['where_line'] = $sponsoredOpen ? 'Annonce sponsorisée' : 'Annonce close';
            } else {
                $item['where_line'] = $sponsoredOpen ? 'Sur ' . $host : 'Était sur ' . $host;
            }

            return $item;
        }

        $today = new \DateTimeImmutable('today');
        $end = new \DateTimeImmutable($closes);
        $diff = $today->diff($end);
        $daysLeft = $diff->invert ? -$diff->days : $diff->days;
        $open = ($item['status'] ?? 'open') === 'open' && $daysLeft >= 0;
        $closesLabel = self::frenchDate($end);

        $item['open'] = $open;
        $item['days_left'] = $daysLeft;
        $item['when'] = self::whenLabel($open, $daysLeft);
        $item['closes_label'] = $closesLabel;
        $item['where_line'] = $host === ''
            ? ($open ? 'Jusqu’au ' . $closesLabel : 'Close le ' . $closesLabel)
            : ($open ? 'Sur ' . $host . ' · jusqu’au ' . $closesLabel : 'Était sur ' . $host);

        return $item;
    }

    private static function whenLabel(bool $open, int $daysLeft): string
    {
        if (!$open) {
            return 'Clôturée';
        }
        if ($daysLeft === 0) {
            return 'Dernier jour';
        }
        if ($daysLeft === 1) {
            return 'Plus qu’un jour';
        }

        return 'Plus que ' . $daysLeft . ' jours';
    }

    private static function frenchDate(\DateTimeImmutable $date): string
    {
        $months = [
            1 => 'janv.', 2 => 'févr.', 3 => 'mars', 4 => 'avr.', 5 => 'mai', 6 => 'juin',
            7 => 'juil.', 8 => 'août', 9 => 'sept.', 10 => 'oct.', 11 => 'nov.', 12 => 'déc.',
        ];

        return (int) $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');
    }

    /** @return list<array<string, mixed>> */
    private static function raw(): array
    {
        return Souscription::publicItems();
    }
}

