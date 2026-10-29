<?php

namespace App\Support;

use App\Enums\DeliveryStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MovementType;
use App\Enums\OnlineRequestStatus;
use App\Enums\OnlineRequestType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Enums\Unit;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Customer;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Rend le journal d'audit lisible : libellés en français, valeurs formatées, « avant → après ».
 */
class AuditPresenter
{
    public const ACTIONS = [
        'category.created' => 'Catégorie créée',
        'category.updated' => 'Catégorie modifiée',
        'customer.created' => 'Client créé',
        'customer.updated' => 'Client modifié',
        'delivery.created' => 'Livraison créée',
        'delivery.status_changed' => 'Statut de livraison modifié',
        'invoice.cancelled' => 'Facture annulée',
        'invoice.created' => 'Facture créée',
        'invoice.issued' => 'Facture émise',
        'invoice.updated' => 'Facture modifiée',
        'online_request.closed' => 'Demande en ligne clôturée',
        'online_request.converted' => 'Demande convertie en devis',
        'online_request.received' => 'Demande en ligne reçue',
        'online_request.taken' => 'Demande prise en charge',
        'order.created' => 'Commande créée',
        'order.status_changed' => 'Statut de commande modifié',
        'payment.cancelled' => 'Paiement annulé',
        'payment.recorded' => 'Paiement enregistré',
        'product.created' => 'Produit créé',
        'product.updated' => 'Produit modifié',
        'product_variant.created' => 'Variante créée',
        'product_variant.updated' => 'Variante modifiée',
        'quote.created' => 'Devis créé',
        'quote.expired' => 'Devis expiré',
        'quote.status_changed' => 'Statut du devis modifié',
        'quote.updated' => 'Devis modifié',
        'report.exported' => 'Rapport exporté',
        'role.permissions_updated' => 'Permissions d’un rôle modifiées',
        'setting.updated' => 'Paramètres modifiés',
        'stock.adjusted' => 'Stock ajusté',
        'stock.negative_allowed' => 'Stock négatif autorisé',
        'supplier.created' => 'Fournisseur créé',
        'supplier.updated' => 'Fournisseur modifié',
        'user.created' => 'Utilisateur créé',
        'user.updated' => 'Utilisateur modifié',
    ];

    /** Familles d'actions pour le filtre (préfixe => libellé). */
    public const FAMILIES = [
        'quote.' => 'Devis', 'order.' => 'Commandes', 'invoice.' => 'Factures', 'payment.' => 'Paiements',
        'delivery.' => 'Livraisons', 'online_request.' => 'Demandes en ligne', 'product' => 'Produits et variantes',
        'category.' => 'Catégories', 'stock.' => 'Stock', 'customer.' => 'Clients', 'supplier.' => 'Fournisseurs',
        'user.' => 'Utilisateurs', 'role.' => 'Rôles et permissions', 'setting.' => 'Paramètres', 'report.' => 'Rapports',
    ];

    private const SUBJECTS = [
        'OnlineRequest' => 'Demande en ligne', 'Quote' => 'Devis', 'Order' => 'Commande', 'Invoice' => 'Facture',
        'Payment' => 'Paiement', 'Delivery' => 'Livraison', 'Product' => 'Produit', 'ProductVariant' => 'Variante',
        'Category' => 'Catégorie', 'Customer' => 'Client', 'Supplier' => 'Fournisseur', 'User' => 'Utilisateur',
        'Role' => 'Rôle', 'Setting' => 'Paramètre', 'StockMovement' => 'Mouvement de stock',
    ];

    private const FIELDS = [
        'status' => 'Statut', 'reference' => 'Référence', 'type' => 'Type', 'items' => 'Nombre de lignes',
        'order_number' => 'N° de commande', 'quote_number' => 'N° de devis', 'invoice_number' => 'N° de facture',
        'delivery_number' => 'N° de livraison', 'total' => 'Total', 'subtotal' => 'Sous-total', 'name' => 'Nom',
        'sku' => 'Référence (SKU)', 'category_id' => 'Catégorie', 'customer_id' => 'Client', 'unit' => 'Unité',
        'purchase_price' => 'Coût d’achat', 'sale_price' => 'Prix de vente', 'min_stock' => 'Seuil d’alerte',
        'is_active' => 'Actif', 'description' => 'Description', 'phone' => 'Téléphone', 'email' => 'E-mail',
        'address' => 'Adresse', 'city' => 'Ville', 'amount' => 'Montant', 'method' => 'Mode de paiement',
        'paid_at' => 'Payé le', 'due_date' => 'Échéance', 'quantity' => 'Quantité', 'reason' => 'Motif',
        'closed_reason' => 'Motif de clôture', 'role' => 'Rôle', 'roles' => 'Rôles', 'permissions' => 'Permissions',
        'discount' => 'Remise', 'valid_until' => 'Valable jusqu’au', 'quote_number ' => 'N° de devis',
        'company_name' => 'Nom de l’entreprise', 'company_phone' => 'Téléphone', 'company_whatsapp' => 'WhatsApp',
        'company_email' => 'E-mail de l’entreprise', 'company_address' => 'Adresse de l’entreprise',
        'image' => 'Photo', 'company_logo' => 'Logo', 'notes' => 'Notes', 'quote_id' => 'Devis', 'order_id' => 'Commande',
    ];

    private const HIDDEN = ['password', 'remember_token', 'updated_at', 'created_at', 'id'];

    private static array $cache = [];

    public static function actionLabel(string $action): string
    {
        return self::ACTIONS[$action] ?? Str::headline(str_replace(['.', '_'], ' ', $action));
    }

    /** Classes Tailwind du badge d'action selon la nature de l'opération. */
    public static function tone(string $action): string
    {
        return match (true) {
            str_contains($action, 'cancel'), str_contains($action, 'closed'), str_contains($action, 'expired'),
            str_contains($action, 'negative') => 'bg-red-50 text-red-700',
            str_ends_with($action, '.created'), str_ends_with($action, '.recorded'), str_ends_with($action, '.received'),
            str_ends_with($action, '.issued') => 'bg-green-50 text-green-700',
            default => 'bg-soft text-ink',
        };
    }

    public static function subjectType(?string $class): string
    {
        $base = class_basename((string) $class);

        return self::SUBJECTS[$base] ?? Str::headline($base);
    }

    /** « Devis DEV-2026-0001 » — avec la référence lisible quand l'objet existe encore. */
    public static function subject(AuditLog $log): string
    {
        $type = self::subjectType($log->subject_type);
        $subject = $log->subject;

        foreach (['quote_number', 'order_number', 'invoice_number', 'delivery_number', 'reference', 'name', 'sku', 'email'] as $key) {
            if ($subject && filled($subject->getAttribute($key))) {
                return $type.' '.$subject->getAttribute($key);
            }
        }

        return $type.' n° '.$log->subject_id;
    }

    public static function fieldLabel(string $key): string
    {
        return self::FIELDS[$key] ?? Str::ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * Lignes lisibles : [libellé, avant, après]. « avant » vaut null pour une création.
     *
     * @return array<int, array{0: string, 1: ?string, 2: string}>
     */
    public static function changes(AuditLog $log): array
    {
        $old = (array) $log->old_values;
        $new = (array) $log->new_values;
        $rows = [];

        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $key) {
            if (in_array($key, self::HIDDEN, true) || (array_key_exists($key, $old) && array_key_exists($key, $new) && $old[$key] === $new[$key])) {
                continue;
            }

            if (in_array($key, ['image', 'company_logo'], true)) {
                $had = filled($old[$key] ?? null);
                $has = filled($new[$key] ?? null);
                $what = $key === 'company_logo' ? 'Logo' : 'Photo';
                $rows[] = [$what, null, $what.(! $has ? ' supprimé' : ($had ? ' remplacé' : ' ajouté')).($what === 'Photo' ? 'e' : '')];

                continue;
            }

            $rows[] = [
                self::fieldLabel($key),
                $old ? self::format($key, $old[$key] ?? null, $log) : null,
                self::format($key, $new[$key] ?? null, $log),
            ];
        }

        return $rows;
    }

    private static function format(string $key, mixed $value, AuditLog $log): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value) || str_starts_with($key, 'is_')) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Oui' : 'Non';
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE), $value));
        }

        $enum = self::enumFor($key, class_basename((string) $log->subject_type));
        if ($enum !== null && is_string($value) && ($case = $enum::tryFrom($value))) {
            return method_exists($case, 'label') ? $case->label() : Str::headline($value);
        }

        if ($key === 'category_id') {
            return self::$cache['cat'.$value] ??= Category::find($value)?->name ?? '#'.$value;
        }
        if ($key === 'customer_id') {
            return self::$cache['cus'.$value] ??= Customer::find($value)?->name ?? '#'.$value;
        }

        if (is_numeric($value) && preg_match('/(total|amount|price|subtotal|paid|balance|discount|tax|cost)/', $key)) {
            return number_format((float) $value, 0, ',', ' ').' '.app(SettingService::class)->get('currency_code', 'FCFA');
        }

        if (is_string($value) && preg_match('/^-?\d+\.\d+$/', $value)) {
            return rtrim(rtrim(number_format((float) $value, 2, ',', ' '), '0'), ',');
        }

        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2})?/', $value)) {
            try {
                return Carbon::parse($value)->format(strlen($value) > 10 ? 'd/m/Y H:i' : 'd/m/Y');
            } catch (\Throwable) {
            }
        }

        return (string) $value;
    }

    /** @return class-string<\BackedEnum>|null */
    private static function enumFor(string $key, string $subject): ?string
    {
        return match (true) {
            $key === 'status' => match ($subject) {
                'Quote' => QuoteStatus::class, 'Order' => OrderStatus::class, 'Invoice' => InvoiceStatus::class,
                'Delivery' => DeliveryStatus::class, 'OnlineRequest' => OnlineRequestStatus::class,
                'Payment' => PaymentStatus::class, default => null,
            },
            $key === 'type' => match ($subject) {
                'OnlineRequest' => OnlineRequestType::class, 'StockMovement' => MovementType::class, default => null,
            },
            $key === 'unit' => Unit::class,
            $key === 'method' => PaymentMethod::class,
            default => null,
        };
    }
}
