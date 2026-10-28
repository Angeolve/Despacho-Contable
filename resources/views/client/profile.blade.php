<x-layouts.client title="Mi perfil">
    <div class="client-page-heading">
        <div><p class="client-eyebrow">Tu cuenta en W&A Balance</p><h1>Mi perfil</h1><p>Consulta los datos con los que creaste tu cuenta.</p></div>
        <span class="client-verified"><x-client.icon name="shield" /> Correo verificado</span>
    </div>

    <section class="client-profile-card" aria-labelledby="client-profile-title">
        <div class="client-profile-card-heading">
            <span class="client-card-icon"><x-ui.icon name="user" /></span>
            <div><h2 id="client-profile-title">Información personal</h2><p>Estos datos corresponden únicamente a tu cuenta.</p></div>
            <span class="client-readonly">Solo lectura</span>
        </div>
        <dl class="client-profile-fields">
            <div><dt>Nombre(s)</dt><dd>{{ request()->user()->name }}</dd></div>
            <div><dt>Apellidos</dt><dd>{{ request()->user()->last_name }}</dd></div>
            <div><dt>Teléfono</dt><dd>{{ request()->user()->phone }}</dd></div>
            <div><dt>Correo electrónico</dt><dd>{{ request()->user()->email }}</dd></div>
        </dl>
        <div class="client-profile-note"><x-client.icon name="lock" /><p>La edición de datos y el cambio de contraseña desde este espacio aún no están disponibles.</p></div>
    </section>
    <a href="{{ route('client.home') }}" class="client-text-link client-back-link">Volver al inicio <x-ui.icon name="arrow-right" /></a>
</x-layouts.client>
