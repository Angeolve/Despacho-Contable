<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\AuthenticatedHome;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->verifiedRedirect($request);
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return $this->verifiedRedirect($request);
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->verifiedRedirect($request);
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return redirect()->route('verification.notice')->with('mail_error', 'No pudimos enviar el correo. Inténtalo de nuevo en unos momentos.');
        }

        return redirect()->route('verification.notice')->with('verification_sent', true);
    }

    private function verifiedRedirect(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->role?->name === Role::CLIENTE
            ? 'registration.complete'
            : AuthenticatedHome::routeName($request->user()));
    }
}
