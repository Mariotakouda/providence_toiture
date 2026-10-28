<?php

namespace App\Enums;

enum OrderStatus: string
{
    case ToConfirm = 'to_confirm';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case PartiallyDelivered = 'partially_delivered';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ToConfirm => 'À confirmer',
            self::Confirmed => 'Confirmée',
            self::Preparing => 'En préparation',
            self::PartiallyDelivered => 'Partiellement livrée',
            self::Delivered => 'Livrée',
            self::Cancelled => 'Annulée',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ToConfirm => 'bg-gray-200 text-gray-700',
            self::Confirmed => 'bg-blue-100 text-blue-700',
            self::Preparing => 'bg-indigo-100 text-indigo-700',
            self::PartiallyDelivered => 'bg-orange-100 text-orange-700',
            self::Delivered => 'bg-green-100 text-green-700',
            self::Cancelled => 'bg-red-100 text-red-700',
        };
    }

    /** Transitions décidées par un utilisateur (les passages en livraison sont automatiques). */
    public function manualTransitions(): array
    {
        return match ($this) {
            self::ToConfirm => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Preparing, self::Cancelled],
            self::Preparing => [self::Cancelled],
            default => [],
        };
    }

    public function canManuallyTransitionTo(self $to): bool
    {
        return in_array($to, $this->manualTransitions(), true);
    }

    public function acceptsDeliveries(): bool
    {
        return in_array($this, [self::Confirmed, self::Preparing, self::PartiallyDelivered], true);
    }

    public function actionLabel(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmer la commande',
            self::Preparing => 'Passer en préparation',
            self::Cancelled => 'Annuler la commande',
            default => $this->label(),
        };
    }
}
