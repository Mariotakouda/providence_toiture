<?php

namespace App\Support;

class WhatsApp
{
    /** Indicatif utilisé quand un numéro local est saisi sans indicatif (Togo). */
    public const DEFAULT_COUNTRY_CODE = '228';

    /**
     * Numéro au format international sans « + » (ex. 22891617055), ou null s'il est inutilisable.
     */
    public static function number(?string $phone): ?string
    {
        $raw = trim((string) $phone);
        $digits = Phone::digits($raw);

        if ($digits === '' || strlen($digits) < 8) {
            return null;
        }

        if (str_starts_with($raw, '+')) {
            return $digits;
        }

        if (str_starts_with($digits, '00')) {
            return substr($digits, 2);
        }

        // Numéro local (8 chiffres au Togo) : on ajoute l'indicatif du pays.
        if (strlen($digits) === 8) {
            return self::DEFAULT_COUNTRY_CODE.$digits;
        }

        return $digits;
    }

    /** Lien wa.me qui ouvre la conversation avec un message déjà rédigé. */
    public static function link(?string $phone, string $message = ''): ?string
    {
        $number = self::number($phone);

        if ($number === null) {
            return null;
        }

        return 'https://wa.me/'.$number.($message !== '' ? '?text='.rawurlencode($message) : '');
    }
}
