<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Validated = 'validated';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Validated => 'Validé',
            self::Cancelled => 'Annulé',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Validated => 'bg-green-100 text-green-700',
            self::Cancelled => 'bg-red-100 text-red-700',
        };
    }
}
