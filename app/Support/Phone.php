<?php

namespace App\Support;

use Closure;

class Phone
{
    public static function digits(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone);
    }

    /**
     * Forme de comparaison : chiffres seulement, 10 derniers chiffres au maximum,
     * pour rapprocher « 07 01 02 03 04 » de « +225 07 01 02 03 04 ».
     */
    public static function normalize(?string $phone): ?string
    {
        $digits = self::digits($phone);

        if ($digits === '') {
            return null;
        }

        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }

    public static function rule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $digits = self::digits((string) $value);

            if (! preg_match('/^\+?[0-9\s().-]+$/', (string) $value) || strlen($digits) < 8 || strlen($digits) > 15) {
                $fail('Le téléphone doit contenir entre 8 et 15 chiffres (espaces, +, points et tirets autorisés).');
            }
        };
    }
}
