<x-layouts.guest :title="$title">
    <section aria-labelledby="private-home-title" class="login-card relative w-full max-w-115 rounded-2xl border border-gold/20 px-6 py-9 text-center sm:px-10 sm:py-10">
        <h1 id="private-home-title" class="font-display text-[32px] leading-tight tracking-tight sm:text-[36px]">{{ $title }}</h1>
        <p class="mt-4 text-sm leading-relaxed text-muted">Tu sesión está activa. El panel se desarrollará posteriormente.</p>
        <x-auth.logout />
    </section>
</x-layouts.guest>
