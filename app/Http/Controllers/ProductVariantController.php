<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductVariantController extends Controller
{
    private const FIELDS = [
        'variant_sku', 'color', 'thickness', 'length', 'width', 'sale_price_override', 'is_active',
    ];

    public function __construct(private AuditService $audit)
    {
    }

    public function create(Product $product): View
    {
        Gate::authorize('update', $product);

        return view('products.variants.create', compact('product'));
    }

    public function store(ProductVariantRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->only(self::FIELDS);
        $data['is_active'] = $request->boolean('is_active');

        $variant = $product->variants()->create($data);

        $this->audit->log('product_variant.created', $variant, null, $variant->only(self::FIELDS));

        return redirect()->route('products.show', $product)->with('success', 'Variante ajoutée.');
    }

    public function edit(Product $product, ProductVariant $variant): View
    {
        Gate::authorize('update', $product);

        return view('products.variants.edit', compact('product', 'variant'));
    }

    public function update(ProductVariantRequest $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        $data = $request->safe()->only(self::FIELDS);
        $data['is_active'] = $request->boolean('is_active');

        $variant->fill($data);
        $changes = $variant->getDirty();

        if ($changes) {
            $old = array_intersect_key($variant->getOriginal(), $changes);
            $variant->save();
            $this->audit->log('product_variant.updated', $variant, $old, $changes);
        }

        return redirect()->route('products.show', $product)->with('success', 'Variante mise à jour.');
    }

    public function toggleStatus(Product $product, ProductVariant $variant): RedirectResponse
    {
        Gate::authorize('update', $product);

        $newStatus = ! $variant->is_active;
        $variant->update(['is_active' => $newStatus]);

        $this->audit->log(
            $newStatus ? 'product_variant.activated' : 'product_variant.deactivated',
            $variant,
            ['is_active' => ! $newStatus],
            ['is_active' => $newStatus]
        );

        return back()->with('success', $newStatus ? 'Variante activée.' : 'Variante désactivée.');
    }
}
