<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\InvoiceService;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('orders.view');
    }

    public function transition(User $user, Order $order, OrderStatus $to): bool
    {
        if (! $order->status->canManuallyTransitionTo($to)) {
            return false;
        }

        return match ($to) {
            OrderStatus::Confirmed => $user->can('orders.update'),
            OrderStatus::Preparing => $user->can('orders.prepare'),
            OrderStatus::Cancelled => $user->can('orders.cancel'),
            default => false,
        };
    }

    public function createDelivery(User $user, Order $order): bool
    {
        return $user->can('deliveries.create') && $order->status->acceptsDeliveries();
    }

    public function createInvoice(User $user, Order $order): bool
    {
        return $user->can('invoices.create')
            && in_array($order->status, InvoiceService::invoiceableStatuses(), true)
            && ! $order->invoices()->where('status', '!=', 'cancelled')->exists();
    }
}
