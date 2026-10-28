<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public const PERIODS = [
        'today' => 'Aujourd’hui',
        'week' => '7 derniers jours',
        'month' => 'Mois en cours',
        'last_month' => 'Mois précédent',
        'year' => 'Année en cours',
        'custom' => 'Période personnalisée',
    ];

    /** @return array<int, string> factures qui comptent dans le chiffre d'affaires */
    public static function countedInvoiceStatuses(): array
    {
        return [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value, InvoiceStatus::Paid->value];
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    public function period(?string $key, ?string $from = null, ?string $to = null): array
    {
        $key = array_key_exists((string) $key, self::PERIODS) ? $key : 'month';
        $today = today();

        return match ($key) {
            'today' => [$today->copy(), $today->copy(), 'today'],
            'week' => [$today->copy()->subDays(6), $today->copy(), 'week'],
            'last_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth(),
                'last_month',
            ],
            'year' => [$today->copy()->startOfYear(), $today->copy(), 'year'],
            'custom' => $this->customPeriod($from, $to),
            default => [$today->copy()->startOfMonth(), $today->copy(), 'month'],
        };
    }

    private function customPeriod(?string $from, ?string $to): array
    {
        try {
            $start = filled($from) ? Carbon::parse($from)->startOfDay() : today()->startOfMonth();
            $end = filled($to) ? Carbon::parse($to)->startOfDay() : today();
        } catch (\Throwable) {
            return [today()->startOfMonth(), today(), 'month'];
        }

        return $start->gt($end) ? [$end, $start, 'custom'] : [$start, $end, 'custom'];
    }

    public function invoicedCount(Carbon $from, Carbon $to): int
    {
        return Invoice::whereIn('status', self::countedInvoiceStatuses())
            ->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])
            ->count();
    }

    public function invoicedTotal(Carbon $from, Carbon $to): string
    {
        return bcadd((string) Invoice::whereIn('status', self::countedInvoiceStatuses())
            ->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])
            ->sum('total'), '0', 2);
    }

    public function collected(Carbon $from, Carbon $to): string
    {
        return bcadd((string) Payment::where('status', PaymentStatus::Validated->value)
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount'), '0', 2);
    }

    public function collectedByMethod(Carbon $from, Carbon $to): Collection
    {
        return Payment::query()
            ->selectRaw('method, COUNT(*) as n, COALESCE(SUM(amount), 0) as total')
            ->where('status', PaymentStatus::Validated->value)
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();
    }

    /** Paiements annulés sur la période : affichés à part pour la transparence. */
    public function cancelledPayments(Carbon $from, Carbon $to): array
    {
        $row = Payment::query()
            ->selectRaw('COUNT(*) as n, COALESCE(SUM(amount), 0) as total')
            ->where('status', PaymentStatus::Cancelled->value)
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->first();

        return ['count' => (int) $row->n, 'total' => bcadd((string) $row->total, '0', 2)];
    }

    /**
     * Marge estimée hors taxes d'après le coût d'achat ACTUEL du catalogue.
     * Les lignes dont le produit n'a pas de coût sont exclues et comptées.
     *
     * @return array{revenue: string, cost: string, margin: string, missing: int}
     */
    public function estimatedMargin(Carbon $from, Carbon $to): array
    {
        $row = DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->leftJoin('products', 'products.id', '=', 'invoice_items.product_id')
            ->whereIn('invoices.status', self::countedInvoiceStatuses())
            ->whereBetween('invoices.issue_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('
                COALESCE(SUM(CASE WHEN products.purchase_price IS NOT NULL THEN invoice_items.line_total END), 0) as revenue,
                COALESCE(SUM(CASE WHEN products.purchase_price IS NOT NULL THEN invoice_items.quantity * products.purchase_price END), 0) as cost,
                COALESCE(SUM(CASE WHEN products.purchase_price IS NULL THEN 1 ELSE 0 END), 0) as missing
            ')
            ->first();

        $revenue = bcadd((string) $row->revenue, '0', 2);
        $cost = bcadd((string) $row->cost, '0', 2);

        return [
            'revenue' => $revenue,
            'cost' => $cost,
            'margin' => bcsub($revenue, $cost, 2),
            'missing' => (int) $row->missing,
        ];
    }

    /** @return array{due: string, count: int, overdue_due: string, overdue_count: int} */
    public function outstanding(): array
    {
        $base = Invoice::whereIn('status', [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value]);

        $all = (clone $base)->selectRaw('COALESCE(SUM(balance_due), 0) as due, COUNT(*) as n')->first();

        $late = (clone $base)
            ->whereDate('due_date', '<', today())
            ->where('balance_due', '>', 0)
            ->selectRaw('COALESCE(SUM(balance_due), 0) as due, COUNT(*) as n')
            ->first();

        return [
            'due' => bcadd((string) $all->due, '0', 2),
            'count' => (int) $all->n,
            'overdue_due' => bcadd((string) $late->due, '0', 2),
            'overdue_count' => (int) $late->n,
        ];
    }

    public function customerBalances(int $limit = 5): Collection
    {
        return DB::table('invoices')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->whereIn('invoices.status', [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value])
            ->where('invoices.balance_due', '>', 0)
            ->selectRaw('customers.id, customers.name, COUNT(invoices.id) as invoices_count, SUM(invoices.balance_due) as due')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('due')
            ->limit($limit)
            ->get();
    }

    /**
     * Lignes de factures filtrées (base des rapports de ventes et de l'export CSV).
     *
     * @param  array{from: Carbon, to: Carbon, category_id: ?int, commercial_id: ?int, status: ?string}  $f
     */
    public function salesLines(array $f): Builder
    {
        $statuses = $f['status'] ? [$f['status']] : self::countedInvoiceStatuses();

        return DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->leftJoin('orders', 'orders.id', '=', 'invoices.order_id')
            ->leftJoin('users as sellers', 'sellers.id', '=', 'orders.created_by')
            ->leftJoin('products', 'products.id', '=', 'invoice_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereIn('invoices.status', $statuses)
            ->whereBetween('invoices.issue_date', [$f['from']->toDateString(), $f['to']->toDateString()])
            ->when($f['category_id'], fn ($q, $id) => $q->where('products.category_id', $id))
            ->when($f['commercial_id'], fn ($q, $id) => $q->where('orders.created_by', $id));
    }

    /** @return array<string, mixed> */
    public function salesReport(array $f): array
    {
        $base = $this->salesLines($f);

        $totals = (clone $base)
            ->selectRaw('COUNT(DISTINCT invoices.id) as invoices_count, COALESCE(SUM(invoice_items.line_total), 0) as lines_total')
            ->first();

        $byCategory = (clone $base)
            ->selectRaw("COALESCE(categories.name, 'Sans catégorie') as label, COALESCE(SUM(invoice_items.line_total), 0) as total")
            ->groupByRaw("COALESCE(categories.name, 'Sans catégorie')")
            ->orderByDesc('total')
            ->get();

        $byCommercial = (clone $base)
            ->selectRaw("COALESCE(sellers.name, 'Non attribué') as label, COALESCE(SUM(invoice_items.line_total), 0) as total")
            ->groupByRaw("COALESCE(sellers.name, 'Non attribué')")
            ->orderByDesc('total')
            ->get();

        $topProducts = (clone $base)
            ->selectRaw('invoice_items.sku_snapshot as sku, invoice_items.product_name_snapshot as name, invoice_items.unit_snapshot as unit, SUM(invoice_items.quantity) as quantity, SUM(invoice_items.line_total) as total')
            ->groupBy('invoice_items.sku_snapshot', 'invoice_items.product_name_snapshot', 'invoice_items.unit_snapshot')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Le total facturé complet n'a de sens que sans filtre par catégorie (frais et taxes ne se répartissent pas).
        $invoiced = null;

        if (! $f['category_id']) {
            $invoiced = bcadd((string) Invoice::query()
                ->whereIn('status', $f['status'] ? [$f['status']] : self::countedInvoiceStatuses())
                ->whereBetween('issue_date', [$f['from']->toDateString(), $f['to']->toDateString()])
                ->when($f['commercial_id'], fn ($q, $id) => $q->whereHas('order', fn ($o) => $o->where('created_by', $id)))
                ->sum('total'), '0', 2);
        }

        return [
            'invoices_count' => (int) $totals->invoices_count,
            'lines_total' => bcadd((string) $totals->lines_total, '0', 2),
            'invoiced' => $invoiced,
            'by_category' => $byCategory,
            'by_commercial' => $byCommercial,
            'top_products' => $topProducts,
        ];
    }
}
