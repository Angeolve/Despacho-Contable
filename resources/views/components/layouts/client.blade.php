@props(['title' => 'Inicio'])

@php($client = request()->user())
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#390304">
        <meta name="description" content="Tu espacio personal en W&A Balance.">
        <title>{{ $title }} · Mi espacio | W&A Balance</title>
        @vite(['resources/css/client.css', 'resources/js/client.js'])
    </head>
    <body class="client-panel">
        <a href="#client-content" class="client-skip-link">Saltar al contenido</a>
        <div class="client-shell">
            <aside class="client-sidebar" aria-label="Menú principal del cliente">
                <a href="{{ route('client.home') }}" class="client-brand" aria-label="W&A Balance, inicio del cliente"><x-brand-logo /></a>
                <div class="client-sidebar-rule"></div>
                <p class="client-nav-label">Mi espacio</p>
                <x-client.navigation />
                <div class="client-sidebar-note">
                    <x-client.icon name="shield" />
                    <p>Claridad en cada paso.<span>Estamos para acompañarte.</span></p>
                </div>
                <a href="{{ url('/') }}" class="client-public-link">Visitar sitio público <x-client.icon name="external" /></a>
            </aside>

            <div class="client-workspace">
                <header class="client-header">
                    <div class="client-mobile-bar">
                        <a href="{{ route('client.home') }}" class="client-mobile-brand" aria-label="W&A Balance, inicio del cliente"><x-brand-logo /></a>
                        <details class="client-mobile-menu" data-client-menu>
                            <summary aria-label="Abrir menú del cliente" aria-controls="client-mobile-links" aria-expanded="false">
                                <x-client.icon name="menu" /><span>Menú</span>
                            </summary>
                            <div id="client-mobile-links" class="client-mobile-links"><x-client.navigation label="Navegación móvil del cliente" /></div>
                        </details>
                    </div>
                    <div class="client-header-row">
                        <p class="client-breadcrumb">Mi espacio <span aria-hidden="true">/</span> <strong>{{ $title }}</strong></p>
                        <div class="client-header-actions">
                            <a href="{{ route('client.profile') }}" class="client-account">
                                <span class="client-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($client->name, 0, 1)) }}</span>
                                <span><strong>{{ $client->name }}</strong><span>Mi perfil</span></span>
                            </a>
                            <div class="client-header-logout"><x-auth.logout /></div>
                        </div>
                    </div>
                </header>

                <main id="client-content" class="client-content" tabindex="-1">{{ $slot }}</main>

                <footer class="client-footer">
                    <p>&copy; {{ date('Y') }} W&A Balance <span>Consultoría jurídica, contable y fiscal.</span></p>
                    <a href="{{ route('privacy.notice') }}" target="_blank" rel="noopener noreferrer">Aviso de privacidad <span class="sr-only">(PDF, se abre en otra pestaña)</span><x-client.icon name="external" /></a>
                </footer>
            </div>
        </div>
    </body>
</html>
