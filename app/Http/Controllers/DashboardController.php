<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\StockMovement;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private ReportService $reports)
    {
    }

    public function index(Request $request): View
    {
        $request->validate([
            'period' => ['nullable', Rule::in(array_keys(ReportService::PERIODS))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $user = $request->user();

        [$from, $to, $period] = $this->reports->period($request->input('period'), $request->input('from'), $request->input('to'));

        $sales = $user->can('reports.sales')
            ? ['invoiced' => $this->reports->invoicedTotal($from, $to), 'count' => $this->reports->invoicedCount($from, $to)]
            : null;

        $finance = $user->can('reports.financial')
            ? [
                'collected' => $this->reports->collected($from, $to),
                'margin' => $user->can('products.view_cost') ? $this->reports->estimatedMargin($from, $to) : null,
            ]
            : null;

        $quotes = $user->can('quotes.view')
            ? [
                'sent_count' => Quote::where('status', 'sent')->count(),
                'sent_total' => bcadd((string) Quote::where('status', 'sent')->sum('total'), '0', 2),
                'draft_count' => Quote::where('status', 'draft')->count(),
            ]
            : null;

        $orders = $user->can('orders.view')
            ? Order::query()
                ->whereIn('status', [
                    OrderStatus::ToConfirm->value, OrderStatus::Confirmed->value,
                    OrderStatus::Preparing->value, OrderStatus::PartiallyDelivered->value,
                ])
                ->selectRaw('status, COUNT(*) as n')
                ->groupBy('status')
                ->pluck('n', 'status')
            : null;

        $deliveries = $user->can('deliveries.view')
            ? [
                'count' => Delivery::whereIn('status', ['planned', 'in_transit'])->count(),
                'next' => Delivery::with('order.customer')
                    ->whereIn('status', ['planned', 'in_transit'])
                    ->orderByRaw('COALESCE(planned_at, created_at)')
                    ->limit(6)
                    ->get(),
            ]
            : null;

        $lowStock = $user->can('stock.view')
            ? [
                'count' => Product::active()->lowStock()->count(),
                'items' => Product::active()->withStock()->lowStock()->orderBy('products.name')->limit(8)->get(),
            ]
            : null;

        $movements = $user->can('stock.view')
            ? StockMovement::with(['product', 'creator'])->orderByDesc('occurred_at')->orderByDesc('id')->limit(8)->get()
            : null;

        $receivables = $user->can('invoices.view')
            ? ['outstanding' => $this->reports->outstanding(), 'balances' => $this->reports->customerBalances(5)]
            : null;

        return view('dashboard', compact(
            'period', 'from', 'to', 'sales', 'finance', 'quotes', 'orders',
            'deliveries', 'lowStock', 'movements', 'receivables'
        ));
    }
}
