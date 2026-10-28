<?php

namespace App\Models;

use App\Enums\CustomerType;
use App\Models\Concerns\HasContactInfo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory, HasContactInfo;

    protected $fillable = [
        'customer_type', 'name', 'phone', 'email',
        'address', 'city', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'customer_type' => CustomerType::class,
            'is_active' => 'boolean',
        ];
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
