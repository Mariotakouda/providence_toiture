<?php

namespace App\Enums;

enum RoleName: string
{
    case Admin = 'admin';
    case Direction = 'direction';
    case Commercial = 'commercial';
    case Magasinier = 'magasinier';
    case Comptable = 'comptable';
    case Livreur = 'livreur';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Direction => 'Direction',
            self::Commercial => 'Commercial',
            self::Magasinier => 'Magasinier',
            self::Comptable => 'Comptable / caisse',
            self::Livreur => 'Livreur',
        };
    }

    public static function labelFor(?string $name): string
    {
        return self::tryFrom((string) $name)?->label() ?? (string) $name;
    }
}
