const landing = document.querySelector('[data-landing-page]');

if (landing) {
    const menu = landing.querySelector('[data-public-menu]');
    const summary = menu.querySelector('summary');
    const closeMenu = () => { menu.open = false; };

    menu.addEventListener('toggle', () => {
        summary.setAttribute('aria-expanded', String(menu.open));
        summary.setAttribute('aria-label', menu.open ? 'Cerrar menú de navegación' : 'Abrir menú de navegación');
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
    window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
        if (event.matches) closeMenu();
    });

    const links = [...landing.querySelectorAll('[data-section-link]')];
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            const visible = entries.filter((entry) => entry.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
            if (!visible.length) return;
            const current = `#${visible[0].target.id}`;
            links.forEach((link) => {
                if (link.getAttribute('href') === current) link.setAttribute('aria-current', 'location');
                else link.removeAttribute('aria-current');
            });
        }, { rootMargin: '-20% 0px -55% 0px' });
        landing.querySelectorAll('[data-landing-section]').forEach((section) => observer.observe(section));
    }
}
