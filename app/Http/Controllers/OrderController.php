<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders)
    {
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Order::class);

        $request->validate([
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $term = trim((string) $request->input('q'));

        $orders = Order::query()
            ->with('customer')
            ->when($term !== '', fn ($q) => $q->where(function ($w) use ($term) {
                $w->where('order_number', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('order_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('order_date', '<=', $request->input('to')))
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load([
            'customer', 'quote', 'creator',
            'items' => fn ($q) => $q->withSum([
                'deliveryItems as prepared_quantity' => fn ($d) => $d->whereHas(
                    'delivery',
                    fn ($x) => $x->whereIn('status', ['planned', 'in_transit'])
                ),
            ], 'quantity_delivered'),
            'deliveries' => fn ($q) => $q->orderByDesc('id'),
        ]);

        $stockByProduct = $request->user()->can('stock.view')
            ? Product::withStock()
                ->whereIn('products.id', $order->items->pluck('product_id')->filter())
                ->get()
                ->pluck('stock_quantity', 'id')
            : null;

        $history = AuditLog::query()
            ->with('user')
            ->where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->orderBy('id')
            ->get();

        return view('orders.show', compact('order', 'stockByProduct', 'history'));
    }

    public function transition(Request $request, Order $order): RedirectResponse
    {
        $request->validate(['status' => ['required', Rule::enum(OrderStatus::class)]]);

        $to = OrderStatus::from($request->input('status'));

        Gate::authorize('transition', [$order, $to]);

        $order = $this->orders->transition($order, $to, $request->user());

        $redirect = back()->with('success', "Commande passée à l’état « {$to->label()} ».");

        // Disponibilité : avertissement non bloquant avant confirmation ou préparation.
        if (in_array($to, [OrderStatus::Confirmed, OrderStatus::Preparing], true)) {
            $shortages = $this->orders->shortages($order);

            if ($shortages !== []) {
                $redirect->with('warning', 'Stock insuffisant à ce jour pour : '.implode(' ; ', $shortages).'. La livraison sera bloquée tant que le stock n’est pas reconstitué.');
            }
        }

        return $redirect;
    }
}
