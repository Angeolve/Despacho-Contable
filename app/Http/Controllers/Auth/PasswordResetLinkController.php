<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetLinkRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetLinkController extends Controller
{
    public function store(PasswordResetLinkRequest $request): RedirectResponse
    {
        $key = 'password-link:'.hash('sha256', Str::lower($request->string('email')->toString()));

        // Apply the same cooldown whether or not the address belongs to a user.
        if (RateLimiter::attempt($key, 1, fn () => true, 60)) {
            try {
                Password::sendResetLink($request->only('email'));
            } catch (TransportExceptionInterface) {
                // Never log the exception or message body: they can contain the reset URL.
                Log::warning('No se pudo enviar un correo de recuperación de contraseña.');
            }
        }

        return redirect()->route('password.request')->with('status',
            'Si el correo corresponde a una cuenta, recibirás un enlace para restablecer tu contraseña. Revisa tu bandeja de entrada y el correo no deseado.'
        )->withInput($request->only('email'));
    }
}
