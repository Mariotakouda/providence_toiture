<?php

namespace App\Enums;

enum OnlineRequestStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Converted = 'converted';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouvelle',
            self::InProgress => 'En cours de traitement',
            self::Converted => 'Convertie en devis',
            self::Closed => 'Clôturée',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::New => 'bg-yellow-100 text-yellow-800',
            self::InProgress => 'bg-blue-100 text-blue-700',
            self::Converted => 'bg-green-100 text-green-700',
            self::Closed => 'bg-gray-200 text-gray-700',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::InProgress], true);
    }
}
