<?php

namespace App\Models;

use App\Enums\OnlineRequestStatus;
use App\Enums\OnlineRequestType;
use App\Models\Concerns\HasContactInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineRequest extends Model
{
    use HasContactInfo;

    protected $fillable = [
        'reference', 'type', 'status', 'name', 'phone', 'email', 'city', 'address',
        'message', 'consent_at', 'ip_address', 'assigned_to', 'customer_id', 'quote_id',
        'closed_reason', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => OnlineRequestType::class,
            'status' => OnlineRequestStatus::class,
            'consent_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OnlineRequestItem::class)->orderBy('id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
