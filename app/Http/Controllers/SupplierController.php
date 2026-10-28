<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\AuditService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(private AuditService $audit)
    {
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Supplier::class);

        $status = $request->input('status', 'active');

        $suppliers = Supplier::query()
            ->search($request->input('q'))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers', 'status'));
    }

    public function create(): View
    {
        Gate::authorize('create', Supplier::class);

        return view('suppliers.create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $duplicates = Supplier::findDuplicates($data['phone'] ?? null, $data['email'] ?? null);

        if ($duplicates->isNotEmpty() && ! $request->boolean('confirm_duplicate')) {
            return back()->withInput()->with('duplicates', $this->duplicatesPayload($duplicates));
        }

        $supplier = Supplier::create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $new = $supplier->only(['name', 'phone', 'email', 'city', 'is_active']);
        if ($duplicates->isNotEmpty()) {
            $new['duplicate_confirmed'] = true;
        }

        $this->audit->log('supplier.created', $supplier, null, $new);

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Fournisseur créé.');
    }

    public function show(Supplier $supplier): View
    {
        Gate::authorize('view', $supplier);

        $movements = $supplier->stockMovements()
            ->with(['product', 'creator'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('suppliers.show', compact('supplier', 'movements'));
    }

    public function edit(Supplier $supplier): View
    {
        Gate::authorize('update', $supplier);

        return view('suppliers.edit', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validated();

        $contactChanged = Phone::normalize($data['phone'] ?? null) !== $supplier->phone_normalized
            || mb_strtolower((string) ($data['email'] ?? '')) !== mb_strtolower((string) $supplier->email);

        $duplicates = collect();

        if ($contactChanged) {
            $duplicates = Supplier::findDuplicates($data['phone'] ?? null, $data['email'] ?? null, $supplier->id);

            if ($duplicates->isNotEmpty() && ! $request->boolean('confirm_duplicate')) {
                return back()->withInput()->with('duplicates', $this->duplicatesPayload($duplicates));
            }
        }

        $supplier->fill([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $changes = $supplier->getDirty();

        if ($changes) {
            $old = array_intersect_key($supplier->getOriginal(), $changes);
            $supplier->save();

            if ($duplicates->isNotEmpty()) {
                $changes['duplicate_confirmed'] = true;
            }

            $this->audit->log('supplier.updated', $supplier, $old, $changes);
        }

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Fournisseur mis à jour.');
    }

    public function toggleStatus(Supplier $supplier): RedirectResponse
    {
        Gate::authorize('update', $supplier);

        $newStatus = ! $supplier->is_active;
        $supplier->update(['is_active' => $newStatus]);

        $this->audit->log(
            $newStatus ? 'supplier.activated' : 'supplier.deactivated',
            $supplier,
            ['is_active' => ! $newStatus],
            ['is_active' => $newStatus]
        );

        return back()->with('success', $newStatus ? 'Fournisseur activé.' : 'Fournisseur désactivé.');
    }

    private function duplicatesPayload(Collection $duplicates): array
    {
        return $duplicates->map(fn (Supplier $s) => [
            'id' => $s->id,
            'name' => $s->name,
            'phone' => $s->phone,
            'email' => $s->email,
            'is_active' => $s->is_active,
        ])->all();
    }
}
