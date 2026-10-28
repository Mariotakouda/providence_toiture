<?php

namespace App\Services;

use App\Enums\OnlineRequestStatus;
use App\Models\Customer;
use App\Models\OnlineRequest;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class OnlineRequestService
{
    public function __construct(
        private DocumentNumberService $numbers,
        private QuoteService $quotes,
        private SettingService $settings,
        private AuditService $audit,
    ) {
    }

    /** Enregistre une demande déposée par un visiteur (aucun compte nécessaire). */
    public function submit(array $data, array $lines, ?string $ip): OnlineRequest
    {
        $request = DB::transaction(function () use ($data, $lines, $ip) {
            $request = OnlineRequest::create([
                'reference' => $this->numbers->next('request'),
                'type' => $data['type'],
                'status' => OnlineRequestStatus::New,
                'name' => trim($data['name']),
                'phone' => trim($data['phone']),
                'email' => filled($data['email'] ?? null) ? mb_strtolower(trim($data['email'])) : null,
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
                'message' => filled($data['message'] ?? null) ? trim($data['message']) : null,
                'consent_at' => now(),
                'ip_address' => $ip,
            ]);

            $products = Product::active()
                ->whereIn('id', collect($lines)->pluck('product_id')->map(fn ($id) => (int) $id)->all())
                ->get()
                ->keyBy('id');

            $count = 0;

            foreach ($lines as $line) {
                $product = $products->get((int) ($line['product_id'] ?? 0));
                $quantity = bcadd((string) ($line['quantity'] ?? '0'), '0', 2);

                if (! $product || bccomp($quantity, '0', 2) <= 0) {
                    continue;
                }

                $request->items()->create([
                    'product_id' => $product->id,
                    'product_name_snapshot' => $product->name,
                    'sku_snapshot' => $product->sku,
                    'unit_snapshot' => $product->unit->label(),
                    'quantity' => $quantity,
                ]);

                $count++;
            }

            $this->audit->log('online_request.received', $request, null, [
                'reference' => $request->reference,
                'type' => $request->type->value,
                'items' => $count,
            ]);

            return $request;
        });

        $this->notifyStaff($request);

        return $request;
    }

    public function takeOver(OnlineRequest $onlineRequest, User $by): OnlineRequest
    {
        return DB::transaction(function () use ($onlineRequest, $by) {
            $locked = OnlineRequest::whereKey($onlineRequest->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== OnlineRequestStatus::New) {
                throw ValidationException::withMessages(['status' => 'Cette demande est déjà prise en charge ou traitée.']);
            }

            $locked->forceFill(['status' => OnlineRequestStatus::InProgress, 'assigned_to' => $by->id])->save();

            $this->audit->log('online_request.taken', $locked, ['status' => 'new'], ['status' => 'in_progress', 'assigned_to' => $by->id]);

            return $locked;
        });
    }

    public function close(OnlineRequest $onlineRequest, string $reason, User $by): OnlineRequest
    {
        return DB::transaction(function () use ($onlineRequest, $reason, $by) {
            $locked = OnlineRequest::whereKey($onlineRequest->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->isOpen()) {
                throw ValidationException::withMessages(['status' => 'Cette demande est déjà traitée.']);
            }

            $from = $locked->status;

            $locked->forceFill([
                'status' => OnlineRequestStatus::Closed,
                'closed_reason' => $reason,
                'closed_at' => now(),
                'assigned_to' => $locked->assigned_to ?? $by->id,
            ])->save();

            $this->audit->log('online_request.closed', $locked, ['status' => $from->value], ['status' => 'closed', 'reason' => $reason]);

            return $locked;
        });
    }

    /**
     * Crée (ou réutilise) le client, puis un devis brouillon avec les produits demandés.
     * Les prix viennent du catalogue : aucune remise n'est appliquée automatiquement.
     */
    public function convertToQuote(OnlineRequest $onlineRequest, User $by, ?int $customerId): Quote
    {
        return DB::transaction(function () use ($onlineRequest, $by, $customerId) {
            $locked = OnlineRequest::whereKey($onlineRequest->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->isOpen()) {
                throw ValidationException::withMessages(['status' => 'Cette demande est déjà traitée.']);
            }

            $locked->load('items');

            if ($customerId) {
                $customer = Customer::findOrFail($customerId);
            } else {
                $customer = Customer::create([
                    'customer_type' => 'individual',
                    'name' => $locked->name,
                    'phone' => $locked->phone,
                    'email' => $locked->email,
                    'address' => $locked->address,
                    'city' => $locked->city,
                    'notes' => "Créé depuis la demande en ligne {$locked->reference}.",
                    'is_active' => true,
                ]);

                $this->audit->log('customer.created', $customer, null, [
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'source' => $locked->reference,
                ]);
            }

            $lines = $locked->items
                ->filter(fn ($item) => $item->product_id)
                ->map(fn ($item) => ['product_id' => $item->product_id, 'quantity' => (string) $item->quantity])
                ->values()
                ->all();

            $notes = "Demande en ligne {$locked->reference}";
            if ($locked->message) {
                $notes .= " : {$locked->message}";
            }

            $quote = $this->quotes->create([
                'customer_id' => $customer->id,
                'issue_date' => today()->toDateString(),
                'valid_until' => today()->addDays((int) $this->settings->get('quote_validity_days', '30'))->toDateString(),
                'delivery_fee' => '0',
                'terms' => $this->settings->get('quote_terms'),
                'notes' => $notes,
            ], $lines, $by);

            $locked->forceFill([
                'status' => OnlineRequestStatus::Converted,
                'customer_id' => $customer->id,
                'quote_id' => $quote->id,
                'assigned_to' => $locked->assigned_to ?? $by->id,
            ])->save();

            $this->audit->log('online_request.converted', $locked, ['status' => 'open'], [
                'status' => 'converted',
                'customer_id' => $customer->id,
                'quote_id' => $quote->id,
            ]);

            return $quote;
        });
    }

    /** Prévient l'entreprise par e-mail ; un échec d'envoi ne doit jamais bloquer le visiteur. */
    private function notifyStaff(OnlineRequest $request): void
    {
        $to = $this->settings->get('company_email');

        if (! filled($to)) {
            return;
        }

        try {
            $request->load('items');

            $lines = $request->items
                ->map(fn ($i) => "- {$i->product_name_snapshot} ({$i->sku_snapshot}) : {$i->quantity} {$i->unit_snapshot}")
                ->implode("\n");

            $text = implode("\n", [
                "Nouvelle demande en ligne {$request->reference} ({$request->type->label()})",
                '',
                "Nom : {$request->name}",
                "Téléphone : {$request->phone}",
                'E-mail : '.($request->email ?? '-'),
                'Ville : '.($request->city ?? '-'),
                'Adresse : '.($request->address ?? '-'),
                '',
                "Produits souhaités :\n".($lines !== '' ? $lines : '- (aucun produit sélectionné)'),
                '',
                'Message : '.($request->message ?? '-'),
                '',
                'Voir la demande : '.route('online-requests.show', $request),
            ]);

            Mail::raw($text, fn ($mail) => $mail->to($to)->subject("Nouvelle demande en ligne {$request->reference}"));
        } catch (\Throwable $e) {
            Log::warning('Notification de demande en ligne non envoyée : '.$e->getMessage());
        }
    }
}
