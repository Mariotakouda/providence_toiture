<?php

namespace App\Http\Controllers;

use App\Enums\OnlineRequestType;
use App\Models\Customer;
use App\Models\OnlineRequest;
use App\Services\OnlineRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OnlineRequestController extends Controller
{
    public function __construct(private OnlineRequestService $requests)
    {
    }

    public function index(Request $request): View
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['open', 'all', 'new', 'in_progress', 'converted', 'closed'])],
            'type' => ['nullable', Rule::enum(OnlineRequestType::class)],
        ]);

        $status = $request->input('status', 'open');
        $term = trim((string) $request->input('q'));

        $requests = OnlineRequest::query()
            ->with('assignee')
            ->withCount('items')
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('reference', 'like', "%{$term}%")
                ->orWhere(fn ($x) => $x->search($term))))
            ->when($status === 'open', fn ($q) => $q->whereIn('status', ['new', 'in_progress']))
            ->when(in_array($status, ['new', 'in_progress', 'converted', 'closed'], true), fn ($q) => $q->where('status', $status))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->orderByRaw("CASE status WHEN 'new' THEN 0 WHEN 'in_progress' THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('online-requests.index', [
            'requests' => $requests,
            'status' => $status,
            'types' => OnlineRequestType::cases(),
        ]);
    }

    public function show(OnlineRequest $onlineRequest): View
    {
        $onlineRequest->load(['items', 'assignee', 'customer', 'quote']);

        $candidates = ($onlineRequest->status->isOpen() && ! $onlineRequest->customer_id)
            ? Customer::findDuplicates($onlineRequest->phone, $onlineRequest->email)
            : collect();

        return view('online-requests.show', [
            'onlineRequest' => $onlineRequest,
            'candidates' => $candidates,
        ]);
    }

    public function take(Request $request, OnlineRequest $onlineRequest): RedirectResponse
    {
        $this->requests->takeOver($onlineRequest, $request->user());

        return back()->with('success', 'Demande prise en charge.');
    }

    public function convert(Request $request, OnlineRequest $onlineRequest): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->can('quotes.create'), 403, 'Vous ne pouvez pas créer de devis.');

        $data = $request->validate(['customer_choice' => ['required', 'string']]);

        $customerId = ctype_digit($data['customer_choice']) ? (int) $data['customer_choice'] : null;

        if ($customerId === null) {
            abort_unless($user->can('customers.create'), 403, 'Vous ne pouvez pas créer de client.');
        }

        $quote = $this->requests->convertToQuote($onlineRequest, $user, $customerId);

        return redirect()->route('quotes.show', $quote)
            ->with('success', "Devis {$quote->quote_number} créé en brouillon depuis la demande {$onlineRequest->reference}.");
    }

    public function close(Request $request, OnlineRequest $onlineRequest): RedirectResponse
    {
        $data = $request->validate(
            ['reason' => ['required', 'string', 'min:3', 'max:255']],
            ['reason.required' => 'Le motif de clôture est obligatoire.']
        );

        $this->requests->close($onlineRequest, trim($data['reason']), $request->user());

        return back()->with('success', 'Demande clôturée.');
    }
}
