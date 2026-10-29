<?php

namespace App\Models;

use App\Enums\MovementType;
use App\Enums\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'description',
        'image',
        'unit',
        'purchase_price',
        'sale_price',
        'min_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'unit' => Unit::class,
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'min_stock' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('products.is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%' . trim($term) . '%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('products.sku', 'like', $like)
                ->orWhere('products.name', 'like', $like)
                ->orWhereHas('category', fn(Builder $c) => $c->where('name', 'like', $like))
                ->orWhereHas('variants', fn(Builder $v) => $v
                    ->where('variant_sku', 'like', $like)
                    ->orWhere('color', 'like', $like));
        });
    }

    /**
     * Expression SQL du stock théorique (entrées - sorties) et ses paramètres.
     *
     * @return array{0: string, 1: array<int, string>}
     */
    protected static function stockSql(): array
    {
        $incoming = collect(MovementType::cases())
            ->filter(fn(MovementType $type) => $type->isIncoming())
            ->map(fn(MovementType $type) => $type->value)
            ->values()
            ->all();

        $placeholders = implode(',', array_fill(0, count($incoming), '?'));

        $sql = "(SELECT COALESCE(SUM(CASE WHEN movement_type IN ({$placeholders}) THEN quantity ELSE -quantity END), 0) "
            . 'FROM stock_movements WHERE stock_movements.product_id = products.id)';

        return [$sql, $incoming];
    }

    /**
     * Ajoute la colonne calculée stock_quantity.
     * À appeler avant tout autre select / withCount.
     */
    public function scopeWithStock(Builder $query): Builder
    {
        [$sql, $bindings] = self::stockSql();

        return $query->select('products.*')->selectRaw("{$sql} as stock_quantity", $bindings);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        [$sql, $bindings] = self::stockSql();

        return $query
            ->where('products.min_stock', '>', 0)
            ->whereRaw("{$sql} <= products.min_stock", $bindings);
    }

    public function isLowStock(): bool
    {
        return (float) $this->min_stock > 0
            && (float) ($this->stock_quantity ?? 0) <= (float) $this->min_stock;
    }
}
