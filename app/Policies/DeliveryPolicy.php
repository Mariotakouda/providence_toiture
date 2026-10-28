<?php

namespace App\Policies;

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\User;

class DeliveryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('deliveries.view');
    }

    public function view(User $user, Delivery $delivery): bool
    {
        return $user->can('deliveries.view');
    }

    public function transition(User $user, Delivery $delivery, DeliveryStatus $to): bool
    {
        if (! $delivery->status->canTransitionTo($to)) {
            return false;
        }

        return match ($to) {
            DeliveryStatus::InTransit, DeliveryStatus::Delivered => $user->can('deliveries.confirm') || $user->can('deliveries.create'),
            DeliveryStatus::Cancelled => $user->can('deliveries.create'),
            default => false,
        };
    }
}
