<x-layouts.client :title="$title">
    <div class="client-page-heading"><div><p class="client-eyebrow">Tu espacio sigue creciendo</p><h1>{{ $title }}</h1><p>Una nueva opción para acompañarte, próximamente.</p></div></div>
    <section class="client-empty-state" aria-labelledby="client-upcoming-title">
        <div class="client-empty-symbol" aria-hidden="true"><x-client.icon :name="$icon" /></div>
        <span class="client-status">Próximamente</span>
        <h2 id="client-upcoming-title">{{ $heading }}</h2>
        <p>{{ $description }}</p>
        @if (request()->routeIs('client.calculator'))
            <div class="client-access-note"><x-client.icon name="lock" /><p>El acceso requerirá la contratación del servicio y la confirmación del pago. Tendrá una vigencia de <strong>12 horas desde la confirmación</strong>. Todavía no puedes capturar datos ni realizar cálculos.</p></div>
        @endif
        <div class="client-empty-actions"><a href="{{ route('client.home') }}" class="client-button client-button-primary">Volver al inicio <x-client.icon name="arrow" /></a><a href="{{ url('/') }}#servicios" class="client-text-link">Consultar catálogo público <x-client.icon name="external" /></a></div>
    </section>
</x-layouts.client>
