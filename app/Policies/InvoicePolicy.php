<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.view');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.create') && $invoice->status === InvoiceStatus::Draft;
    }

    public function transition(User $user, Invoice $invoice, InvoiceStatus $to): bool
    {
        return match ($to) {
            InvoiceStatus::Issued => $invoice->status === InvoiceStatus::Draft && $user->can('invoices.create'),
            InvoiceStatus::Cancelled => in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Issued], true)
                && $user->can('invoices.cancel'),
            default => false,
        };
    }

    public function recordPayment(User $user, Invoice $invoice): bool
    {
        return $user->can('payments.record') && $invoice->status->acceptsPayments();
    }
}
