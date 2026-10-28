<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use App\Support\Format;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private DocumentNumberService $numbers,
        private AuditService $audit,
        private StockService $stock,
    ) {
    }

    /** Conversion d'un devis accepté : les lignes sont copiées, sans ressaisie. */
    public function createFromQuote(Quote $quote, User $by): Order
    {
        return DB::transaction(function () use ($quote, $by) {
            $locked = Quote::whereKey($quote->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== QuoteStatus::Accepted) {
                throw ValidationException::withMessages(['quote' => 'Seul un devis accepté peut être converti en commande.']);
            }

            if (Order::where('quote_id', $locked->id)->exists()) {
                throw ValidationException::withMessages(['quote' => 'Ce devis a déjà été converti en commande.']);
            }

            $locked->load('items');

            $order = Order::create([
                'order_number' => $this->numbers->next('order'),
                'customer_id' => $locked->customer_id,
                'quote_id' => $locked->id,
                'status' => OrderStatus::ToConfirm,
                'order_date' => today(),
                'subtotal' => $locked->subtotal,
                'discount_total' => $locked->discount_total,
                'tax_total' => $locked->tax_total,
                'delivery_fee' => $locked->delivery_fee,
                'total' => $locked->total,
                'created_by' => $by->id,
                'notes' => $locked->notes,
            ]);

            foreach ($locked->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name_snapshot' => $item->product_name_snapshot,
                    'sku_snapshot' => $item->sku_snapshot,
                    'unit_snapshot' => $item->unit_snapshot,
                    'quantity_ordered' => $item->quantity,
                    'quantity_delivered' => 0,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'discount_percent' => $item->discount_percent,
                    'tax_rate' => $item->tax_rate,
                    'line_total' => $item->line_total,
                ]);
            }

            $this->audit->log('order.created', $order, null, [
                'order_number' => $order->order_number,
                'quote_number' => $locked->quote_number,
                'total' => $order->total,
            ]);

            return $order;
        });
    }

    public function transition(Order $order, OrderStatus $to, User $by): Order
    {
        return DB::transaction(function () use ($order, $to, $by) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! $from->canManuallyTransitionTo($to)) {
                throw ValidationException::withMessages([
                    'status' => "Passage impossible de « {$from->label()} » à « {$to->label()} ».",
                ]);
            }

            if ($to === OrderStatus::Cancelled && $locked->deliveries()->where('status', '!=', 'cancelled')->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Impossible d’annuler : des bons de livraison existent. Annulez-les d’abord.',
                ]);
            }

            $locked->forceFill(['status' => $to])->save();

            $this->audit->log('order.status_changed', $locked, ['status' => $from->value], ['status' => $to->value]);

            return $locked;
        });
    }

    /**
     * Passage automatique en « partiellement livrée » ou « livrée »
     * selon les quantités livrées. Appelé par DeliveryService, dans sa transaction.
     */
    public function refreshDeliveryStatus(Order $order, User $by): void
    {
        $items = $order->items()->get();

        $allDone = $items->every(fn ($i) => bccomp((string) $i->quantity_delivered, (string) $i->quantity_ordered, 2) >= 0);
        $any = $items->contains(fn ($i) => bccomp((string) $i->quantity_delivered, '0', 2) > 0);

        $new = $allDone ? OrderStatus::Delivered : ($any ? OrderStatus::PartiallyDelivered : null);

        if ($new === null || $order->status === $new) {
            return;
        }

        $from = $order->status;
        $order->forceFill(['status' => $new])->save();

        $this->audit->log('order.status_changed', $order, ['status' => $from->value], ['status' => $new->value, 'auto' => true]);
    }

    /**
     * Lignes pour lesquelles le stock actuel ne couvre pas le reste à livrer.
     *
     * @return array<int, string>
     */
    public function shortages(Order $order): array
    {
        $order->loadMissing('items');

        $needs = [];

        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            $remaining = bcsub((string) $item->quantity_ordered, (string) $item->quantity_delivered, 2);

            if (bccomp($remaining, '0', 2) <= 0) {
                continue;
            }

            $needs[$item->product_id]['qty'] = bcadd($needs[$item->product_id]['qty'] ?? '0', $remaining, 2);
            $needs[$item->product_id]['name'] = $item->product_name_snapshot;
        }

        $out = [];

        foreach ($needs as $productId => $need) {
            $available = $this->stock->currentStock((int) $productId);

            if (bccomp($available, $need['qty'], 2) < 0) {
                $out[] = sprintf(
                    '%s : disponible %s, nécessaire %s',
                    $need['name'],
                    Format::quantity($available),
                    Format::quantity($need['qty'])
                );
            }
        }

        return $out;
    }
}
