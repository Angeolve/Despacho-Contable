<x-layouts.guest title="Restablecer contraseña">
    <section aria-labelledby="reset-password-title" class="login-card relative w-full max-w-115 rounded-2xl border border-gold/20 px-6 py-9 sm:px-10 sm:py-10">
        <div aria-hidden="true" class="absolute top-0 left-1/2 h-px w-24 -translate-x-1/2 bg-linear-to-r from-transparent via-gold/65 to-transparent"></div>
        <div class="text-center">
            <h1 id="reset-password-title" class="font-display text-[32px] leading-tight tracking-tight sm:text-[36px]">Restablecer contraseña</h1>
            <p class="mt-3 text-[13px] leading-relaxed text-muted">Elige una nueva contraseña para tu cuenta.</p>
        </div>
        <form action="{{ route('password.update') }}" method="post" class="mt-7" data-register-form>
            @csrf
            <input type="hidden" name="token" value="{{ request()->route('token') }}">
            <div class="space-y-5">
                <x-form.input
                    id="reset-email"
                    name="email"
                    :value="is_string(old('email', request()->query('email'))) ? old('email', request()->query('email')) : ''"
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
                    data-register-field="email"
                    :live-feedback="true"
                    required
                />
                <div>
                    <x-form.password
                        id="reset-password"
                        name="password"
                        label="Nueva contraseña"
                        placeholder="Tu nueva contraseña"
                        :error="$errors->first('password')"
                        pattern="(?=.*\p{Lu})(?=.*[0-9])(?=.*[\p{P}\p{S}]).{8,12}"
                        aria-describedby="reset-password-requirements"
                        data-register-field="password"
                        :live-feedback="true"
                        required
                    />
                    <p id="reset-password-requirements" class="mt-2 text-xs leading-relaxed text-muted">De 8 a 12 caracteres, con al menos una mayúscula, un número y un carácter especial.</p>
                </div>
                <x-form.password
                    id="reset-password-confirmation"
                    name="password_confirmation"
                    label="Confirmar nueva contraseña"
                    placeholder="Repite la nueva contraseña"
                    :error="$errors->first('password_confirmation')"
                    toggle-label="confirmación de contraseña"
                    data-register-field="passwordConfirmation"
                    :live-feedback="true"
                    required
                />
            </div>
            <button type="submit" class="mt-6 flex min-h-13 w-full cursor-pointer items-center justify-center rounded-lg border border-white/5 bg-linear-to-r from-wine-700 to-wine-600 px-4 py-3 text-sm font-medium text-white hover:from-wine-600 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold">Restablecer contraseña</button>
        </form>
        <p class="mt-5 text-center text-xs leading-6 text-muted">
            <a href="{{ route('password.request') }}" class="inline-block py-2 text-gold underline decoration-gold/40 underline-offset-4 hover:text-gold-light focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold">Solicitar otro enlace</a>
        </p>
    </section>
</x-layouts.guest>
