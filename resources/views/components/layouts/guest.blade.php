@props(['title' => 'W&A Balance'])

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#310a12">
        <meta name="description" content="Accede a WBalance, el espacio de clientes de W&A Balance.">

        <title>{{ $title }} | W&A Balance</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-wine-950 font-sans text-ivory antialiased">
        <div class="login-scene relative flex min-h-svh flex-col overflow-hidden">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                <svg class="login-corner login-corner--top" viewBox="0 0 320 320" fill="none">
                    <path d="M142 0H320V161L259 207Z" fill="#b28b4c" />
                    <path d="M152 0H320V147L261 193Z" fill="#082e2d" />
                    <path d="M173 0H320V130L264 174Z" fill="#092122" />
                    <path d="M142 0L259 207L320 161" stroke="#efda9b" stroke-opacity=".64" stroke-width="2" />
                    <path d="M82 0L243 283L320 223" stroke="#d5b575" stroke-opacity=".10" />
                </svg>
                <svg class="login-corner login-corner--bottom" viewBox="0 0 320 320" fill="none">
                    <path d="M142 0H320V161L259 207Z" fill="#b28b4c" />
                    <path d="M152 0H320V147L261 193Z" fill="#082e2d" />
                    <path d="M173 0H320V130L264 174Z" fill="#092122" />
                    <path d="M142 0L259 207L320 161" stroke="#efda9b" stroke-opacity=".64" stroke-width="2" />
                    <path d="M82 0L243 283L320 223" stroke="#d5b575" stroke-opacity=".10" />
                </svg>
            </div>

            <main class="relative z-10 flex flex-1 flex-col items-center justify-center px-5 py-10 sm:px-8 sm:py-14">
                <header class="mb-9 flex flex-col items-center sm:mb-10">
                    <x-brand-logo class="h-auto w-60 sm:w-70" />
                </header>

                {{ $slot }}

                <footer class="mt-9 text-center text-[11px] leading-relaxed tracking-wide text-muted/65 sm:mt-10">
                    &copy; {{ date('Y') }} W&A Balance. Todos los derechos reservados.
                </footer>
            </main>
        </div>
    </body>
</html>
