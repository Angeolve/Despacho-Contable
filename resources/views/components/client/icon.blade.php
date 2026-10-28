@props(['name'])

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>
    @switch($name)
        @case('home')
            <path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z" />
            @break
        @case('catalog')
            <rect x="3" y="3" width="7" height="7" rx="1.5" /><rect x="14" y="3" width="7" height="7" rx="1.5" /><rect x="3" y="14" width="7" height="7" rx="1.5" /><rect x="14" y="14" width="7" height="7" rx="1.5" />
            @break
        @case('services')
            <rect x="3" y="7" width="18" height="14" rx="2" /><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12a22 22 0 0 0 18 0M10 13v3h4v-3" />
            @break
        @case('calculator')
            <rect x="5" y="2" width="14" height="20" rx="2" /><path d="M8 6h8M8 11h1m6 0h1m-8 4h1m6 0h1m-8 4h1m6 0h1" />
            @break
        @case('results')
            <path d="M5 3h9l5 5v13H5V3Zm9 0v6h5M9 13h6m-6 4h6" />
            @break
        @case('profile')
            <circle cx="12" cy="8" r="4" /><path d="M4 21v-2a8 8 0 0 1 16 0v2" />
            @break
        @case('shield')
            <path d="m12 2 8 3v6c0 6-5 9-8 11-3-2-8-5-8-11V5l8-3Z" /><path d="m8 11 3 3 5-6" />
            @break
        @case('arrow')
            <path d="M4 12h16m-6-6 6 6-6 6" />
            @break
        @case('external')
            <path d="M14 3h7v7m0-7L10 14M9 5H4a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-5" />
            @break
        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16" />
            @break
        @case('lock')
            <rect x="5" y="10" width="14" height="11" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2" />
            @break
    @endswitch
</svg>
