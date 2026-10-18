const form = document.querySelector('[data-login-form], [data-password-request-form]');

if (form) {
    form.querySelector('[aria-invalid="true"]')?.focus();
}
