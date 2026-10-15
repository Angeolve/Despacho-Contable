document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
    const password = document.getElementById(toggle.getAttribute('aria-controls'));

    if (!password) return;

    toggle.hidden = false;
    toggle.addEventListener('click', () => {
        const visible = password.type === 'password';

        password.type = visible ? 'text' : 'password';
        toggle.setAttribute('aria-label', visible
            ? (toggle.dataset.hideLabel || 'Ocultar contraseña')
            : (toggle.dataset.showLabel || 'Mostrar contraseña'));
        toggle.setAttribute('aria-pressed', String(visible));
        toggle.querySelector('[data-password-show]').hidden = visible;
        toggle.querySelector('[data-password-hide]').hidden = !visible;
    });
});
