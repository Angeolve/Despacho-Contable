@props(['label' => 'Navegación del cliente'])

@php
    $items = [
        ['label' => 'Inicio', 'route' => 'client.home', 'icon' => 'home'],
        ['label' => 'Catálogo de servicios', 'url' => url('/').'#servicios', 'icon' => 'catalog'],
        ['label' => 'Mis servicios', 'route' => 'client.services', 'icon' => 'services', 'upcoming' => true],
        ['label' => 'Calculadora fiscal', 'route' => 'client.calculator', 'icon' => 'calculator', 'upcoming' => true],
        ['label' => 'Mis resultados', 'route' => 'client.results', 'icon' => 'results', 'upcoming' => true],
        ['label' => 'Mi perfil', 'route' => 'client.profile', 'icon' => 'profile'],
    ];
@endphp

<nav aria-label="{{ $label }}" class="client-nav">
    @foreach ($items as $item)
        <a href="{{ isset($item['route']) ? route($item['route']) : $item['url'] }}"
            @if (isset($item['route']) && request()->routeIs($item['route'])) aria-current="page" @endif
        >
            <x-client.icon :name="$item['icon']" />
            <span>{{ $item['label'] }}</span>
            @if ($item['upcoming'] ?? false)
                <span class="client-nav-dot"><span class="sr-only">Próximamente</span></span>
            @elseif (isset($item['url']))
                <x-client.icon name="external" class="client-nav-external" />
                <span class="sr-only">Catálogo público</span>
            @endif
        </a>
    @endforeach
</nav>
