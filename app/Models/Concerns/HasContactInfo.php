<?php

namespace App\Models\Concerns;

use App\Support\Phone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait HasContactInfo
{
    protected static function bootHasContactInfo(): void
    {
        static::saving(function ($model) {
            $model->phone_normalized = Phone::normalize($model->phone);
        });
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $digits = Phone::digits($term);

        return $query->where(function (Builder $q) use ($term, $digits) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('city', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");

            if (strlen($digits) >= 4) {
                $q->orWhere('phone_normalized', 'like', "%{$digits}%");
            }
        });
    }

    /**
     * Contacts existants avec le même téléphone (normalisé) ou le même e-mail.
     */
    public static function findDuplicates(?string $phone, ?string $email, ?int $ignoreId = null): Collection
    {
        $normalized = Phone::normalize($phone);
        $email = filled($email) ? mb_strtolower(trim($email)) : null;

        if ($normalized === null && $email === null) {
            return collect();
        }

        return static::query()
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->where(function (Builder $q) use ($normalized, $email) {
                if ($normalized !== null) {
                    $q->orWhere('phone_normalized', $normalized);
                }

                if ($email !== null) {
                    $q->orWhereRaw('LOWER(email) = ?', [$email]);
                }
            })
            ->orderBy('name')
            ->limit(5)
            ->get();
    }
}
