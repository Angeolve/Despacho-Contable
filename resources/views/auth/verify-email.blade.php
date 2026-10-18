<x-layouts.guest title="Verifica tu correo">
    <section aria-labelledby="verification-title" class="login-card relative w-full max-w-115 rounded-2xl border border-gold/20 px-6 py-9 text-center sm:px-10 sm:py-10">
        <div aria-hidden="true" class="absolute top-0 left-1/2 h-px w-24 -translate-x-1/2 bg-linear-to-r from-transparent via-gold/65 to-transparent"></div>
        <h1 id="verification-title" class="font-display text-[32px] leading-tight tracking-tight sm:text-[36px]">Verifica tu correo</h1>
        <p class="mt-4 text-sm leading-relaxed text-muted">Tu cuenta de cliente fue creada. Confirma tu correo para completar el registro.</p>
        <p class="mt-4 text-sm leading-relaxed text-ivory break-words">{{ auth()->user()->email }}</p>
        <p class="mt-4 text-xs leading-relaxed text-muted">Abre el enlace del correo en este mismo navegador. Si no lo encuentras, revisa la carpeta de correo no deseado.</p>

        @if (session('mail_error'))
            <p role="alert" class="mt-5 text-sm leading-relaxed text-rose-200">{{ session('mail_error') }}</p>
        @elseif (session('verification_sent'))
            <p role="status" class="mt-5 text-sm leading-relaxed text-gold-light">Te enviamos un enlace de verificación.</p>
        @endif

        <form action="{{ route('verification.send') }}" method="post" class="mt-7">
            @csrf
            <button type="submit" class="flex min-h-13 w-full cursor-pointer items-center justify-center rounded-lg border border-white/5 bg-linear-to-r from-wine-700 to-wine-600 px-5 py-3 text-sm font-medium text-white hover:from-wine-600 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold">Reenviar enlace de verificación</button>
        </form>
        <x-auth.logout />
    </section>
</x-layouts.guest>
