<form action="{{ route('logout') }}" method="post" class="mt-5">
    @csrf
    <button type="submit" class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-md px-4 text-sm text-gold underline decoration-gold/40 underline-offset-4 transition-colors hover:text-gold-light focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold">Cerrar sesión</button>
</form>
