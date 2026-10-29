<?php

namespace App\Http\Controllers;

use App\Enums\Unit;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);

        $status = $request->input('status', 'active');

        $products = Product::query()
            ->withStock()
            ->with('category')
            ->withCount('variants')
            ->search($request->input('q'))
            ->when($request->filled('category_id'), fn($q) => $q->where('products.category_id', $request->input('category_id')))
            ->when($status === 'active', fn($q) => $q->where('products.is_active', true))
            ->when($status === 'archived', fn($q) => $q->where('products.is_active', false))
            ->when($request->input('stock') === 'low', fn($q) => $q->lowStock())
            ->orderBy('products.name')
            ->paginate(15)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Product::class);

        return view('products.create', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'units' => Unit::cases(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $this->prepareData($request->validated(), $request);

        $product = Product::create($data);

        $this->audit->log('product.created', $product, null, $product->only([
            'sku',
            'name',
            'category_id',
            'unit',
            'purchase_price',
            'sale_price',
            'min_stock',
            'is_active',
        ]));

        return redirect()->route('products.show', $product)->with('success', 'Produit créé.');
    }

    public function show(Product $product): View
    {
        Gate::authorize('view', $product);

        $movements = $product->stockMovements()
            ->with('creator')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('products.show', compact('product', 'movements'));

    }

    public function edit(Product $product): View
    {
        Gate::authorize('update', $product);

        return view('products.edit', [
            'product' => $product,
            'categories' => Category::where('is_active', true)
                ->orWhere('id', $product->category_id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'units' => Unit::cases(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $this->prepareData($request->validated(), $request, $product);

        $product->fill($data);
        $changes = $product->getDirty();

        if ($changes) {
            $old = array_intersect_key($product->getOriginal(), $changes);
            $product->save();
            $this->audit->log('product.updated', $product, $old, $changes);
        }

        return redirect()->route('products.show', $product)->with('success', 'Produit mis à jour.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        Gate::authorize('archive', $product);

        $newStatus = ! $product->is_active;
        $product->update(['is_active' => $newStatus]);

        $this->audit->log(
            $newStatus ? 'product.restored' : 'product.archived',
            $product,
            ['is_active' => ! $newStatus],
            ['is_active' => $newStatus]
        );

        return back()->with('success', $newStatus ? 'Produit réactivé.' : 'Produit archivé.');
    }

    private function prepareData(array $data, Request $request, ?Product $product = null): array
    {
        unset($data['image'], $data['remove_image']);

        if ($request->hasFile('image')) {
            if ($product?->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        } elseif ($product && $request->boolean('remove_image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = null;
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['min_stock'] = $data['min_stock'] ?? 0;

        // Sans la permission, le coût d'achat n'est ni lu ni modifié.
        if (! $request->user()->can('products.view_cost')) {
            unset($data['purchase_price']);
        }

        return $data;
    }
}
