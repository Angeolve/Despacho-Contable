<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class RegisteredClientController extends Controller
{
    public function store(RegisterRequest $request): RedirectResponse
    {
        $document = public_path(config('privacy.document'));
        $version = config('privacy.version');
        abort_unless(is_file($document) && hash_equals($version, hash_file('sha256', $document)), 503, 'El aviso de privacidad no está disponible.');

        $data = $request->safe()->only(['name', 'last_name', 'phone', 'email', 'password']);

        try {
            $user = DB::transaction(function () use ($data, $version) {
                $user = new User($data);
                $user->role()->associate(Role::where('name', Role::CLIENTE)->firstOrFail());
                $user->forceFill([
                    'privacy_policy_accepted_at' => now(),
                    'privacy_policy_version' => $version,
                ])->save();

                return $user;
            });
        } catch (UniqueConstraintViolationException $exception) {
            // The database also protects against simultaneous registrations.
            $errors = [];
            if (User::where('email', $data['email'])->exists()) {
                $errors['email'] = 'Este correo electrónico ya está registrado.';
            }
            if (User::where('phone', $data['phone'])->exists()) {
                $errors['phone'] = 'Este número de teléfono ya está registrado.';
            }
            if ($errors === []) {
                throw $exception;
            }
            throw ValidationException::withMessages($errors);
        }

        Auth::login($user);
        $request->session()->regenerate();

        try {
            event(new Registered($user));
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return redirect()->route('verification.notice')->with('mail_error', 'Tu cuenta se creó, pero no pudimos enviar el correo. Intenta reenviarlo en unos momentos.');
        }

        return redirect()->route('verification.notice')->with('verification_sent', true);
    }
}
