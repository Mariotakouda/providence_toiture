<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicRequestRequest;
use App\Services\OnlineRequestService;
use Illuminate\Http\RedirectResponse;

class PublicRequestController extends Controller
{
    public function __construct(private OnlineRequestService $requests)
    {
    }

    public function store(StorePublicRequestRequest $request): RedirectResponse
    {
        $onlineRequest = $this->requests->submit(
            $request->validated(),
            $request->validated('items') ?? [],
            $request->ip()
        );

        return redirect('/#demande')->with('request_reference', $onlineRequest->reference);
    }
}
