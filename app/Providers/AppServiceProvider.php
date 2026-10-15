<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(function (object $user, string $url) {
            return (new MailMessage)
                ->subject('Verifica tu correo · W&A Balance')
                ->greeting('Hola, '.$user->name.'.')
                ->line('Gracias por registrarte como cliente de W&A Balance.')
                ->line('Confirma tu correo electrónico para completar el registro.')
                ->action('Verificar mi correo', $url)
                ->line('Abre el enlace en el mismo navegador en el que creaste tu cuenta.')
                ->line('Si no solicitaste esta cuenta, no necesitas realizar ninguna acción.');
        });
    }
}
