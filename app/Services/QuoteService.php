<?php

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteService
{
    public function __construct(
        private DocumentNumberService $numbers,
        private SettingService $settings,
        private AuditService $audit,
    ) {
    }

    public function create(array $data, array $lines, User $user): Quote
    {
        return DB::transaction(function () use ($data, $lines, $user) {
            $computed = $this->compute($lines, $user, (string) ($data['delivery_fee'] ?? '0'));

            $quote = Quote::create([
                'quote_number' => $this->numbers->next('quote'),
                'customer_id' => $data['customer_id'],
                'created_by' => $user->id,
                'issue_date' => $data['issue_date'],
                'valid_until' => $data['valid_until'],
                'status' => QuoteStatus::Draft,
                ...$computed['totals'],
                'terms' => $data['terms'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $quote->items()->createMany($computed['lines']);

            $this->audit->log('quote.created', $quote, null, [
                'quote_number' => $quote->quote_number,
                'customer_id' => $quote->customer_id,
                'total' => $quote->total,
                'discount_total' => $quote->discount_total,
            ]);

            return $quote;
        });
    }

    public function update(Quote $quote, array $data, array $lines, User $user): Quote
    {
        return DB::transaction(function () use ($quote, $data, $lines, $user) {
            $locked = Quote::whereKey($quote->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->isEditable()) {
                throw ValidationException::withMessages(['status' => 'Seul un devis en brouillon peut être modifié.']);
            }

            $locked->load('items');

            $old = [
                'customer_id' => $locked->customer_id,
                'valid_until' => $locked->valid_until->toDateString(),
                'total' => $locked->total,
                'discount_total' => $locked->discount_total,
                'lines' => $locked->items->count(),
            ];

            $computed = $this->compute($lines, $user, (string) ($data['delivery_fee'] ?? '0'), $locked);

            $locked->update([
                'customer_id' => $data['customer_id'],
                'issue_date' => $data['issue_date'],
                'valid_until' => $data['valid_until'],
                ...$computed['totals'],
                'terms' => $data['terms'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $locked->items()->delete();
            $locked->items()->createMany($computed['lines']);

            $this->audit->log('quote.updated', $locked, $old, [
                'customer_id' => $locked->customer_id,
                'valid_until' => $locked->valid_until->toDateString(),
                'total' => $locked->total,
                'discount_total' => $locked->discount_total,
                'lines' => count($computed['lines']),
            ]);

            return $locked;
        });
    }

    public function transition(Quote $quote, QuoteStatus $to, User $by): Quote
    {
        return DB::transaction(function () use ($quote, $to, $by) {
            $locked = Quote::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw ValidationException::withMessages([
                    'status' => "Passage impossible de « {$from->label()} » à « {$to->label()} ».",
                ]);
            }

            if ($to === QuoteStatus::Sent) {
                if ($locked->items()->count() === 0) {
                    throw ValidationException::withMessages(['status' => 'Un devis sans ligne ne peut pas être envoyé.']);
                }

                if ($locked->valid_until->lt(today())) {
                    throw ValidationException::withMessages(['status' => 'La date de validité est dépassée : modifiez le devis avant de l’envoyer.']);
                }
            }

            $dates = match ($to) {
                QuoteStatus::Sent => ['sent_at' => now(), 'responded_at' => null],
                QuoteStatus::Accepted, QuoteStatus::Rejected => ['responded_at' => now()],
                QuoteStatus::Draft => ['sent_at' => null, 'responded_at' => null],
                default => [],
            };

            $locked->forceFill(['status' => $to, ...$dates])->save();

            $this->audit->log('quote.status_changed', $locked, ['status' => $from->value], ['status' => $to->value]);

            return $locked;
        });
    }

    /** Passe en « expiré » les devis envoyés dont la validité est dépassée. */
    public function expireOverdue(bool $system = false): int
    {
        $quotes = Quote::query()
            ->where('status', QuoteStatus::Sent->value)
            ->whereDate('valid_until', '<', today())
            ->get();

        foreach ($quotes as $quote) {
            $quote->forceFill(['status' => QuoteStatus::Expired])->save();
            $this->audit->log('quote.expired', $quote, ['status' => 'sent'], ['status' => 'expired'], $system);
        }

        return $quotes->count();
    }

    /**
     * Calcul entièrement côté serveur : prix et remises du formulaire ne sont repris
     * que si l'utilisateur a la permission quotes.discount.
     *
     * @return array{lines: array<int, array>, totals: array<string, string>}
     */
    private function compute(array $lines, User $user, string $deliveryFee, ?Quote $quote = null): array
    {
        $canOverride = $user->can('quotes.discount');
        $taxRate = $this->settings->bool('tax_enabled')
            ? bcadd((string) $this->settings->get('tax_rate', '0'), '0', 2)
            : '0.00';

        $existing = $quote ? $quote->items->keyBy('id') : collect();

        $products = Product::with('variants')
            ->whereIn('id', collect($lines)->pluck('product_id')->map(fn ($id) => (int) $id)->all())
            ->get()
            ->keyBy('id');

        $out = [];
        $subtotal = $discountTotal = $taxTotal = '0.00';

        foreach ($lines as $key => $line) {
            $product = $products->get((int) $line['product_id']);

            if (! $product || ! $product->is_active) {
                throw ValidationException::withMessages(["lines.{$key}.product_id" => 'Produit introuvable ou archivé.']);
            }

            $variant = null;

            if (filled($line['product_variant_id'] ?? null)) {
                $variant = $product->variants->firstWhere('id', (int) $line['product_variant_id']);

                if (! $variant || ! $variant->is_active) {
                    throw ValidationException::withMessages(["lines.{$key}.product_variant_id" => 'Variante invalide pour ce produit.']);
                }
            }

            $catalogPrice = (string) ($variant?->sale_price_override ?? $product->sale_price);

            $previous = filled($line['id'] ?? null) ? $existing->get((int) $line['id']) : null;
            $unchanged = $previous
                && (int) $previous->product_id === $product->id
                && (int) ($previous->product_variant_id ?? 0) === (int) ($variant?->id ?? 0);

            if ($canOverride) {
                $unitPrice = filled($line['unit_price'] ?? null) ? (string) $line['unit_price'] : $catalogPrice;
                $discountPercent = filled($line['discount_percent'] ?? null) ? (string) $line['discount_percent'] : '0';
            } elseif ($unchanged) {
                $unitPrice = (string) $previous->unit_price;
                $discountPercent = (string) $previous->discount_percent;
            } else {
                $unitPrice = $catalogPrice;
                $discountPercent = '0';
            }

            $quantity = bcadd((string) $line['quantity'], '0', 2);
            $unitPrice = bcadd($unitPrice, '0', 2);
            $discountPercent = bcadd($discountPercent, '0', 2);

            $gross = $this->round(bcmul($quantity, $unitPrice, 4));
            $discount = $this->round(bcdiv(bcmul($gross, $discountPercent, 4), '100', 4));
            $net = bcsub($gross, $discount, 2);
            $tax = $this->round(bcdiv(bcmul($net, $taxRate, 4), '100', 4));

            $subtotal = bcadd($subtotal, $gross, 2);
            $discountTotal = bcadd($discountTotal, $discount, 2);
            $taxTotal = bcadd($taxTotal, $tax, 2);

            $name = $product->name;
            if ($variant) {
                $name .= ' - '.($variant->label() ?: $variant->variant_sku);
            }

            $out[] = [
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'product_name_snapshot' => $name,
                'sku_snapshot' => $variant?->variant_sku ?? $product->sku,
                'unit_snapshot' => $product->unit->label(),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'discount_percent' => $discountPercent,
                'tax_rate' => $taxRate,
                'line_total' => $net,
            ];
        }

        $deliveryFee = bcadd(filled($deliveryFee) ? $deliveryFee : '0', '0', 2);
        $total = bcadd(bcadd(bcsub($subtotal, $discountTotal, 2), $taxTotal, 2), $deliveryFee, 2);

        return [
            'lines' => $out,
            'totals' => [
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
            ],
        ];
    }

    /** Arrondi au plus proche à 2 décimales (valeurs positives). */
    private function round(string $value): string
    {
        return bcadd($value, '0.005', 2);
    }
}
