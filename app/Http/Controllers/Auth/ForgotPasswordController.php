<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $status = Password::sendResetLink([
            'email' => $request->input('email'),
            'is_active' => true,
        ]);

        if ($status === Password::RESET_THROTTLED) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Veuillez patienter avant de refaire une demande.']);
        }

        // Même message que le compte existe ou non, pour ne pas révéler les e-mails enregistrés.
        return back()->with('status', 'Si un compte actif correspond à cette adresse, un lien de réinitialisation a été envoyé.');
    }
}
