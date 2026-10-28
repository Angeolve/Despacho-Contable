<x-layouts.client title="Inicio">
    <div class="client-page-heading">
        <div>
            <p class="client-eyebrow">Tu espacio, tu tranquilidad</p>
            <h1>Hola, {{ request()->user()->name }}.</h1>
            <p>Te damos la bienvenida a tu espacio personal en W&A Balance.</p>
        </div>
        <span class="client-verified"><x-client.icon name="shield" /> Cuenta verificada</span>
    </div>

    <div class="client-overview-grid">
        <section class="client-catalog-feature" aria-labelledby="client-catalog-title">
            <div class="client-catalog-copy">
                <p class="client-eyebrow">Conoce nuestras soluciones</p>
                <h2 id="client-catalog-title">El acompañamiento<br>que necesitas.</h2>
                <p>Explora los servicios contables, fiscales y jurídicos del despacho en nuestro catálogo público.</p>
                <a href="{{ url('/') }}#servicios" class="client-button client-button-gold">Explorar catálogo <x-client.icon name="arrow" /></a>
                <span class="client-catalog-caption">Consulta informativa · Sin contratación en línea</span>
            </div>
            <div class="client-catalog-art" aria-hidden="true">
                <span class="client-art-orbit"></span>
                <div class="client-art-document"><x-client.icon name="results" /><span></span><span></span><span></span></div>
                <div class="client-art-seal"><x-client.icon name="shield" /></div>
            </div>
        </section>

        <section class="client-profile-preview" aria-labelledby="client-profile-preview-title">
            <span class="client-card-icon"><x-ui.icon name="user" /></span>
            <p class="client-eyebrow">Mi perfil</p>
            <h2 id="client-profile-preview-title">Tu información,<br>a la mano.</h2>
            <p class="client-profile-name">{{ request()->user()->name }} {{ request()->user()->last_name }}</p>
            <p class="client-profile-email">{{ request()->user()->email }}</p>
            <a href="{{ route('client.profile') }}" class="client-text-link">Consultar mi perfil <x-ui.icon name="arrow-right" /></a>
        </section>
    </div>

    <section class="client-future-section" aria-labelledby="client-options-title">
        <div class="client-section-heading">
            <div><p class="client-eyebrow">Lo que viene para ti</p><h2 id="client-options-title">Más opciones en tu espacio</h2></div>
            <p>Estas secciones estarán disponibles próximamente.</p>
        </div>
        <div class="client-feature-grid">
            @foreach ([
                ['route' => 'client.services', 'icon' => 'services', 'title' => 'Mis servicios', 'description' => 'Un lugar para consultar tus servicios y dar seguimiento a su estado.'],
                ['route' => 'client.calculator', 'icon' => 'calculator', 'title' => 'Calculadora fiscal', 'description' => 'Una estimación preliminar de impuestos para docentes de secundaria.'],
                ['route' => 'client.results', 'icon' => 'results', 'title' => 'Mis resultados', 'description' => 'Consulta de resultados y documentos cuando estén disponibles.'],
            ] as $feature)
                <article class="client-feature-card">
                    <div class="client-feature-top"><span class="client-card-icon"><x-client.icon :name="$feature['icon']" /></span><span class="client-status">Próximamente</span></div>
                    <h3>{{ $feature['title'] }}</h3>
                    <p>{{ $feature['description'] }}</p>
                    <a href="{{ route($feature['route']) }}" class="client-text-link">Conocer esta sección <span class="sr-only">: {{ $feature['title'] }}</span><x-client.icon name="arrow" /></a>
                </article>
            @endforeach
        </div>
    </section>

    <div class="client-help-note"><x-client.icon name="shield" /><p>Un espacio personal para acompañarte.<span>Por ahora puedes consultar tu perfil y conocer los servicios del despacho.</span></p></div>
</x-layouts.client>
