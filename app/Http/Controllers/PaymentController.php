<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $payments,
        private SettingService $settings,
    ) {
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Payment::class);

        $request->validate([
            'method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $term = trim((string) $request->input('q'));

        $query = Payment::query()
            ->with(['invoice.customer', 'receiver'])
            ->when($term !== '', fn ($q) => $q->where(function ($w) use ($term) {
                $w->where('payment_number', 'like', "%{$term}%")
                    ->orWhere('reference', 'like', "%{$term}%")
                    ->orWhereHas('invoice', fn ($i) => $i->where('invoice_number', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%")));
            }))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->input('method')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('payment_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('payment_date', '<=', $request->input('to')));

        // Encaissements : uniquement les paiements validés du filtre en cours.
        $collected = (clone $query)->where('status', PaymentStatus::Validated->value)->sum('amount');

        $payments = $query->orderByDesc('payment_date')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'methods' => PaymentMethod::cases(),
            'statuses' => PaymentStatus::cases(),
            'collected' => $collected,
        ]);
    }

    public function store(StorePaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $payment = $this->payments->record($invoice, $request->validated(), $request->user());

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Paiement {$payment->payment_number} enregistré.");
    }

    public function cancel(Request $request, Payment $payment): RedirectResponse
    {
        Gate::authorize('cancel', $payment);

        $data = $request->validate(
            ['reason' => ['required', 'string', 'min:3', 'max:255']],
            ['reason.required' => 'Le motif d’annulation est obligatoire.']
        );

        $this->payments->cancel($payment, trim($data['reason']), $request->user());

        return back()->with('success', "Paiement {$payment->payment_number} annulé.");
    }

    public function receipt(Payment $payment)
    {
        Gate::authorize('view', $payment);

        $payment->load(['invoice.order', 'receiver']);

        $paidUpTo = $payment->invoice->payments()
            ->where('status', PaymentStatus::Validated->value)
            ->where('id', '<=', $payment->id)
            ->sum('amount');

        $remaining = bcsub((string) $payment->invoice->total, bcadd((string) $paidUpTo, '0', 2), 2);

        return Pdf::loadView('pdf.receipt', [
            'payment' => $payment,
            'remaining' => bccomp($remaining, '0', 2) < 0 ? '0.00' : $remaining,
            'company' => $this->settings->all(),
            'logo' => $this->settings->logoDataUriOrDefault(),
        ])->setPaper('a5')->stream($payment->payment_number.'.pdf');
    }
}
