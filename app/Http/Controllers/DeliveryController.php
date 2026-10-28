<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryStatus;
use App\Exceptions\StockException;
use App\Http\Requests\StoreDeliveryRequest;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use App\Services\DeliveryService;
use App\Services\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function __construct(
        private DeliveryService $deliveries,
        private SettingService $settings,
    ) {
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Delivery::class);

        $request->validate(['status' => ['nullable', Rule::enum(DeliveryStatus::class)]]);

        $term = trim((string) $request->input('q'));

        $deliveries = Delivery::query()
            ->with('order.customer')
            ->when($term !== '', fn ($q) => $q->where(function ($w) use ($term) {
                $w->where('delivery_number', 'like', "%{$term}%")
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%")));
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('deliveries.index', [
            'deliveries' => $deliveries,
            'statuses' => DeliveryStatus::cases(),
        ]);
    }

    public function create(Order $order): View
    {
        Gate::authorize('createDelivery', $order);

        $order->load(['customer', 'items']);

        return view('deliveries.create', [
            'order' => $order,
            'remaining' => $this->deliveries->remainingQuantities($order),
            'drivers' => User::role('livreur')->where('is_active', true)->orderBy('name')->pluck('name'),
        ]);
    }

    public function store(StoreDeliveryRequest $request, Order $order): RedirectResponse
    {
        $delivery = $this->deliveries->create($order, $request->validated(), $request->validated('lines'), $request->user());

        return redirect()->route('deliveries.show', $delivery)
            ->with('success', "Bon de livraison {$delivery->delivery_number} créé.");
    }

    public function show(Delivery $delivery): View
    {
        Gate::authorize('view', $delivery);

        $delivery->load(['order.customer', 'items.orderItem', 'creator']);

        return view('deliveries.show', compact('delivery'));
    }

    public function transition(Request $request, Delivery $delivery): RedirectResponse
    {
        $request->validate([
            'status' => ['required', Rule::enum(DeliveryStatus::class)],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'delivered_at' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $to = DeliveryStatus::from($request->input('status'));

        Gate::authorize('transition', [$delivery, $to]);

        try {
            $this->deliveries->transition($delivery, $to, $request->user(), $request->only(['receiver_name', 'delivered_at']));
        } catch (StockException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', "Bon de livraison passé à l’état « {$to->label()} ».");
    }

    public function pdf(Delivery $delivery)
    {
        Gate::authorize('view', $delivery);

        $delivery->load(['order.customer', 'items.orderItem', 'creator']);

        return Pdf::loadView('pdf.delivery-note', [
            'delivery' => $delivery,
            'company' => $this->settings->all(),
            'logo' => $this->settings->logoDataUriOrDefault(),
        ])->setPaper('a4')->stream($delivery->delivery_number.'.pdf');
    }
}
