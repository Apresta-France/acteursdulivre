<?php

declare(strict_types=1);

namespace Adl\Models;

final class Commission
{
    public const EXAMPLE_TTC = 235;
    public const VAT_RATE = 0.20;

    public static function percent(): int
    {
        $value = (int) (Setting::get('commission_percent', '8') ?: '8');
        return max(0, min(100, $value));
    }

    public static function founderLimit(): int
    {
        return max(0, (int) (Setting::get('founder_limit', '100') ?: '100'));
    }

    public static function founderPercent(): int
    {
        $value = (int) (Setting::get('founder_commission_percent', '6') ?: '6');
        return max(0, min(100, $value));
    }

    public static function loyaltyThreshold(): int
    {
        return max(0, (int) (Setting::get('loyalty_order_threshold', '12') ?: '12'));
    }

    public static function percentForSeller(int $sellerId): int
    {
        return self::quoteForSeller($sellerId)['ongoing_percent'];
    }

    public static function dueDays(): int
    {
        $value = (int) (Setting::get('invoice_due_days', '15') ?: '15');
        return max(1, $value);
    }

    /**
     * @return array{
     *   percent: int,
     *   first_free: bool,
     *   completed: int,
     *   founder: bool,
     *   loyal: bool,
     *   ongoing_percent: int
     * }
     */
    public static function quoteForSeller(int $sellerId): array
    {
        $completed = Order::completedCountForSeller($sellerId);
        $founder = User::isFounder($sellerId);
        $ongoing = self::ongoingPercent($founder, $completed);
        $loyal = !$founder && self::qualifiesForLoyalty($completed);

        if ($completed < 1) {
            return [
                'percent' => 0,
                'first_free' => true,
                'completed' => $completed,
                'founder' => $founder,
                'loyal' => false,
                'ongoing_percent' => $ongoing,
            ];
        }

        return [
            'percent' => $ongoing,
            'first_free' => false,
            'completed' => $completed,
            'founder' => $founder,
            'loyal' => $loyal,
            'ongoing_percent' => $ongoing,
        ];
    }

    /**
     * Taux et textes pour l'espace prestataire.
     *
     * @return array{
     *   percent: int,
     *   first_free: bool,
     *   completed: int,
     *   founder: bool,
     *   loyal: bool,
     *   ongoing_percent: int,
     *   label: string,
     *   detail: string,
     *   progress: string,
     *   example: array{ttc: string, ht: string, fee: string, fee_vat: string, fee_ttc: string, percent: int, first_free: bool}
     * }
     */
    public static function accountState(int $sellerId): array
    {
        $quote = self::quoteForSeller($sellerId);
        $reduced = self::founderPercent();
        $standard = self::percent();
        $threshold = self::loyaltyThreshold();
        $display = $quote['first_free'] ? 0 : $quote['percent'];

        if ($quote['first_free']) {
            $detail = $quote['founder']
                ? 'Première mission offerte. Ensuite ' . $quote['ongoing_percent'] . ' % (membre fondateur), hors taxes.'
                : 'Première mission offerte. Ensuite ' . $quote['ongoing_percent'] . ' % hors taxes'
                    . ($threshold > 0 ? ', puis ' . $reduced . ' % dès ' . $threshold . ' missions réalisées.' : '.');
        } elseif ($quote['founder']) {
            $detail = 'Taux membre fondateur (parmi les ' . self::founderLimit() . ' premiers inscrits), hors taxes.';
        } elseif ($quote['loyal']) {
            $detail = 'Taux fidélité : ' . $threshold . ' missions réalisées. Hors taxes.';
        } else {
            $remaining = $threshold > 0 ? max(0, $threshold - $quote['completed']) : 0;
            $detail = 'Taux standard, hors taxes.';
            if ($threshold > 0 && $remaining > 0) {
                $detail .= ' Passe à ' . $reduced . ' % dès ' . $threshold . ' missions réalisées (encore '
                    . $remaining . ' mission' . ($remaining > 1 ? 's' : '') . ').';
            }
        }

        $progress = '';
        if (!$quote['founder'] && $threshold > 0 && $quote['completed'] < $threshold) {
            $progress = $quote['completed'] . ' / ' . $threshold . ' missions vers le taux à ' . $reduced . ' %';
        } elseif ($quote['completed'] > 0) {
            $progress = $quote['completed'] . ' mission' . ($quote['completed'] > 1 ? 's' : '') . ' réalisée'
                . ($quote['completed'] > 1 ? 's' : '');
        }

        return array_merge($quote, [
            'percent' => $display,
            'label' => $display . ' % HT',
            'detail' => $detail,
            'progress' => $progress,
            'example' => self::exampleForPercent($quote['ongoing_percent'], $quote['first_free']),
        ]);
    }

    /**
     * @return array{ttc: string, ht: string, fee: string, fee_vat: string, fee_ttc: string, percent: int, first_free: bool}
     */
    public static function exampleForPercent(int $percent, bool $firstFree = false): array
    {
        $ht = self::EXAMPLE_TTC / (1 + self::VAT_RATE);
        $fee = $ht * max(0, $percent) / 100;

        return [
            'ttc' => self::formatMoney((float) self::EXAMPLE_TTC),
            'ht' => self::formatMoney($ht),
            'fee' => self::formatMoney($fee),
            'fee_vat' => self::formatMoney(self::vatOn($fee)),
            'fee_ttc' => self::formatMoney(self::ttcOn($fee)),
            'percent' => $percent,
            'first_free' => $firstFree,
        ];
    }

    public static function vatOn(float $ht): float
    {
        return round($ht * self::VAT_RATE, 2);
    }

    public static function ttcOn(float $ht): float
    {
        return round($ht + self::vatOn($ht), 2);
    }

    public static function formatMoney(float $value): string
    {
        $cents = (int) round($value * 100);
        if ($cents % 100 === 0) {
            return number_format((int) ($cents / 100), 0, ',', ' ') . ' €';
        }
        return number_format($cents / 100, 2, ',', ' ') . ' €';
    }

    public static function amount(int $missionAmount, int $percent): int
    {
        if ($missionAmount <= 0 || $percent <= 0) {
            return 0;
        }
        return (int) round($missionAmount * $percent / 100);
    }

    private static function ongoingPercent(bool $founder, int $completed): int
    {
        if ($founder || self::qualifiesForLoyalty($completed)) {
            return self::founderPercent();
        }
        return self::percent();
    }

    private static function qualifiesForLoyalty(int $completed): bool
    {
        $threshold = self::loyaltyThreshold();
        return $threshold > 0 && $completed >= $threshold;
    }
}
