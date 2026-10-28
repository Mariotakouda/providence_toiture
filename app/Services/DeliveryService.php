<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Exceptions\StockException;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Format;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryService
{
    public function __construct(
        private DocumentNumberService $numbers,
        private AuditService $audit,
        private StockService $stock,
        private OrderService $orders,
    ) {
    }

    /**
     * Reste à livrer par ligne = commandé - livré - déjà prévu sur d'autres bons actifs.
     *
     * @return array<int, string> clé = order_item_id
     */
    public function remainingQuantities(Order $order): array
    {
        $order->loadMissing('items');

        $planned = DeliveryItem::query()
            ->selectRaw('order_item_id, COALESCE(SUM(quantity_delivered), 0) as qty')
            ->whereIn('order_item_id', $order->items->pluck('id'))
            ->whereHas('delivery', fn ($q) => $q->whereIn('status', [DeliveryStatus::Planned->value, DeliveryStatus::InTransit->value]))
            ->groupBy('order_item_id')
            ->pluck('qty', 'order_item_id');

        $remaining = [];

        foreach ($order->items as $item) {
            $left = bcsub((string) $item->quantity_ordered, (string) $item->quantity_delivered, 2);
            $left = bcsub($left, bcadd((string) ($planned[$item->id] ?? '0'), '0', 2), 2);

            $remaining[$item->id] = bccomp($left, '0', 2) > 0 ? $left : '0.00';
        }

        return $remaining;
    }

    public function create(Order $order, array $data, array $lines, User $by): Delivery
    {
        return DB::transaction(function () use ($order, $data, $lines, $by) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->acceptsDeliveries()) {
                throw ValidationException::withMessages([
                    'lines' => "Une commande « {$locked->status->label()} » ne peut pas recevoir de livraison.",
                ]);
            }

            $locked->load('items');
            $remaining = $this->remainingQuantities($locked);
            $itemIds = $locked->items->pluck('id')->all();

            $rows = [];

            foreach ($lines as $key => $line) {
                $qty = bcadd((string) ($line['quantity'] ?? '0'), '0', 2);

                if (bccomp($qty, '0', 2) <= 0) {
                    continue;
                }

                $itemId = (int) $line['order_item_id'];

                if (! in_array($itemId, $itemIds, true)) {
                    throw ValidationException::withMessages(["lines.{$key}.quantity" => 'Ligne inconnue pour cette commande.']);
                }

                $total = bcadd($rows[$itemId] ?? '0', $qty, 2);

                if (bccomp($total, $remaining[$itemId], 2) > 0) {
                    throw ValidationException::withMessages([
                        "lines.{$key}.quantity" => sprintf('Quantité supérieure au reste à livrer (%s).', Format::quantity($remaining[$itemId])),
                    ]);
                }

                $rows[$itemId] = $total;
            }

            if ($rows === []) {
                throw ValidationException::withMessages(['lines' => 'Saisissez au moins une quantité à livrer.']);
            }

            $delivery = Delivery::create([
                'delivery_number' => $this->numbers->next('delivery'),
                'order_id' => $locked->id,
                'status' => DeliveryStatus::Planned,
                'planned_at' => $data['planned_at'] ?? null,
                'driver_name' => $data['driver_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $by->id,
            ]);

            foreach ($rows as $itemId => $qty) {
                $delivery->items()->create(['order_item_id' => $itemId, 'quantity_delivered' => $qty]);
            }

            // Le premier bon lance la préparation de la commande.
            if ($locked->status === OrderStatus::Confirmed) {
                $locked->forceFill(['status' => OrderStatus::Preparing])->save();
                $this->audit->log('order.status_changed', $locked, ['status' => 'confirmed'], ['status' => 'preparing', 'auto' => true]);
            }

            $this->audit->log('delivery.created', $delivery, null, [
                'delivery_number' => $delivery->delivery_number,
                'order_id' => $locked->id,
                'lines' => count($rows),
            ]);

            return $delivery;
        });
    }

    /**
     * @throws StockException si le stock ne couvre pas la livraison : tout est annulé.
     */
    public function transition(Delivery $delivery, DeliveryStatus $to, User $by, array $data = []): Delivery
    {
        $orderId = Delivery::whereKey($delivery->id)->value('order_id');

        return DB::transaction(function () use ($delivery, $to, $by, $data, $orderId) {
            // Même ordre de verrouillage que create() : commande d'abord, puis bon.
            $order = Order::whereKey($orderId)->lockForUpdate()->firstOrFail();
            $locked = Delivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw ValidationException::withMessages([
                    'status' => "Passage impossible de « {$from->label()} » à « {$to->label()} ».",
                ]);
            }

            if ($to === DeliveryStatus::Delivered) {
                $this->complete($locked, $order, $by, $data);
            } else {
                $locked->forceFill(['status' => $to])->save();
            }

            $this->audit->log('delivery.status_changed', $locked, ['status' => $from->value], ['status' => $to->value]);

            return $locked;
        });
    }

    /**
     * Sortie de stock unique : un mouvement « vente » par ligne, au passage en « livrée ».
     * Le statut livrée est définitif, donc aucune seconde déduction n'est possible.
     */
    private function complete(Delivery $delivery, Order $order, User $by, array $data): void
    {
        $deliveredAt = filled($data['delivered_at'] ?? null) ? Carbon::parse($data['delivered_at']) : now();

        $delivery->load('items.orderItem');

        foreach ($delivery->items as $item) {
            $orderItem = $item->orderItem;

            if (! $orderItem->product_id) {
                throw new StockException("La ligne « {$orderItem->product_name_snapshot} » n’est plus liée à un produit.");
            }

            $this->stock->record(
                product: Product::findOrFail($orderItem->product_id),
                type: MovementType::Sale,
                quantity: $item->quantity_delivered,
                by: $by,
                reason: "Livraison {$delivery->delivery_number} (commande {$order->order_number})",
                reference: $delivery,
                occurredAt: $deliveredAt,
            );

            $orderItem->forceFill([
                'quantity_delivered' => bcadd((string) $orderItem->quantity_delivered, (string) $item->quantity_delivered, 2),
            ])->save();
        }

        $delivery->forceFill([
            'status' => DeliveryStatus::Delivered,
            'delivered_at' => $deliveredAt,
            'receiver_name' => filled($data['receiver_name'] ?? null) ? trim($data['receiver_name']) : null,
        ])->save();

        $this->orders->refreshDeliveryStatus($order, $by);
    }
}
