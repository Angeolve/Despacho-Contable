@props(['name' => 'document'])

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>
    @switch($name)
        @case('shield')
            <path d="M16 3 27 7v8c0 7-5 11-11 14C10 26 5 22 5 15V7L16 3Z" /><path d="m10 15 4 4 8-8" />
            @break
        @case('chart')
            <path d="M4 28h25M7 24v-8h4v8m4 0V11h4v13m4 0V6h4v18M5 11l9-6 5 2 9-5m-6 0h6v6" />
            @break
        @case('people')
            <circle cx="16" cy="10" r="5" /><path d="M7 29v-4a9 9 0 0 1 18 0v4H7ZM6 8a4 4 0 0 0 0 8m20-8a4 4 0 0 1 0 8M3 27v-4a6 6 0 0 1 4-6m22 10v-4a6 6 0 0 0-4-6" />
            @break
        @case('scales')
            <path d="M16 3v24m-6 2h12M4 8h24M16 5l-3 3m3-3 3 3M7 8l-5 12h10L7 8Zm18 0-5 12h10L25 8ZM2 20a5 5 0 0 0 10 0m8 0a5 5 0 0 0 10 0" />
            @break
        @case('calculator')
            <rect x="6" y="3" width="20" height="26" rx="2" /><path d="M6 11h20M11 16h2m6 0h2m-10 5h2m6 0h2m-10 5h2m6 0h2" />
            @break
        @case('check')
            <circle cx="16" cy="16" r="12" /><path d="m10 16 4 4 8-9" />
            @break
        @case('handshake')
            <path d="m3 15 6-9 7 3 7-3 6 9-6 9-5 3-8-4-7-8Zm7-8 6 2-5 6 3 3 6-5m-6 5 8 6m-5-3 2 5M5 13l5 4m17-4-7 0" />
            @break
        @case('bulb')
            <path d="M11 24v-3a10 10 0 1 1 10 0v3H11Zm1 4h8m-7 3h6M16 4v3m-5 5 5 5 5-5m-5 5v7" />
            @break
        @case('folder')
            <path d="M3 9V6h10l3 4h13v17H3V9Zm0 4h26M10 19h12" />
            @break
        @case('mail')
            <rect x="3" y="6" width="26" height="20" rx="2" /><path d="m3 8 13 10L29 8" />
            @break
        @case('phone')
            <path d="m8 3 5 7-4 4a22 22 0 0 0 9 9l4-4 7 5-2 5C13 31 1 18 3 5l5-2Z" />
            @break
        @case('pin')
            <path d="M27 13c0 8-11 17-11 17S5 21 5 13a11 11 0 0 1 22 0Z" /><circle cx="16" cy="13" r="4" />
            @break
        @case('arrow')
            <path d="M5 16h22m-8-8 8 8-8 8" />
            @break
        @case('user')
            <circle cx="16" cy="10" r="5" /><path d="M5 29v-4a11 11 0 0 1 22 0v4H5Z" />
            @break
        @default
            <path d="M7 3h12l7 7v19H7V3Zm12 0v8h7M12 15h9m-9 5h9m-9 5h6" />
    @endswitch
</svg>
