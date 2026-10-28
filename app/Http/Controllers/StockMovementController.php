<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Exceptions\StockException;
use App\Http\Requests\StoreStockMovementRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Models\Supplier;

class StockMovementController extends Controller
{
    public function __construct(private StockService $stock) {}

    public function index(Request $request): View
    {
        $request->validate([
            'type' => ['nullable', Rule::enum(MovementType::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'product_id' => ['nullable', 'integer'],
        ]);

        $term = $request->input('q');

        $movements = StockMovement::query()
            ->with(['product', 'creator', 'reference'])
            ->when($request->filled('product_id'), fn($q) => $q->where('product_id', $request->integer('product_id')))
            ->when(filled($term), fn($q) => $q->whereHas('product', fn($p) => $p
                ->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")))
            ->when($request->filled('type'), fn($q) => $q->where('movement_type', $request->input('type')))
            ->when($request->filled('from'), fn($q) => $q->where('occurred_at', '>=', Carbon::parse($request->input('from'))->startOfDay()))
            ->when($request->filled('to'), fn($q) => $q->where('occurred_at', '<=', Carbon::parse($request->input('to'))->endOfDay()))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('stock-movements.index', [
            'movements' => $movements,
            'types' => MovementType::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('stock-movements.create', [
            'products' => Product::active()->withStock()->orderBy('products.name')->get(),
            'types' => MovementType::allowedFor($request->user()),
            'selectedProduct' => $request->integer('product_id') ?: null,
            'suppliers' => $request->user()->can('suppliers.view')
                ? Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function store(StoreStockMovementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $type = MovementType::from($data['movement_type']);

        // Le coût unitaire n'est pris en compte que pour les entrées, avec la permission dédiée.
        $unitCost = $user->can('products.view_cost') && $type->isIncoming()
            ? ($data['unit_cost'] ?? null)
            : null;
        $supplier = null;

        if ($type === MovementType::Purchase && filled($data['supplier_id'] ?? null) && $user->can('suppliers.view')) {
            $supplier = Supplier::find($data['supplier_id']);
        }

        try {
            $this->stock->record(
                product: Product::findOrFail($data['product_id']),
                type: $type,
                quantity: $data['quantity'],
                by: $user,
                reason: filled($data['reason'] ?? null) ? trim($data['reason']) : null,
                unitCost: $unitCost !== null ? (string) $unitCost : null,
                occurredAt: filled($data['occurred_at'] ?? null) ? Carbon::parse($data['occurred_at']) : null,
            );
        } catch (StockException $e) {
            return back()->withInput()->withErrors(['quantity' => $e->getMessage()]);
        }

        return redirect()->route('stock-movements.index')->with('success', 'Mouvement enregistré.');
    }
}
