<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuoteConversionController extends Controller
{
    public function __construct(private OrderService $orders)
    {
    }

    public function store(Request $request, Quote $quote): RedirectResponse
    {
        Gate::authorize('convert', $quote);

        $order = $this->orders->createFromQuote($quote, $request->user());

        return redirect()->route('orders.show', $order)
            ->with('success', "Commande {$order->order_number} créée depuis le devis {$quote->quote_number}.");
    }
}
