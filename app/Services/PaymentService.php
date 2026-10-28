<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Support\Format;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private DocumentNumberService $numbers,
        private InvoiceService $invoices,
        private AuditService $audit,
    ) {
    }

    public function record(Invoice $invoice, array $data, User $by): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $by) {
            // Le verrou sur la facture empêche deux paiements simultanés de dépasser le solde.
            $locked = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->acceptsPayments()) {
                throw ValidationException::withMessages([
                    'amount' => "Une facture « {$locked->status->label()} » n’accepte pas de paiement.",
                ]);
            }

            $amount = bcadd((string) $data['amount'], '0', 2);

            if (bccomp($amount, '0', 2) <= 0) {
                throw ValidationException::withMessages(['amount' => 'Le montant doit être supérieur à zéro.']);
            }

            if (bccomp($amount, (string) $locked->balance_due, 2) > 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Le montant dépasse le solde restant dû ('.Format::money($locked->balance_due).').',
                ]);
            }

            $payment = Payment::create([
                'payment_number' => $this->numbers->next('payment'),
                'invoice_id' => $locked->id,
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'method' => $data['method'],
                'reference' => filled($data['reference'] ?? null) ? trim($data['reference']) : null,
                'status' => PaymentStatus::Validated,
                'received_by' => $by->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->invoices->recalculate($locked);

            $this->audit->log('payment.recorded', $payment, null, [
                'payment_number' => $payment->payment_number,
                'invoice_id' => $locked->id,
                'amount' => $amount,
                'method' => $payment->method->value,
            ]);

            return $payment;
        });
    }

    /** Un paiement validé n'est jamais supprimé : il est annulé avec un motif. */
    public function cancel(Payment $payment, string $reason, User $by): Payment
    {
        $invoiceId = Payment::whereKey($payment->id)->value('invoice_id');

        return DB::transaction(function () use ($payment, $reason, $by, $invoiceId) {
            $invoice = Invoice::whereKey($invoiceId)->lockForUpdate()->firstOrFail();
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PaymentStatus::Validated) {
                throw ValidationException::withMessages(['payment' => 'Ce paiement est déjà annulé.']);
            }

            $locked->forceFill([
                'status' => PaymentStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $by->id,
                'cancel_reason' => $reason,
            ])->save();

            $this->invoices->recalculate($invoice);

            $this->audit->log('payment.cancelled', $locked, ['status' => 'validated'], [
                'status' => 'cancelled',
                'amount' => (string) $locked->amount,
                'reason' => $reason,
            ]);

            return $locked;
        });
    }
}
