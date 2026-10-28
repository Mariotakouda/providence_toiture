<?php

namespace App\Enums;

use App\Models\User;

enum MovementType: string
{
    case Purchase = 'purchase';
    case Return = 'return';
    case Production = 'production';
    case Sale = 'sale';
    case Breakage = 'breakage';
    case InternalUse = 'internal_use';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';

    public function isIncoming(): bool
    {
        return in_array($this, [self::Purchase, self::Return, self::Production, self::AdjustmentIn]);
    }

    public function sign(): int
    {
        return $this->isIncoming() ? 1 : -1;
    }

    public function isAdjustment(): bool
    {
        return in_array($this, [self::AdjustmentIn, self::AdjustmentOut]);
    }

    public function requiresReason(): bool
    {
        return in_array($this, [self::Breakage, self::InternalUse, self::AdjustmentIn, self::AdjustmentOut]);
    }

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Achat',
            self::Return => 'Retour',
            self::Production => 'Production',
            self::Sale => 'Vente',
            self::Breakage => 'Casse',
            self::InternalUse => 'Usage interne',
            self::AdjustmentIn => 'Ajustement (+)',
            self::AdjustmentOut => 'Ajustement (-)',
        };
    }

    /**
     * Types qu'un utilisateur peut saisir à la main.
     * La vente est réservée au système (livraisons).
     *
     * @return array<int, self>
     */
    public static function allowedFor(User $user): array
    {
        return array_values(array_filter(self::cases(), function (self $type) use ($user) {
            if ($type === self::Sale) {
                return false;
            }

            return $type->isAdjustment()
                ? $user->can('stock.adjust')
                : $user->can('stock.move');
        }));
    }
}
