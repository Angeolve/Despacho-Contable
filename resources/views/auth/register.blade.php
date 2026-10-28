<x-layouts.guest title="Crear cuenta">
    <section aria-labelledby="register-title" class="login-card relative w-full max-w-140 rounded-2xl border border-gold/20 px-6 py-8 sm:px-10 sm:py-9">
        <div aria-hidden="true" class="absolute top-0 left-1/2 h-px w-24 -translate-x-1/2 bg-linear-to-r from-transparent via-gold/65 to-transparent"></div>

        <div class="text-center">
            <h1 id="register-title" class="font-display text-[32px] leading-tight tracking-tight sm:text-[36px]">
                Crear cuenta
            </h1>
            <p class="mt-3 text-[13px] leading-relaxed text-muted">
                Regístrate como cliente de W&A Balance.
            </p>
        </div>

        <form action="{{ route('register') }}" method="post" class="mt-7" data-register-form>
            @csrf
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-form.input
                    id="register-name"
                    name="name"
                    :value="is_string(old('name')) ? old('name') : ''"
                    :error="$errors->first('name')"
                    maxlength="255"
                    label="Nombre(s)"
                    icon="user"
                    placeholder="Tu nombre o nombres"
                    autocomplete="given-name"
                    pattern="(?:\p{L}\p{M}*| )+"
                    data-register-field="firstName"
                    :live-feedback="true"
                    required
                />

                <x-form.input
                    id="register-last-name"
                    name="last_name"
                    :value="is_string(old('last_name')) ? old('last_name') : ''"
                    :error="$errors->first('last_name')"
                    maxlength="255"
                    label="Apellidos"
                    icon="user"
                    placeholder="Tus apellidos"
                    autocomplete="family-name"
                    pattern="(?:\p{L}\p{M}*| )+"
                    data-register-field="lastName"
                    :live-feedback="true"
                    required
                />

                <x-form.input
                    id="register-phone"
                    name="phone"
                    :value="is_string(old('phone')) ? old('phone') : ''"
                    :error="$errors->first('phone')"
                    label="Número de teléfono"
                    type="tel"
                    icon="phone"
                    placeholder="5512345678"
                    autocomplete="tel"
                    inputmode="tel"
                    minlength="10"
                    maxlength="10"
                    pattern="[0-9]{10}"
                    data-register-field="phone"
                    :live-feedback="true"
                    required
                />

                <x-form.input
                    id="register-email"
                    name="email"
                    :value="is_string(old('email')) ? old('email') : ''"
                    :error="$errors->first('email')"
                    maxlength="255"
                    label="Correo electrónico"
                    type="email"
                    icon="mail"
                    placeholder="tu@correo.com"
                    autocomplete="email"
                    inputmode="email"
                    autocapitalize="none"
                    spellcheck="false"
                    data-register-field="email"
                    :live-feedback="true"
                    required
                />

                <div>
                    <x-form.password
                        id="register-password"
                        name="password"
                        :error="$errors->first('password')"
                        pattern="(?=.*\p{Lu})(?=.*[0-9])(?=.*[\p{P}\p{S}]).{8,12}"
                        aria-describedby="register-password-requirements"
                        data-register-field="password"
                        :live-feedback="true"
                        required
                    />
                    <p id="register-password-requirements" class="mt-2 text-xs leading-relaxed text-muted">
                        De 8 a 12 caracteres, con al menos una mayúscula, un número y un carácter especial.
                    </p>
                </div>

                <x-form.password
                    id="register-password-confirmation"
                    name="password_confirmation"
                    :error="$errors->first('password_confirmation')"
                    label="Confirmar contraseña"
                    placeholder="Repite la contraseña"
                    toggle-label="confirmación de contraseña"
                    data-register-field="passwordConfirmation"
                    :live-feedback="true"
                    required
                />
            </div>

            <div class="mt-5 space-y-2.5">
                <div class="flex items-start gap-3">
                    <input
                        id="register-privacy"
                        name="privacy"
                        value="1"
                        type="checkbox"
                        required
                        aria-invalid="{{ $errors->has('privacy') ? 'true' : 'false' }}"
                        @checked(in_array(old('privacy'), ['1', 1, true, 'true', 'on', 'yes'], true))
                        aria-describedby="register-privacy-feedback"
                        data-register-field="privacy"
                        class="mt-1 size-4 shrink-0 cursor-pointer accent-gold focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold aria-invalid:ring-1 aria-invalid:ring-rose-300"
                    >
                    <label for="register-privacy" class="cursor-pointer text-xs leading-6 text-muted">
                        Acepto el
                        <a
                            href="{{ route('privacy.notice') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Abrir el aviso de privacidad en PDF (nueva pestaña)"
                            class="inline-block text-gold underline decoration-gold/40 underline-offset-4 transition-colors hover:text-gold-light focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold motion-reduce:transition-none"
                        >aviso de privacidad</a>.
                    </label>
                </div>
                <p id="register-privacy-feedback" role="status" aria-live="polite" aria-atomic="true" @class(['text-xs leading-relaxed break-words', 'text-rose-200' => $errors->has('privacy'), 'text-gold-light' => ! $errors->has('privacy')]) data-field-feedback @if (! $errors->has('privacy')) hidden @endif>{{ $errors->first('privacy') }}</p>
            </div>

            <button
                type="submit"
                class="group relative mt-6 flex h-13 w-full cursor-pointer items-center justify-center gap-3 rounded-lg border border-white/5 bg-linear-to-r from-wine-700 to-wine-600 px-5 text-sm font-medium text-white shadow-[0_4px_18px_#800d251a] transition-colors hover:from-wine-600 hover:to-[#ac2842] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold active:from-wine-800 active:to-wine-700 motion-reduce:transition-none"
            >
                Crear cuenta
                <x-ui.icon name="arrow-right" class="absolute right-4 size-4 text-ivory/65 transition-transform group-hover:translate-x-0.5 motion-reduce:transition-none" />
            </button>

            <div aria-hidden="true" class="my-6 flex items-center gap-3">
                <span class="h-px flex-1 bg-linear-to-r from-transparent to-gold/20"></span>
                <span class="size-1 rotate-45 border border-gold/60"></span>
                <span class="h-px flex-1 bg-linear-to-r from-gold/20 to-transparent"></span>
            </div>

            <p class="text-center text-xs leading-6 text-muted">
                ¿Ya tienes una cuenta?
                <a
                    href="{{ route('login') }}"
                    class="inline-block font-medium text-gold underline decoration-gold/40 underline-offset-4 transition-colors hover:text-gold-light focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold motion-reduce:transition-none"
                >Inicia sesión</a>
            </p>

        </form>
    </section>
</x-layouts.guest>
