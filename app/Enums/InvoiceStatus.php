<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Issued => 'Émise',
            self::PartiallyPaid => 'Partiellement payée',
            self::Paid => 'Payée',
            self::Cancelled => 'Annulée',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-200 text-gray-700',
            self::Issued => 'bg-blue-100 text-blue-700',
            self::PartiallyPaid => 'bg-orange-100 text-orange-700',
            self::Paid => 'bg-green-100 text-green-700',
            self::Cancelled => 'bg-red-100 text-red-700',
        };
    }

    public function acceptsPayments(): bool
    {
        return in_array($this, [self::Issued, self::PartiallyPaid], true);
    }
}
