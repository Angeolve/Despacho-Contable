const menu = document.querySelector('[data-client-menu]');

if (menu) {
    const summary = menu.querySelector('summary');
    const closeMenu = () => { menu.open = false; };

    menu.addEventListener('toggle', () => {
        summary.setAttribute('aria-expanded', String(menu.open));
        summary.setAttribute('aria-label', menu.open ? 'Cerrar menú del cliente' : 'Abrir menú del cliente');
    });
    menu.addEventListener('click', (event) => {
        if (event.target.closest('a')) closeMenu();
    });
    document.addEventListener('click', (event) => {
        if (menu.open && !menu.contains(event.target)) closeMenu();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && menu.open) {
            event.preventDefault();
            closeMenu();
            summary.focus();
        }
    });
    window.matchMedia('(min-width: 1100px)').addEventListener('change', (event) => {
        if (event.matches) closeMenu();
    });
}
