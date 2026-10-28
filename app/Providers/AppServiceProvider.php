<?php

namespace App\Providers;

use App\Models\Category;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;


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
