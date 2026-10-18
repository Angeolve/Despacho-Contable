<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
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
        ResetPassword::toMailUsing(function (object $user, #[\SensitiveParameter] string $token) {
            // Use the configured application origin, never the request's Host header.
            $url = rtrim(config('app.url'), '/').route('password.reset', [
                'token' => $token, 'email' => $user->getEmailForPasswordReset(),
            ], false);

            return (new MailMessage)
                // SMTP prevents a log mailer or failover from recording the secret URL.
                ->mailer('smtp')
                ->subject('Restablece tu contraseña · W&A Balance')
                ->greeting('Hola.')
                ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta de W&A Balance.')
                ->action('Restablecer contraseña', $url)
                ->line('Este enlace vence en '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutos y solo puede utilizarse una vez.')
                ->line('Si no solicitaste el cambio, puedes ignorar este correo. Tu contraseña no cambiará.');
        });

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
