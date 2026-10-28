<?php

namespace App\Http\Controllers;

use App\Enums\CustomerType;
use App\Enums\InvoiceStatus;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Payment;
use App\Services\AuditService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private AuditService $audit)
    {
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Customer::class);

        $request->validate(['type' => ['nullable', Rule::enum(CustomerType::class)]]);

        $status = $request->input('status', 'active');

        $customers = Customer::query()
            ->search($request->input('q'))
            ->when($request->filled('type'), fn ($q) => $q->where('customer_type', $request->input('type')))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
            'types' => CustomerType::cases(),
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Customer::class);

        return view('customers.create', ['types' => CustomerType::cases()]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $duplicates = Customer::findDuplicates($data['phone'], $data['email'] ?? null);

        if ($duplicates->isNotEmpty() && ! $request->boolean('confirm_duplicate')) {
            return back()->withInput()->with('duplicates', $this->duplicatesPayload($duplicates));
        }

        $customer = Customer::create([
            'customer_type' => $data['customer_type'],
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $new = $customer->only(['customer_type', 'name', 'phone', 'email', 'city', 'is_active']);
        if ($duplicates->isNotEmpty()) {
            $new['duplicate_confirmed'] = true;
        }

        $this->audit->log('customer.created', $customer, null, $new);

        return redirect()->route('customers.show', $customer)->with('success', 'Client créé.');
    }

    public function show(Request $request, Customer $customer): View
    {
        Gate::authorize('view', $customer);

        $user = $request->user();

        // Chaque historique n'est chargé que si l'utilisateur a la permission correspondante.
        $quotes = $user->can('quotes.view')
            ? $customer->quotes()->orderByDesc('issue_date')->orderByDesc('id')->limit(10)->get()
            : null;

        $orders = $user->can('orders.view')
            ? $customer->orders()->orderByDesc('order_date')->orderByDesc('id')->limit(10)->get()
            : null;

        $invoices = $user->can('invoices.view')
            ? $customer->invoices()->orderByDesc('issue_date')->orderByDesc('id')->limit(10)->get()
            : null;

        $payments = $user->can('payments.view')
            ? Payment::query()
                ->whereHas('invoice', fn ($q) => $q->where('customer_id', $customer->id))
                ->orderByDesc('payment_date')
                ->orderByDesc('id')
                ->limit(10)
                ->get()
            : null;

        $balance = $user->can('invoices.view')
            ? $customer->invoices()
                ->where('status', '!=', InvoiceStatus::Cancelled->value)
                ->selectRaw('COALESCE(SUM(total), 0) as invoiced, COALESCE(SUM(amount_paid), 0) as paid, COALESCE(SUM(balance_due), 0) as due')
                ->first()
            : null;

        return view('customers.show', compact('customer', 'quotes', 'orders', 'invoices', 'payments', 'balance'));
    }

    public function edit(Customer $customer): View
    {
        Gate::authorize('update', $customer);

        return view('customers.edit', [
            'customer' => $customer,
            'types' => CustomerType::cases(),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->validated();

        $contactChanged = Phone::normalize($data['phone']) !== $customer->phone_normalized
            || mb_strtolower((string) ($data['email'] ?? '')) !== mb_strtolower((string) $customer->email);

        $duplicates = collect();

        if ($contactChanged) {
            $duplicates = Customer::findDuplicates($data['phone'], $data['email'] ?? null, $customer->id);

            if ($duplicates->isNotEmpty() && ! $request->boolean('confirm_duplicate')) {
                return back()->withInput()->with('duplicates', $this->duplicatesPayload($duplicates));
            }
        }

        $customer->fill([
            'customer_type' => $data['customer_type'],
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $changes = $customer->getDirty();

        if ($changes) {
            $old = array_intersect_key($customer->getOriginal(), $changes);
            $customer->save();

            if ($duplicates->isNotEmpty()) {
                $changes['duplicate_confirmed'] = true;
            }

            $this->audit->log('customer.updated', $customer, $old, $changes);
        }

        return redirect()->route('customers.show', $customer)->with('success', 'Client mis à jour.');
    }

    public function toggleStatus(Customer $customer): RedirectResponse
    {
        Gate::authorize('update', $customer);

        $newStatus = ! $customer->is_active;
        $customer->update(['is_active' => $newStatus]);

        $this->audit->log(
            $newStatus ? 'customer.activated' : 'customer.deactivated',
            $customer,
            ['is_active' => ! $newStatus],
            ['is_active' => $newStatus]
        );

        return back()->with('success', $newStatus ? 'Client activé.' : 'Client désactivé.');
    }

    private function duplicatesPayload(Collection $duplicates): array
    {
        return $duplicates->map(fn (Customer $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'phone' => $c->phone,
            'email' => $c->email,
            'is_active' => $c->is_active,
        ])->all();
    }
}
