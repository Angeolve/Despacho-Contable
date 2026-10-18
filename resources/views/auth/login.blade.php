<x-layouts.guest title="Iniciar sesión">
    <section aria-labelledby="login-title" class="login-card relative w-full max-w-115 rounded-2xl border border-gold/20 px-6 py-9 sm:px-10 sm:py-10">
        <div aria-hidden="true" class="absolute top-0 left-1/2 h-px w-24 -translate-x-1/2 bg-linear-to-r from-transparent via-gold/65 to-transparent"></div>

        <div class="text-center">
            <h1 id="login-title" class="font-display text-[32px] leading-tight tracking-tight sm:text-[36px]">
                Iniciar sesión
            </h1>
            <p class="mt-3 text-[13px] leading-relaxed text-muted">
                Accede a tu cuenta para gestionar tus servicios.
            </p>
        </div>

        <form action="{{ route('login') }}" method="post" class="mt-8" data-login-form>
            @csrf
            <div class="space-y-5">
                <x-form.input
                    id="email"
                    name="email"
                    :value="old('email')"
                    :error="$errors->first('email')"
                    label="Correo electrónico"
                    type="email"
                    icon="mail"
                    placeholder="tu@correo.com"
                    autocomplete="email"
                    inputmode="email"
                    autocapitalize="none"
                    spellcheck="false"
                    required
                />

                <x-form.input
                    id="password"
                    name="password"
                    :error="$errors->first('password')"
                    label="Contraseña"
                    type="password"
                    icon="lock"
                    placeholder="Ingresa tu contraseña"
                    autocomplete="current-password"
                    required
                >
                    <button
                        type="button"
                        aria-label="Mostrar contraseña"
                        aria-controls="password"
                        aria-pressed="false"
                        class="absolute inset-y-0 right-1.5 my-auto flex size-11 cursor-pointer items-center justify-center rounded-md text-muted transition-colors hover:text-gold-light focus-visible:outline-2 focus-visible:outline-offset-0 focus-visible:outline-gold motion-reduce:transition-none"
                        data-password-toggle
                        hidden
                    >
                        <span data-password-show><x-ui.icon name="eye" /></span>
                        <span data-password-hide hidden><x-ui.icon name="eye-off" /></span>
                    </button>
                </x-form.input>
            </div>

            <div class="mt-3 text-right">
                <a
                    href="{{ route('password.request') }}"
                    class="inline-block py-1 text-xs text-gold underline decoration-gold/40 underline-offset-4 transition-colors hover:text-gold-light focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold motion-reduce:transition-none"
                >¿Olvidaste tu contraseña?</a>
            </div>

            <button
                type="submit"
                class="group relative mt-6 flex h-13 w-full cursor-pointer items-center justify-center gap-3 rounded-lg border border-white/5 bg-linear-to-r from-wine-700 to-wine-600 px-5 text-sm font-medium text-white shadow-[0_4px_18px_#800d251a] transition-colors hover:from-wine-600 hover:to-[#ac2842] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold active:from-wine-800 active:to-wine-700 motion-reduce:transition-none"
            >
                Iniciar sesión
                <x-ui.icon name="arrow-right" class="absolute right-4 size-4 text-ivory/65 transition-transform group-hover:translate-x-0.5 motion-reduce:transition-none" />
            </button>

            <div aria-hidden="true" class="my-7 flex items-center gap-3">
                <span class="h-px flex-1 bg-linear-to-r from-transparent to-gold/20"></span>
                <span class="size-1 rotate-45 border border-gold/60"></span>
                <span class="h-px flex-1 bg-linear-to-r from-gold/20 to-transparent"></span>
            </div>

            <p class="text-center text-xs leading-6 text-muted">
                ¿Aún no tienes una cuenta?
                <a
                    href="{{ route('register') }}"
                    class="inline-block font-medium text-gold underline decoration-gold/40 underline-offset-4 transition-colors hover:text-gold-light focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold motion-reduce:transition-none"
                >Regístrate como cliente</a>
            </p>

            <p id="login-notice" role="status" aria-live="polite" aria-atomic="true" class="mt-5 text-center text-xs leading-relaxed text-gold-light" @if (! session('status')) hidden @endif>{{ session('status') }}</p>
        </form>
    </section>
</x-layouts.guest>
