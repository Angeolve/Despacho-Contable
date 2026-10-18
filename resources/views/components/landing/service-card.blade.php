@props(['service'])

<article class="landing-service-card">
    <div class="landing-service-icon"><x-landing.icon :name="$service['icon'] ?? 'document'" /></div>
    <div class="landing-service-body">
        <h3>{{ $service['name'] }}</h3>
        @if (! empty($service['description']))
            <p>{{ $service['description'] }}</p>
        @endif
        @if (($service['price_confirmed'] ?? false) === true && isset($service['price']))
            <p class="landing-service-price">{{ $service['price'] }} @if ($service['price_provisional'] ?? false)<span>Precio provisional</span>@endif</p>
        @endif
        <span class="landing-service-more" aria-disabled="true">Más información <x-landing.icon name="arrow" /></span>
    </div>
</article>
