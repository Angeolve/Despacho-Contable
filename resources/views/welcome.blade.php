@php
    $navigation = ['inicio' => 'Inicio', 'quienes-somos' => '¿Quiénes somos?', 'servicios' => 'Servicios', 'consejos-fiscales' => 'Consejos fiscales', 'contacto' => 'Contacto'];
    $services = config('landing.services', []);
    $contact = config('landing.contact', []);
@endphp
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#390304">
        <meta name="description" content="W&A Balance: consultoría jurídica, contable y fiscal para personas y empresas. Conoce nuestros servicios y contacta con el despacho.">
        <title>W&A Balance · Consultoría jurídica, contable y fiscal</title>
        <link rel="preload" as="image" href="{{ asset('images/landing/consultoria-hero-1600.webp') }}" imagesrcset="{{ asset('images/landing/consultoria-hero-800.webp') }} 800w, {{ asset('images/landing/consultoria-hero-1600.webp') }} 1600w" imagesizes="100vw">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="landing-page" data-landing-page>
        <a href="#contenido" class="landing-skip-link">Saltar al contenido</a>
        <header class="landing-header">
            <div class="landing-container landing-header-inner">
                <a href="#inicio" class="landing-brand" aria-label="W&A Balance, ir al inicio"><x-brand-logo /></a>
                <nav aria-label="Navegación principal" class="landing-desktop-nav">
                    @foreach ($navigation as $id => $label)
                        <a href="#{{ $id }}" data-section-link @if ($loop->first) aria-current="location" @endif>{{ $label }}</a>
                    @endforeach
                </nav>
                <a href="{{ route('login') }}" class="landing-login"><x-landing.icon name="user" /> Iniciar sesión</a>
                <details class="landing-mobile-menu" data-public-menu>
                    <summary aria-label="Abrir menú de navegación" aria-controls="landing-mobile-links" aria-expanded="false">
                        <span></span><span></span><span></span>
                    </summary>
                    <nav id="landing-mobile-links" aria-label="Navegación móvil">
                        @foreach ($navigation as $id => $label)
                            <a href="#{{ $id }}" data-section-link @if ($loop->first) aria-current="location" @endif>{{ $label }}</a>
                        @endforeach
                        <a href="{{ route('login') }}" class="landing-mobile-login"><x-landing.icon name="user" /> Iniciar sesión</a>
                    </nav>
                </details>
            </div>
        </header>

        <main id="contenido" tabindex="-1">
            <section id="inicio" class="landing-hero" aria-labelledby="hero-title" data-landing-section>
                <img class="landing-hero-image" src="{{ asset('images/landing/consultoria-hero-1600.webp') }}" srcset="{{ asset('images/landing/consultoria-hero-800.webp') }} 800w, {{ asset('images/landing/consultoria-hero-1600.webp') }} 1600w" sizes="100vw" alt="" width="1600" height="751" fetchpriority="high">
                <div class="landing-hero-shade" aria-hidden="true"></div>
                <x-landing.ornament class="landing-hero-ornament landing-hero-ornament-left" />
                <x-landing.ornament class="landing-hero-ornament landing-hero-ornament-right" />
                <div class="landing-container">
                    <div class="landing-hero-content">
                        <p class="landing-eyebrow">Tu aliado en el cumplimiento fiscal</p>
                        <h1 id="hero-title">Soluciones <span>contables,<br class="landing-desktop-break"> fiscales y jurídicas</span></h1>
                        <p class="landing-hero-description">Brindamos asesoría profesional y personalizada para personas y empresas, con un enfoque en claridad, confianza y resultados.</p>
                        <div class="landing-hero-actions">
                            <a href="#servicios" class="landing-button landing-button-primary">Conoce nuestros servicios <x-landing.icon name="arrow" /></a>
                            <a href="#contacto" class="landing-button landing-button-outline">Contáctanos</a>
                        </div>
                    </div>
                </div>
                <p class="landing-image-note"></p>
            </section>

            <section class="landing-benefits" aria-label="Nuestra forma de acompañarte">
                <div class="landing-container landing-benefits-grid">
                    @foreach ([['shield', 'Asesoría', 'personalizada'], ['document', 'Cumplimiento', 'normativo'], ['chart', 'Información clara', 'y oportuna'], ['people', 'Confianza y', 'acompañamiento']] as [$icon, $first, $second])
                        <div class="landing-benefit"><x-landing.icon :name="$icon" /><p>{{ $first }}<br>{{ $second }}</p></div>
                    @endforeach
                </div>
            </section>

            <section id="servicios" class="landing-services landing-section" aria-labelledby="services-title" data-landing-section>
                <div class="landing-container">
                    <div class="landing-services-heading">
                        <div><p class="landing-section-label">Nuestros servicios <span></span></p><h2 id="services-title">Soluciones para<br><em>tus necesidades</em></h2></div>
                        <p>Ofrecemos soluciones integrales en materia contable, fiscal y jurídica, adaptadas a las necesidades de personas y empresas.</p>
                        <span class="landing-catalog-label"><span></span> Todos nuestros servicios</span>
                    </div>
                    <div class="landing-services-grid">
                        @forelse ($services as $service)
                            <x-landing.service-card :service="$service" />
                        @empty
                            <p class="landing-catalog-pending">.</p>
                        @endforelse
                    </div>
                    @if (collect($services)->contains(fn ($service) => $service['price_provisional'] ?? false))
                        <p class="landing-provisional-note"></p>
                    @endif
                </div>
            </section>

            <section class="landing-why" aria-labelledby="why-title">
                <figure class="landing-why-image">
                    <img src="{{ asset('images/landing/arquitectura-480.webp') }}" srcset="{{ asset('images/landing/arquitectura-480.webp') }} 480w, {{ asset('images/landing/arquitectura-960.webp') }} 960w" sizes="(min-width: 1024px) 30vw, 100vw" alt="Composición arquitectónica ilustrativa de un edificio; no representa las oficinas del despacho" width="960" height="720" loading="lazy" decoding="async">
                    <figcaption></figcaption>
                </figure>
                <div class="landing-why-content">
                    <div class="landing-why-heading"><p class="landing-section-label">¿Por qué elegirnos? <span></span></p><h2 id="why-title">Comprometidos<br>con <em>tu tranquilidad</em></h2></div>
                    <div class="landing-why-benefits">
                        @foreach ([['people', 'Atención', 'personalizada'], ['scales', 'Soluciones', 'integrales'], ['document', 'Información', 'clara'], ['handshake', 'Acompañamiento', 'durante el proceso']] as [$icon, $first, $second])
                            <div><x-landing.icon :name="$icon" /><p>{{ $first }}<br>{{ $second }}</p></div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="quienes-somos" class="landing-about landing-section" aria-labelledby="about-title" data-landing-section>
                <div class="landing-container">
                    <div class="landing-about-grid">
                        <div class="landing-about-heading"><p class="landing-section-label">W&A Balance <span></span></p><h2 id="about-title">¿Quiénes<br><em>somos?</em></h2><div class="landing-about-mark" aria-hidden="true"><x-landing.icon name="scales" /></div></div>
                        <div class="landing-about-copy">
                            <p>En W&A Balance somos un despacho dedicado a brindar soluciones contables, fiscales y jurídicas, orientadas a atender las necesidades de personas y empresas.</p>
                            <p>Nuestro propósito es ofrecer asesoría profesional y personalizada, facilitando la comprensión de las obligaciones fiscales y contables de nuestros clientes. Buscamos generar confianza mediante una atención cercana, información clara y soluciones adaptadas a cada situación.</p>
                            <p>Creemos que una buena asesoría es fundamental para tomar decisiones informadas y mantener una adecuada organización financiera y fiscal.</p>
                        </div>
                    </div>
                    <div class="landing-purpose-grid">
                        <article class="landing-purpose-card"><div class="landing-purpose-icon"><x-landing.icon name="shield" /></div><div><p class="landing-purpose-label">Nuestro propósito</p><h3>Misión</h3><p>Brindar servicios contables, fiscales y jurídicos de calidad, mediante una atención personalizada, responsable y transparente, que permita a nuestros clientes cumplir con sus obligaciones, tomar decisiones informadas y encontrar soluciones adecuadas a sus necesidades.</p></div></article>
                        <article class="landing-purpose-card"><div class="landing-purpose-icon"><x-landing.icon name="chart" /></div><div><p class="landing-purpose-label">Hacia dónde vamos</p><h3>Visión</h3><p>Ser un despacho reconocido por su profesionalismo, confiabilidad y compromiso con sus clientes, destacando por ofrecer soluciones integrales e innovadoras que contribuyan a su tranquilidad, crecimiento y estabilidad financiera y fiscal.</p></div></article>
                    </div>
                    @unless (config('landing.institutional_copy_confirmed'))
                        <p class="landing-provisional-note">Textos institucionales propuestos, pendientes de aprobación del despacho.</p>
                    @endunless
                </div>
            </section>

            <section id="consejos-fiscales" class="landing-tips landing-section" aria-labelledby="tips-title" data-landing-section>
                <div class="landing-container">
                    <div class="landing-tips-heading"><div><p class="landing-section-label">Información para acompañarte <span></span></p><h2 id="tips-title">Consejos fiscales<br>y <em>datos de interés</em></h2></div><p>Un espacio para compartir ideas y facilitar la organización de tu información.</p></div>
                    <div class="landing-tips-grid">
                        @foreach ([['folder', 'Organización', 'Un lugar para cada documento', 'Agrupa tus documentos por tema y conserva una copia ordenada para encontrarlos cuando los necesites.'], ['document', 'Preparación', 'Tus preguntas también importan', 'Anota tus dudas antes de una asesoría y reúne la información relacionada para aprovechar mejor la conversación.'], ['bulb', 'Información', 'La claridad es un buen comienzo', 'Si un concepto no te resulta claro, pide que te lo expliquen antes de tomar una decisión.']] as [$icon, $category, $title, $copy])
                            <article class="landing-tip-card"><div class="landing-tip-top"><x-landing.icon :name="$icon" /><span>{{ $category }}</span><span class="landing-tip-number">0{{ $loop->iteration }}</span></div><div class="landing-tip-body"><p class="landing-tip-status">Contenido provisional</p><h3>{{ $title }}</h3><p>{{ $copy }}</p></div></article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="contacto" class="landing-contact landing-section" aria-labelledby="contact-title" data-landing-section>
                <x-landing.ornament class="landing-contact-ornament" />
                <div class="landing-container landing-contact-grid">
                    <div><p class="landing-section-label">Contacto <span></span></p><h2 id="contact-title">Conversemos sobre<br><em>tus necesidades.</em></h2><p class="landing-contact-intro">W&A Balance<br>Consultoría jurídica, contable y fiscal.</p>
                        @if (! empty($contact['whatsapp']))
                            <a href="https://wa.me/{{ $contact['whatsapp'] }}" class="landing-button landing-button-gold" target="_blank" rel="noopener noreferrer" aria-label="Contactar a W&A Balance por WhatsApp (se abre en otra pestaña)"><x-landing.icon name="phone" /> Escríbenos por WhatsApp <x-landing.icon name="arrow" /></a>
                        @endif
                    </div>
                    <div class="landing-contact-details">
                        @if (! empty($contact['phone']))
                            <div><x-landing.icon name="phone" /><p><span>WhatsApp</span><a href="https://wa.me/{{ $contact['whatsapp'] }}" target="_blank" rel="noopener noreferrer">{{ $contact['phone'] }}</a></p></div>
                        @endif
                        @if (! empty($contact['email']))
                            <div><x-landing.icon name="mail" /><p><span>Correo electrónico</span><a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></p></div>
                        @endif
                        @if (! empty($contact['address']))
                            <div><x-landing.icon name="pin" /><p><span>Dirección</span>{{ $contact['address'] }}</p></div>
                        @endif
                        @foreach ($contact['social_links'] ?? [] as $social)
                            <div><x-landing.icon name="people" /><p><span>{{ $social['label'] }}</span><a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer">{{ $social['handle'] }}</a></p></div>
                        @endforeach
                        <a href="{{ route('privacy.notice') }}" class="landing-contact-privacy" target="_blank" rel="noopener noreferrer">Consulta nuestro aviso de privacidad <span class="landing-pdf-label">PDF</span> <x-landing.icon name="arrow" /></a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="landing-footer">
            <div class="landing-container landing-footer-main"><a href="#inicio" class="landing-footer-brand" aria-label="W&A Balance, volver al inicio"><x-brand-logo /></a><nav aria-label="Navegación al pie de página">@foreach ($navigation as $id => $label)<a href="#{{ $id }}">{{ $label }}</a>@endforeach</nav><a href="{{ route('login') }}" class="landing-footer-login">Iniciar sesión <x-landing.icon name="arrow" /></a></div>
            <div class="landing-container landing-footer-bottom"><p>&copy; {{ date('Y') }} W&A Balance. Todos los derechos reservados.</p><a href="{{ route('privacy.notice') }}" target="_blank" rel="noopener noreferrer">Aviso de privacidad (PDF)</a></div>
        </footer>
    </body>
</html>
