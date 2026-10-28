<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Planned = 'planned';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planifiée',
            self::InTransit => 'En cours de livraison',
            self::Delivered => 'Livrée',
            self::Cancelled => 'Annulée',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Planned => 'bg-gray-200 text-gray-700',
            self::InTransit => 'bg-blue-100 text-blue-700',
            self::Delivered => 'bg-green-100 text-green-700',
            self::Cancelled => 'bg-red-100 text-red-700',
        };
    }

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Planned => [self::InTransit, self::Delivered, self::Cancelled],
            self::InTransit => [self::Delivered, self::Cancelled],
            default => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function actionLabel(): string
    {
        return match ($this) {
            self::InTransit => 'Marquer en cours de livraison',
            self::Delivered => 'Marquer comme livrée',
            self::Cancelled => 'Annuler le bon',
            default => $this->label(),
        };
    }
}
