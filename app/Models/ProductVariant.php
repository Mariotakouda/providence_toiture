<?php

namespace App\Models;

use App\Support\Format;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_sku',
        'color',
        'thickness',
        'length',
        'width',
        'sale_price_override',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'thickness' => 'decimal:2',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'sale_price_override' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function label(): string
    {
        return collect([
            $this->color,
            $this->thickness !== null ? Format::quantity($this->thickness) . ' mm' : null,
            $this->length !== null ? Format::quantity($this->length) . ' m' : null,
            $this->width !== null ? 'larg. ' . Format::quantity($this->width) . ' m' : null,
        ])->filter()->implode(' · ');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
