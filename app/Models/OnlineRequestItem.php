<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineRequestItem extends Model
{
    protected $fillable = [
        'online_request_id', 'product_id', 'product_name_snapshot',
        'sku_snapshot', 'unit_snapshot', 'quantity',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2'];
    }

    public function onlineRequest(): BelongsTo
    {
        return $this->belongsTo(OnlineRequest::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
