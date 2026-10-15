const form = document.querySelector('[data-login-preview]');

if (form) {
    const notice = form.querySelector('[data-preview-notice]');

    const showNotice = (message) => {
        notice.hidden = false;
        notice.textContent = message;
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        showNotice('El inicio de sesión estará disponible próximamente.');
    });

    form.querySelectorAll('[data-preview-link]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            showNotice(link.dataset.previewMessage);
        });
    });
}
