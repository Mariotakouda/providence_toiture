<?php

namespace App\Enums;

enum Unit: string
{
    case Piece = 'piece';
    case Sheet = 'sheet';
    case Meter = 'm';
    case SquareMeter = 'm2';
    case Kilogram = 'kg';
    case Liter = 'l';
    case Roll = 'roll';
    case Bucket = 'bucket';
    case Pack = 'pack';

    public function label(): string
    {
        return match ($this) {
            self::Piece => 'Pièce',
            self::Sheet => 'Feuille',
            self::Meter => 'Mètre linéaire',
            self::SquareMeter => 'Mètre carré (m²)',
            self::Kilogram => 'Kilogramme',
            self::Liter => 'Litre',
            self::Roll => 'Rouleau',
            self::Bucket => 'Seau',
            self::Pack => 'Paquet',
        };
    }
}
