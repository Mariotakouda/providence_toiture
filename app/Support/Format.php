<?php

namespace App\Support;

class Format
{
    // Devise provisoire : à confirmer avec la direction et le comptable.
    public const CURRENCY = 'FCFA';

    public static function money(null|int|float|string $amount): string
    {
        if ($amount === null || $amount === '') {
            return '-';
        }

        $decimals = fmod((float) $amount, 1.0) == 0.0 ? 0 : 2;
        $currency = app(\App\Services\SettingService::class)->get('currency_code', 'FCFA');

        return number_format((float) $amount, $decimals, ',', ' ') . ' ' . $currency;
    }

    public static function quantity(null|int|float|string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return rtrim(rtrim(number_format((float) $value, 2, ',', ' '), '0'), ',');
    }
}
