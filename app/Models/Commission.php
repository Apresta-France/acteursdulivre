<?php

declare(strict_types=1);

namespace Adl\Models;

final class Commission
{
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

    public static function percentForSeller(int $sellerId): int
    {
        return User::isFounder($sellerId) ? self::founderPercent() : self::percent();
    }

    public static function dueDays(): int
    {
        $value = (int) (Setting::get('invoice_due_days', '15') ?: '15');
        return max(1, $value);
    }

    /** @return array{percent: int, first_free: bool, completed: int, founder: bool} */
    public static function quoteForSeller(int $sellerId): array
    {
        $completed = Order::completedCountForSeller($sellerId);
        $founder = User::isFounder($sellerId);
        if ($completed < 1) {
            return ['percent' => 0, 'first_free' => true, 'completed' => $completed, 'founder' => $founder];
        }

        return [
            'percent' => $founder ? self::founderPercent() : self::percent(),
            'first_free' => false,
            'completed' => $completed,
            'founder' => $founder,
        ];
    }

    public static function amount(int $missionAmount, int $percent): int
    {
        if ($missionAmount <= 0 || $percent <= 0) {
            return 0;
        }
        return (int) round($missionAmount * $percent / 100);
    }
}
