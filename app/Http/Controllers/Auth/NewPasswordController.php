<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $status = DB::transaction(function () use ($request) {
            // Serialize redemption in MySQL so concurrent requests cannot reuse one token.
            DB::table(config('auth.passwords.'.Password::getDefaultDriver().'.table'))
                ->where('email', $request->input('email'))->lockForUpdate()->first();

            return Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, #[\SensitiveParameter] string $password): void {
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    if (config('session.driver') === 'database') {
                        DB::connection(config('session.connection'))->table(config('session.table'))
                            ->where('user_id', $user->getAuthIdentifier())->delete();
                    }

                    event(new PasswordReset($user));
                }
            );
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => 'El enlace de recuperación es inválido o ha vencido. Solicita uno nuevo.',
            ])->redirectTo(route('password.request'));
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Tu contraseña se restableció correctamente. Inicia sesión con tu nueva contraseña.');
    }
}
