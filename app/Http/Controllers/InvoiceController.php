<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoiceService;
use App\Services\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoices,
        private SettingService $settings,
    ) {
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Invoice::class);

        $request->validate([
            'status' => ['nullable', Rule::enum(InvoiceStatus::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $term = trim((string) $request->input('q'));

        $invoices = Invoice::query()
            ->with('customer')
            ->when($term !== '', fn ($q) => $q->where(function ($w) use ($term) {
                $w->where('invoice_number', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->boolean('overdue'), fn ($q) => $q->overdue())
            ->when($request->filled('from'), fn ($q) => $q->whereDate('issue_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('issue_date', '<=', $request->input('to')))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $outstanding = Invoice::whereIn('status', [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value])->sum('balance_due');

        return view('invoices.index', [
            'invoices' => $invoices,
            'statuses' => InvoiceStatus::cases(),
            'outstanding' => $outstanding,
        ]);
    }

    public function create(Order $order): View
    {
        Gate::authorize('createInvoice', $order);

        $order->load('customer');

        return view('invoices.create', [
            'order' => $order,
            'defaultDueDate' => now()->addDays(30)->toDateString(),
            'defaultTerms' => $this->settings->get('invoice_terms', ''),
        ]);
    }

    public function store(StoreInvoiceRequest $request, Order $order): RedirectResponse
    {
        $invoice = $this->invoices->createFromOrder($order, $request->validated(), $request->user());

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Facture {$invoice->invoice_number} créée en brouillon.");
    }

    public function show(Invoice $invoice): View
    {
        Gate::authorize('view', $invoice);

        $invoice->load(['order', 'items', 'creator', 'payments.receiver', 'payments.canceller']);

        return view('invoices.show', [
            'invoice' => $invoice,
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function edit(Invoice $invoice): View
    {
        Gate::authorize('update', $invoice);

        return view('invoices.edit', compact('invoice'));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->invoices->update($invoice, $request->validated(), $request->user());

        return redirect()->route('invoices.show', $invoice)->with('success', 'Facture mise à jour.');
    }

    public function transition(Request $request, Invoice $invoice): RedirectResponse
    {
        $request->validate([
            'status' => ['required', Rule::in([InvoiceStatus::Issued->value, InvoiceStatus::Cancelled->value])],
            'reason' => ['required_if:status,cancelled', 'nullable', 'string', 'min:3', 'max:255'],
        ], [
            'reason.required_if' => 'Le motif d’annulation est obligatoire.',
        ]);

        $to = InvoiceStatus::from($request->input('status'));

        Gate::authorize('transition', [$invoice, $to]);

        if ($to === InvoiceStatus::Issued) {
            $this->invoices->issue($invoice, $request->user());
            $message = 'Facture émise.';
        } else {
            $this->invoices->cancel($invoice, trim($request->input('reason')), $request->user());
            $message = 'Facture annulée.';
        }

        return back()->with('success', $message);
    }

    public function pdf(Invoice $invoice)
    {
        Gate::authorize('view', $invoice);

        $invoice->load(['order', 'items', 'creator', 'payments' => fn ($q) => $q->where('status', 'validated')]);

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => $this->settings->all(),
            'logo' => $this->settings->logoDataUriOrDefault(),
        ])->setPaper('a4')->stream($invoice->invoice_number.'.pdf');
    }
}
