<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Exceptions\StockException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\Format;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function __construct(private AuditService $audit) {}

    /**
     * Stock théorique = entrées - sorties, en chaîne décimale (2 décimales).
     */
    public function currentStock(Product|int $product): string
    {
        $id = $product instanceof Product ? $product->id : $product;
        $incoming = collect(MovementType::cases())
            ->filter(fn(MovementType $type) => $type->isIncoming())
            ->map(fn(MovementType $type) => $type->value)
            ->all();

        $in = StockMovement::where('product_id', $id)->whereIn('movement_type', $incoming)->sum('quantity');
        $out = StockMovement::where('product_id', $id)->whereNotIn('movement_type', $incoming)->sum('quantity');

        return bcsub(bcadd((string) $in, '0', 2), bcadd((string) $out, '0', 2), 2);
    }

    /**
     * Enregistre un mouvement dans une transaction, avec verrou sur le produit.
     *
     * @throws StockException
     */
    public function record(
        Product $product,
        MovementType $type,
        string|int|float $quantity,
        User $by,
        ?string $reason = null,
        ?Model $reference = null,
        ?string $unitCost = null,
        ?CarbonInterface $occurredAt = null,
        bool $allowNegative = false,
    ): StockMovement {
        $quantity = bcadd((string) $quantity, '0', 2);

        if (bccomp($quantity, '0', 2) <= 0) {
            throw new StockException('La quantité doit être supérieure à zéro.');
        }

        return DB::transaction(function () use ($product, $type, $quantity, $by, $reason, $reference, $unitCost, $occurredAt, $allowNegative) {
            // Le verrou sérialise les mouvements concurrents sur un même produit.
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

            if (! $locked->is_active && $type !== MovementType::Sale) {
                throw new StockException("Le produit « {$locked->name} » est archivé.");
            }

            $goesNegative = false;

            if (! $type->isIncoming()) {
                $available = $this->currentStock($locked);
                $goesNegative = bccomp($available, $quantity, 2) < 0;

                if ($goesNegative && ! $allowNegative) {
                    throw new StockException(sprintf(
                        'Stock insuffisant pour « %s » : disponible %s, demandé %s.',
                        $locked->name,
                        Format::quantity($available),
                        Format::quantity($quantity)
                    ));
                }
            }

            $movement = StockMovement::create([
                'product_id' => $locked->id,
                'movement_type' => $type,
                'quantity' => $quantity,
                'unit_cost' => $type->isIncoming() ? $unitCost : null,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'reason' => $reason,
                'created_by' => $by->id,
                'occurred_at' => $occurredAt ?? now(),
            ]);

            if ($type->isAdjustment()) {
                $this->audit->log('stock.adjusted', $movement, null, [
                    'product_id' => $locked->id,
                    'type' => $type->value,
                    'quantity' => $quantity,
                    'reason' => $reason,
                ]);
            }

            if ($goesNegative) {
                $this->audit->log('stock.negative_allowed', $movement, null, [
                    'product_id' => $locked->id,
                    'type' => $type->value,
                    'quantity' => $quantity,
                    'reason' => $reason,
                ]);
            }

            return $movement;
        });
    }

    /**
     * Applique un inventaire : un ajustement par écart constaté, le tout dans une transaction.
     * Si le stock a bougé depuis l'ouverture de la fiche, tout est annulé.
     *
     * @param  array<int, array{counted: string, expected: string}>  $lines  clé = product_id
     * @return array<int, StockMovement>
     *
     * @throws StockException
     */
    public function applyInventory(array $lines, User $by, string $reason): array
    {
        return DB::transaction(function () use ($lines, $by, $reason) {
            $products = Product::whereIn('id', array_keys($lines))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $created = [];

            foreach ($lines as $productId => $line) {
                $product = $products->get($productId);

                if (! $product || ! $product->is_active) {
                    throw new StockException('Un produit de la fiche est introuvable ou archivé.');
                }

                $current = $this->currentStock($product);
                $expected = bcadd($line['expected'], '0', 2);

                if (bccomp($current, $expected, 2) !== 0) {
                    throw new StockException(sprintf(
                        'Le stock de « %s » a changé depuis l’ouverture de la fiche (affiché : %s, actuel : %s). Rechargez la page et recommencez.',
                        $product->name,
                        Format::quantity($expected),
                        Format::quantity($current)
                    ));
                }

                $difference = bcsub(bcadd($line['counted'], '0', 2), $current, 2);

                if (bccomp($difference, '0', 2) === 0) {
                    continue;
                }

                $type = bccomp($difference, '0', 2) > 0
                    ? MovementType::AdjustmentIn
                    : MovementType::AdjustmentOut;

                $created[] = $this->record($product, $type, ltrim($difference, '-'), $by, $reason);
            }

            return $created;
        });
    }
}
