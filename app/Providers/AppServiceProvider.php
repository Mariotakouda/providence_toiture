<?php

namespace App\Providers;

use App\Models\Category;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rules\Password;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\SettingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Mots de passe : 12 caractères min., lettres + chiffres ; contrôle des fuites connues en production.
        Password::defaults(fn () => Password::min(12)
            ->letters()
            ->numbers()
            ->when(app()->isProduction(), fn ($rule) => $rule->uncompromised()));

        // Connexion : 5 requêtes par minute et par adresse IP (en plus du blocage par e-mail+IP de LoginRequest).
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by($request->ip())
            ->response(fn (Request $request, array $headers) => redirect()->route('login')
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Trop de tentatives. Réessayez dans une minute.'])));

        ResetPasswordNotification::toMailUsing(function (object $notifiable, string $token) {
            $url = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            return (new MailMessage)
                ->subject('Réinitialisation de votre mot de passe')
                ->greeting('Bonjour,')
                ->line('Une réinitialisation du mot de passe a été demandée pour votre compte Providence Manager.')
                ->action('Réinitialiser le mot de passe', $url)
                ->line('Ce lien expire dans 60 minutes.')
                ->line('Si vous n’êtes pas à l’origine de cette demande, ignorez ce message.')
                ->salutation('Providence Toiture');
        });

        View::composer('home', function ($view) {
            $view->with('catalog', Category::query()
                ->where('is_active', true)
                ->with(['products' => fn($q) => $q->where('is_active', true)->orderBy('name')])
                ->orderBy('name')
                ->get()
                ->filter(fn($category) => $category->products->isNotEmpty())
                ->values());
        });
    }
}
