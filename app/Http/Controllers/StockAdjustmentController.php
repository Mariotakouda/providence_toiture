<?php

namespace App\Http\Controllers;

use App\Exceptions\StockException;
use App\Http\Requests\StoreInventoryRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function create(Request $request): View
    {
        $products = Product::query()
            ->withStock()
            ->with('category')
            ->active()
            ->when($request->filled('category_id'), fn ($q) => $q->where('products.category_id', $request->integer('category_id')))
            ->orderBy('products.name')
            ->get();

        return view('stock.inventory', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreInventoryRequest $request): RedirectResponse
    {
        $counts = $request->input('counts', []);
        $expected = $request->input('expected', []);

        $lines = [];

        foreach ($counts as $productId => $counted) {
            if ($counted === null || $counted === '') {
                continue; // produit non compté
            }

            if (! isset($expected[$productId])) {
                return back()->withInput()->withErrors(['inventory' => 'Fiche d’inventaire invalide, rechargez la page.']);
            }

            $lines[(int) $productId] = [
                'counted' => (string) $counted,
                'expected' => (string) $expected[$productId],
            ];
        }

        if ($lines === []) {
            return back()->withInput()->withErrors(['inventory' => 'Saisissez au moins une quantité comptée.']);
        }

        try {
            $movements = $this->stock->applyInventory($lines, $request->user(), trim($request->input('reason')));
        } catch (StockException $e) {
            return back()->withInput()->withErrors(['inventory' => $e->getMessage()]);
        }

        $message = count($movements) === 0
            ? 'Inventaire enregistré : aucun écart constaté.'
            : 'Inventaire enregistré : '.count($movements).' ajustement(s) créé(s).';

        return redirect()->route('stock-movements.index')->with('success', $message);
    }
}
