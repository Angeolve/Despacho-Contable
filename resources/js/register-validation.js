// Match the email syntax used by HTML email inputs; this does not check availability.
const emailPattern = /^[a-z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)*$/i;
const namePattern = /^(?:\p{L}\p{M}*| )+$/u;

export function trimRegistrationName(value) {
    // Match Laravel: remove ordinary outer spaces, preserving invalid whitespace.
    return value.replace(/^ +| +$/g, '');
}

export function validateRegistrationField(field, value, password = '') {
    switch (field) {
        case 'firstName':
        case 'lastName': {
            const normalized = trimRegistrationName(value);
            if (!normalized) {
                return field === 'firstName' ? 'Ingresa tu nombre o nombres.' : 'Ingresa tus apellidos.';
            }

            if (Array.from(normalized).length > 255) {
                return field === 'firstName'
                    ? 'El nombre no puede superar los 255 caracteres.'
                    : 'Los apellidos no pueden superar los 255 caracteres.';
            }

            return namePattern.exec(normalized)?.[0] === normalized
                ? ''
                : 'Usa únicamente letras y espacios; se permiten acentos y ñ.';
        }
        case 'phone':
            if (!value) return 'Ingresa tu número de teléfono.';
            return value.length === 10 && /^[0-9]{10}$/.test(value) ? '' : 'El teléfono debe contener exactamente 10 dígitos del 0 al 9, sin espacios ni símbolos.';
        case 'email':
            if (!value.trim()) return 'Ingresa tu correo electrónico.';
            return emailPattern.exec(value)?.[0] === value ? '' : 'Ingresa un correo electrónico válido, por ejemplo: nombre@dominio.com.';
        case 'password': {
            if (!value) return 'Ingresa una contraseña.';

            const missing = [];
            const length = Array.from(value).length;
            if (length < 8 || length > 12) missing.push('tener de 8 a 12 caracteres');
            if (!/\p{Lu}/u.test(value)) missing.push('incluir al menos una mayúscula');
            if (!/[0-9]/.test(value)) missing.push('incluir al menos un número');
            if (!/[\p{P}\p{S}]/u.test(value)) missing.push('incluir al menos un carácter especial');

            return missing.length ? `La contraseña debe ${missing.join(', ')}.` : '';
        }
        case 'passwordConfirmation':
            if (!value) return 'Confirma tu contraseña.';
            return value === password ? '' : 'Las contraseñas no coinciden.';
        case 'privacy':
            return value === true ? '' : 'Debes aceptar el aviso de privacidad para continuar.';
        default:
            throw new Error(`Unknown registration field: ${field}`);
    }
}
