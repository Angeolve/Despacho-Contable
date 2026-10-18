<x-layouts.guest title="Registro confirmado">
    <section aria-labelledby="registration-complete-title" class="login-card relative w-full max-w-115 rounded-2xl border border-gold/20 px-6 py-9 text-center sm:px-10 sm:py-10">
        <div aria-hidden="true" class="absolute top-0 left-1/2 h-px w-24 -translate-x-1/2 bg-linear-to-r from-transparent via-gold/65 to-transparent"></div>
        <h1 id="registration-complete-title" class="font-display text-[32px] leading-tight tracking-tight sm:text-[36px]">Registro confirmado</h1>
        <p class="mt-4 text-sm leading-relaxed text-muted">Tu correo electrónico fue verificado correctamente.</p>
        <p class="mt-4 text-sm leading-relaxed text-gold-light">Gracias por registrarte como cliente de W&A Balance.</p>
        <a href="{{ route('client.home') }}" class="mt-5 inline-flex min-h-11 items-center text-sm text-gold underline decoration-gold/40 underline-offset-4 hover:text-gold-light focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold">Ir a mi espacio</a>
        <x-auth.logout />
    </section>
</x-layouts.guest>
