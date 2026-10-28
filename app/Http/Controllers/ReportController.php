<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reports,
        private AuditService $audit,
    ) {
    }

    public function sales(Request $request): View
    {
        $filters = $this->salesFilters($request);

        return view('reports.sales', [
            'filters' => $filters,
            'period' => $filters['period'],
            'from' => $filters['from'],
            'to' => $filters['to'],
            'report' => $this->reports->salesReport($filters),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'commercials' => User::whereIn('id', Order::select('created_by'))->orderBy('name')->get(['id', 'name']),
            'statuses' => ReportService::countedInvoiceStatuses(),
        ]);
    }

    public function financial(Request $request): View
    {
        $request->validate($this->periodRules());

        [$from, $to, $period] = $this->reports->period($request->input('period'), $request->input('from'), $request->input('to'));

        return view('reports.financial', [
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'collected' => $this->reports->collected($from, $to),
            'byMethod' => $this->reports->collectedByMethod($from, $to),
            'cancelled' => $this->reports->cancelledPayments($from, $to),
            'invoiced' => $this->reports->invoicedTotal($from, $to),
            'outstanding' => $this->reports->outstanding(),
            'balances' => $this->reports->customerBalances(10),
            'margin' => $request->user()->can('products.view_cost') ? $this->reports->estimatedMargin($from, $to) : null,
        ]);
    }

    public function exportSales(Request $request): StreamedResponse
    {
        $filters = $this->salesFilters($request);

        $this->audit->log('report.exported', $request->user(), null, [
            'report' => 'sales',
            'from' => $filters['from']->toDateString(),
            'to' => $filters['to']->toDateString(),
        ]);

        $query = $this->reports->salesLines($filters)
            ->selectRaw("
                invoices.invoice_number, invoices.issue_date, invoices.status,
                COALESCE(JSON_UNQUOTE(JSON_EXTRACT(invoices.customer_snapshot, '$.name')), '') as customer,
                COALESCE(sellers.name, '') as commercial,
                COALESCE(categories.name, '') as category,
                invoice_items.sku_snapshot, invoice_items.product_name_snapshot, invoice_items.unit_snapshot,
                invoice_items.quantity, invoice_items.unit_price, invoice_items.discount_percent, invoice_items.line_total
            ")
            ->orderBy('invoices.issue_date')
            ->orderBy('invoice_items.id');

        $headers = ['Facture', 'Date', 'Statut', 'Client', 'Commercial', 'Catégorie', 'Référence', 'Désignation', 'Unité', 'Quantité', 'Prix unitaire', 'Remise %', 'Total ligne HT'];

        return $this->csv('ventes-'.now()->format('Ymd-His').'.csv', $headers, $query, fn ($r) => [
            $r->invoice_number, $r->issue_date, $r->status, $r->customer, $r->commercial, $r->category,
            $r->sku_snapshot, $r->product_name_snapshot, $r->unit_snapshot,
            $r->quantity, $r->unit_price, $r->discount_percent, $r->line_total,
        ]);
    }

    public function exportPayments(Request $request): StreamedResponse
    {
        $request->validate($this->periodRules());

        [$from, $to] = $this->reports->period($request->input('period'), $request->input('from'), $request->input('to'));

        $this->audit->log('report.exported', $request->user(), null, [
            'report' => 'payments',
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);

        $query = Payment::query()
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->join('users as receivers', 'receivers.id', '=', 'payments.received_by')
            ->whereBetween('payments.payment_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("
                payments.payment_number, payments.payment_date, invoices.invoice_number,
                COALESCE(JSON_UNQUOTE(JSON_EXTRACT(invoices.customer_snapshot, '$.name')), '') as customer,
                payments.method, COALESCE(payments.reference, '') as reference, payments.status,
                payments.amount, receivers.name as receiver
            ")
            ->orderBy('payments.payment_date')
            ->orderBy('payments.id')
            ->toBase();

        $headers = ['Reçu', 'Date', 'Facture', 'Client', 'Mode', 'Référence', 'Statut', 'Montant', 'Reçu par'];

        return $this->csv('paiements-'.now()->format('Ymd-His').'.csv', $headers, $query, fn ($r) => [
            $r->payment_number, $r->payment_date, $r->invoice_number, $r->customer,
            PaymentMethod::tryFrom($r->method)?->label() ?? $r->method,
            $r->reference,
            PaymentStatus::tryFrom($r->status)?->label() ?? $r->status,
            $r->amount, $r->receiver,
        ]);
    }

    private function periodRules(): array
    {
        return [
            'period' => ['nullable', Rule::in(array_keys(ReportService::PERIODS))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ];
    }

    private function salesFilters(Request $request): array
    {
        $request->validate($this->periodRules() + [
            'category_id' => ['nullable', 'integer'],
            'commercial_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(ReportService::countedInvoiceStatuses())],
        ]);

        [$from, $to, $period] = $this->reports->period($request->input('period'), $request->input('from'), $request->input('to'));

        return [
            'from' => $from,
            'to' => $to,
            'period' => $period,
            'category_id' => $request->filled('category_id') ? $request->integer('category_id') : null,
            'commercial_id' => $request->filled('commercial_id') ? $request->integer('commercial_id') : null,
            'status' => $request->input('status') ?: null,
        ];
    }

    /** CSV pour Excel français : séparateur « ; », UTF-8 avec BOM, décimales à la virgule. */
    private function csv(string $filename, array $headers, $query, callable $row): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $query, $row) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');

            foreach ($query->cursor() as $record) {
                fputcsv($out, array_map([$this, 'cell'], $row($record)), ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function cell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        // Les nombres passent avec une virgule décimale.
        if (preg_match('/^-?\d+\.\d+$/', $value)) {
            return str_replace('.', ',', $value);
        }

        // Neutralise l'injection de formules dans Excel.
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) && ! is_numeric($value)) {
            return "'".$value;
        }

        return $value;
    }
}
