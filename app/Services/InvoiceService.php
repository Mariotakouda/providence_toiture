<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        private DocumentNumberService $numbers,
        private SettingService $settings,
        private AuditService $audit,
    ) {
    }

    /** @return array<int, OrderStatus> */
    public static function invoiceableStatuses(): array
    {
        return [OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::PartiallyDelivered, OrderStatus::Delivered];
    }

    public function createFromOrder(Order $order, array $data, User $by): Invoice
    {
        return DB::transaction(function () use ($order, $data, $by) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, self::invoiceableStatuses(), true)) {
                throw ValidationException::withMessages([
                    'order' => "Une commande « {$locked->status->label()} » ne peut pas être facturée.",
                ]);
            }

            if ($locked->invoices()->where('status', '!=', InvoiceStatus::Cancelled->value)->exists()) {
                throw ValidationException::withMessages(['order' => 'Cette commande a déjà une facture non annulée.']);
            }

            $locked->load(['items', 'customer']);
            $customer = $locked->customer;

            $invoice = Invoice::create([
                'invoice_number' => $this->numbers->next('invoice'),
                'order_id' => $locked->id,
                'customer_id' => $customer->id,
                'customer_snapshot' => [
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'address' => $customer->address,
                    'city' => $customer->city,
                ],
                'issue_date' => today(),
                'due_date' => $data['due_date'],
                'status' => InvoiceStatus::Draft,
                'subtotal' => $locked->subtotal,
                'discount_total' => $locked->discount_total,
                'tax_total' => $locked->tax_total,
                'delivery_fee' => $locked->delivery_fee,
                'total' => $locked->total,
                'amount_paid' => 0,
                'balance_due' => $locked->total,
                'terms' => $data['terms'] ?? $this->settings->get('invoice_terms'),
                'notes' => $data['notes'] ?? null,
                'created_by' => $by->id,
            ]);

            foreach ($locked->items as $item) {
                $invoice->items()->create([
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name_snapshot' => $item->product_name_snapshot,
                    'sku_snapshot' => $item->sku_snapshot,
                    'unit_snapshot' => $item->unit_snapshot,
                    'quantity' => $item->quantity_ordered,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'discount_percent' => $item->discount_percent,
                    'tax_rate' => $item->tax_rate,
                    'line_total' => $item->line_total,
                ]);
            }

            $this->audit->log('invoice.created', $invoice, null, [
                'invoice_number' => $invoice->invoice_number,
                'order_id' => $locked->id,
                'total' => $invoice->total,
            ]);

            return $invoice;
        });
    }

    /** Seuls l'échéance, les conditions et les notes d'un brouillon sont modifiables. */
    public function update(Invoice $invoice, array $data, User $by): Invoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $locked = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== InvoiceStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Seule une facture en brouillon peut être modifiée.']);
            }

            $locked->fill([
                'due_date' => $data['due_date'],
                'terms' => $data['terms'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $changes = $locked->getDirty();

            if ($changes) {
                $old = array_intersect_key($locked->getOriginal(), $changes);
                $locked->save();
                $this->audit->log('invoice.updated', $locked, $old, $changes);
            }

            return $locked;
        });
    }

    public function issue(Invoice $invoice, User $by): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $locked = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== InvoiceStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Seule une facture en brouillon peut être émise.']);
            }

            if ($locked->items()->count() === 0) {
                throw ValidationException::withMessages(['status' => 'Une facture sans ligne ne peut pas être émise.']);
            }

            if ($locked->due_date->lt(today())) {
                throw ValidationException::withMessages(['status' => 'La date d’échéance est dépassée : modifiez la facture avant de l’émettre.']);
            }

            $locked->forceFill([
                'status' => InvoiceStatus::Issued,
                'issue_date' => today(),
                'issued_at' => now(),
            ])->save();

            $this->audit->log('invoice.issued', $locked, ['status' => 'draft'], ['status' => 'issued']);

            return $locked;
        });
    }

    public function cancel(Invoice $invoice, string $reason, User $by): Invoice
    {
        return DB::transaction(function () use ($invoice, $reason, $by) {
            $locked = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! in_array($from, [InvoiceStatus::Draft, InvoiceStatus::Issued], true)) {
                throw ValidationException::withMessages([
                    'status' => "Une facture « {$from->label()} » ne peut pas être annulée : annulez d’abord ses paiements.",
                ]);
            }

            if (bccomp((string) $locked->amount_paid, '0', 2) > 0) {
                throw ValidationException::withMessages(['status' => 'Des paiements validés existent : annulez-les d’abord.']);
            }

            $locked->forceFill([
                'status' => InvoiceStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $by->id,
                'cancel_reason' => $reason,
            ])->save();

            $this->audit->log('invoice.cancelled', $locked, ['status' => $from->value], [
                'status' => 'cancelled',
                'reason' => $reason,
            ]);

            return $locked;
        });
    }

    /**
     * Recalcule montant payé, solde et statut à partir des paiements validés.
     * À appeler dans une transaction, facture verrouillée.
     */
    public function recalculate(Invoice $invoice): Invoice
    {
        $paid = bcadd((string) $invoice->payments()->where('status', PaymentStatus::Validated->value)->sum('amount'), '0', 2);
        $balance = bcsub((string) $invoice->total, $paid, 2);

        $status = $invoice->status;

        if (in_array($status, [InvoiceStatus::Issued, InvoiceStatus::PartiallyPaid, InvoiceStatus::Paid], true)) {
            $status = bccomp($balance, '0', 2) <= 0
                ? InvoiceStatus::Paid
                : (bccomp($paid, '0', 2) > 0 ? InvoiceStatus::PartiallyPaid : InvoiceStatus::Issued);
        }

        $invoice->forceFill([
            'amount_paid' => $paid,
            'balance_due' => bccomp($balance, '0', 2) < 0 ? '0.00' : $balance,
            'status' => $status,
        ])->save();

        return $invoice;
    }
}
