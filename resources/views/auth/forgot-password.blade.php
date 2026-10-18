<x-layouts.guest title="Recuperar contraseña">
    <section aria-labelledby="forgot-password-title" class="login-card relative w-full max-w-115 rounded-2xl border border-gold/20 px-6 py-9 sm:px-10 sm:py-10">
        <div aria-hidden="true" class="absolute top-0 left-1/2 h-px w-24 -translate-x-1/2 bg-linear-to-r from-transparent via-gold/65 to-transparent"></div>
        <div class="text-center">
            <h1 id="forgot-password-title" class="font-display text-[32px] leading-tight tracking-tight sm:text-[36px]">Recuperar contraseña</h1>
            <p class="mt-3 text-[13px] leading-relaxed text-muted">Ingresa tu correo para solicitar un enlace de recuperación.</p>
        </div>

        @if (session('status'))
            <p role="status" aria-live="polite" aria-atomic="true" class="mt-5 text-sm leading-relaxed break-words text-gold-light">{{ session('status') }}</p>
        @endif
        @error('token')
            <p role="alert" class="mt-5 text-sm leading-relaxed break-words text-rose-200">{{ $message }}</p>
        @enderror

        <form action="{{ route('password.email') }}" method="post" class="mt-7" data-password-request-form>
            @csrf
            <x-form.input
                id="recovery-email"
                name="email"
                :value="is_string(old('email')) ? old('email') : ''"
                :error="$errors->first('email')"
                label="Correo electrónico"
                type="email"
                icon="mail"
                placeholder="tu@correo.com"
                autocomplete="email"
                inputmode="email"
                autocapitalize="none"
                spellcheck="false"
                maxlength="255"
                required
            />
            <button type="submit" class="mt-6 flex min-h-13 w-full cursor-pointer items-center justify-center rounded-lg border border-white/5 bg-linear-to-r from-wine-700 to-wine-600 px-4 py-3 text-sm font-medium text-white hover:from-wine-600 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold">Enviar enlace de recuperación</button>
        </form>
        <p class="mt-5 text-center text-xs leading-6 text-muted">
            <a href="{{ route('login') }}" class="inline-block py-2 text-gold underline decoration-gold/40 underline-offset-4 hover:text-gold-light focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold">Volver a iniciar sesión</a>
        </p>
    </section>
</x-layouts.guest>
