<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Services\QuoteService;
use App\Services\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function __construct(
        private QuoteService $quotes,
        private SettingService $settings,
    ) {
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Quote::class);

        $request->validate([
            'status' => ['nullable', Rule::enum(QuoteStatus::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $this->quotes->expireOverdue();

        $term = trim((string) $request->input('q'));

        $quotes = Quote::query()
            ->with('customer')
            ->when($term !== '', fn ($q) => $q->where(function ($w) use ($term) {
                $w->where('quote_number', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('issue_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('issue_date', '<=', $request->input('to')))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('quotes.index', [
            'quotes' => $quotes,
            'statuses' => QuoteStatus::cases(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Quote::class);

        return view('quotes.create', $this->formData(null));
    }

    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        $quote = $this->quotes->create($request->validated(), $request->validated('lines'), $request->user());

        return redirect()->route('quotes.show', $quote)->with('success', "Devis {$quote->quote_number} créé.");
    }

    public function show(Quote $quote): View
    {
        Gate::authorize('view', $quote);

        $quote->load(['customer', 'items', 'creator']);

        return view('quotes.show', compact('quote'));
    }

    public function edit(Quote $quote): View
    {
        Gate::authorize('update', $quote);

        $quote->load('items');

        return view('quotes.edit', $this->formData($quote));
    }

    public function update(UpdateQuoteRequest $request, Quote $quote): RedirectResponse
    {
        $this->quotes->update($quote, $request->validated(), $request->validated('lines'), $request->user());

        return redirect()->route('quotes.show', $quote)->with('success', 'Devis mis à jour.');
    }

    public function transition(Request $request, Quote $quote): RedirectResponse
    {
        $request->validate(['status' => ['required', Rule::enum(QuoteStatus::class)]]);

        $to = QuoteStatus::from($request->input('status'));

        Gate::authorize('transition', [$quote, $to]);

        $this->quotes->transition($quote, $to, $request->user());

        return back()->with('success', "Devis passé à l’état « {$to->label()} ».");
    }

    public function pdf(Quote $quote)
    {
        Gate::authorize('view', $quote);

        $quote->load(['customer', 'items', 'creator']);

        return Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'company' => $this->settings->all(),
            'logo' => $this->settings->logoDataUriOrDefault(),
        ])->setPaper('a4')->stream($quote->quote_number.'.pdf');
    }

    private function formData(?Quote $quote): array
    {
        $taxEnabled = $this->settings->bool('tax_enabled');

        return [
            'quote' => $quote,
            'customers' => Customer::where('is_active', true)
                ->when($quote, fn ($q) => $q->orWhere('id', $quote->customer_id))
                ->orderBy('name')
                ->get(['id', 'name', 'phone']),
            'catalog' => $this->catalog(),
            'taxRate' => $taxEnabled ? (string) $this->settings->get('tax_rate', '0') : '0',
            'canOverride' => auth()->user()->can('quotes.discount'),
            'defaultValidUntil' => now()->addDays((int) $this->settings->get('quote_validity_days', '30'))->toDateString(),
            'defaultTerms' => $this->settings->get('quote_terms', ''),
        ];
    }

    private function catalog(): array
    {
        return Product::active()
            ->with(['variants' => fn ($q) => $q->where('is_active', true)->orderBy('variant_sku')])
            ->orderBy('products.name')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'unit' => $p->unit->label(),
                'price' => (float) $p->sale_price,
                'variants' => $p->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'label' => $v->label() ?: $v->variant_sku,
                    'price' => $v->sale_price_override !== null ? (float) $v->sale_price_override : null,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
