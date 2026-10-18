import { trimRegistrationName, validateRegistrationField } from './register-validation.js';

const form = document.querySelector('[data-register-form]');

if (form) {
    const fields = [...form.querySelectorAll('[data-register-field]')];
    const password = form.querySelector('[data-register-field="password"]');
    const confirmation = form.querySelector('[data-register-field="passwordConfirmation"]');
    const fieldValue = (field) => field.type === 'checkbox' ? field.checked : field.value;
    const serverErrors = new Map(fields
        .filter((field) => field.getAttribute('aria-invalid') === 'true')
        .map((field) => [field, {
            value: fieldValue(field),
            message: document.getElementById(`${field.id}-feedback`).textContent.trim(),
        }]));
    const touched = new Set(serverErrors.keys());
    const successMessages = {
        firstName: 'Nombre válido.',
        lastName: 'Apellidos válidos.',
        phone: 'El teléfono tiene 10 dígitos.',
        email: 'El formato del correo es válido.',
        password: 'La contraseña cumple los requisitos.',
        passwordConfirmation: 'Las contraseñas coinciden.',
        privacy: 'Has marcado la aceptación del aviso de privacidad.',
    };
    let submitted = false;

    // Keep native HTML constraints as a fallback; JavaScript supplies inline feedback.
    form.noValidate = true;

    const validate = (field, trimName = false) => {
        const key = field.dataset.registerField;
        if (trimName && (key === 'firstName' || key === 'lastName')) {
            field.value = trimRegistrationName(field.value);
        }
        const value = fieldValue(field);
        const serverError = serverErrors.get(field);
        if (serverError && serverError.value === value) {
            field.setCustomValidity(serverError.message);
            return false;
        }
        serverErrors.delete(field);
        const error = validateRegistrationField(key, value, password.value);
        const feedback = document.getElementById(`${field.id}-feedback`);
        const message = error || successMessages[key];

        field.setCustomValidity(error);
        field.setAttribute('aria-invalid', String(Boolean(error)));
        if (error) delete field.dataset.valid;
        else field.dataset.valid = 'true';

        feedback.classList.toggle('text-rose-200', Boolean(error));
        feedback.classList.toggle('text-gold-light', !error);
        if (feedback.textContent !== message) feedback.textContent = message;
        feedback.hidden = false;

        return !error;
    };

    fields.forEach((field) => {
        field.addEventListener('blur', () => {
            touched.add(field);
            validate(field, true);
        });

        field.addEventListener(field.type === 'checkbox' ? 'change' : 'input', () => {
            if (submitted || touched.has(field)) validate(field);
            if (field === password && (submitted || touched.has(confirmation))) validate(confirmation);
        });
    });

    form.addEventListener('submit', (event) => {
        submitted = true;
        const invalid = fields.filter((field) => !validate(field, true));

        if (invalid.length) {
            event.preventDefault();
            invalid[0].focus();
        }
    });

    // Keep server-side uniqueness errors until the corresponding value is edited.
    for (const [field, error] of serverErrors) field.setCustomValidity(error.message);
    serverErrors.keys().next().value?.focus();
}
