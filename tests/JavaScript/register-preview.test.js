import assert from 'node:assert/strict';
import test from 'node:test';

let fixtureId = 0;

// A small DOM adapter exercises the actual event handlers without browser dependencies.
async function registrationForm(t, overrides = {}, serverErrors = {}) {
    const feedback = new Map();
    const document = {
        activeElement: null,
        querySelector: () => form,
        getElementById: (id) => feedback.get(id),
    };
    const definitions = [
        ['firstName', 'register-name', 'María José'],
        ['lastName', 'register-last-name', 'Muñoz de la Cruz'],
        ['phone', 'register-phone', '5512345678'],
        ['email', 'register-email', 'maria@example.test'],
        ['password', 'register-password', 'Abcdef1!'],
        ['passwordConfirmation', 'register-password-confirmation', 'Abcdef1!'],
        ['privacy', 'register-privacy', '1'],
    ];
    const fields = Object.fromEntries(definitions.map(([key, id, value]) => {
        const field = new EventTarget();
        const attributes = new Map([['aria-invalid', String(Boolean(serverErrors[key]))]]);
        Object.assign(field, {
            id,
            type: key === 'privacy' ? 'checkbox' : 'text',
            value: overrides[key] ?? value,
            checked: key === 'privacy',
            dataset: { registerField: key },
            getAttribute: (name) => attributes.get(name),
            setAttribute: (name, value) => attributes.set(name, value),
            setCustomValidity(message) { this.validationMessage = message; },
            focus() { document.activeElement = this; },
        });
        const classes = new Set();
        feedback.set(`${id}-feedback`, {
            textContent: serverErrors[key] ?? '',
            hidden: !serverErrors[key],
            classList: {
                toggle: (name, enabled) => enabled ? classes.add(name) : classes.delete(name),
                contains: (name) => classes.has(name),
            },
        });
        return [key, field];
    }));
    const form = new EventTarget();
    form.querySelectorAll = () => Object.values(fields);
    form.querySelector = (selector) => fields[selector.match(/"([^"]+)"/)[1]];
    const previousDocument = Object.getOwnPropertyDescriptor(globalThis, 'document');
    globalThis.document = document;
    t.after(() => {
        if (previousDocument) Object.defineProperty(globalThis, 'document', previousDocument);
        else delete globalThis.document;
    });
    await import(`../../resources/js/register-preview.js?fixture=${++fixtureId}`);

    return {
        form, fields, document,
        feedback: (key) => feedback.get(`${fields[key].id}-feedback`),
        submit() {
            const event = new Event('submit', { cancelable: true });
            form.dispatchEvent(event);
            return event;
        },
    };
}

for (const key of ['firstName', 'lastName']) {
    test(`${key}: invalid names block submission, expose inline errors and focus the field`, async (t) => {
        const fixture = await registrationForm(t);
        const field = fixture.fields[key];

        for (const value of ['', '   ', 'Ana2', 'Ana?', 'Ana!', 'Ana@', 'Ana#', 'Ana$', 'Ana%', 'Ana&', 'Ana"', "Ana'", 'Ana-María', 'Ana😀']) {
            field.value = value;
            assert.equal(fixture.submit().defaultPrevented, true, value);
            assert.equal(fixture.document.activeElement, field, value);
            assert.equal(field.getAttribute('aria-invalid'), 'true');
            assert.ok(field.validationMessage);
            assert.equal(fixture.feedback(key).hidden, false);
            assert.equal(fixture.feedback(key).textContent, field.validationMessage);
            assert.equal(fixture.feedback(key).classList.contains('text-rose-200'), true);
        }
    });
}

test('submit: focuses the first invalid field and updates focus after it is corrected', async (t) => {
    const fixture = await registrationForm(t, { firstName: '   ', lastName: 'Muñoz?' });
    assert.equal(fixture.submit().defaultPrevented, true);
    assert.equal(fixture.document.activeElement, fixture.fields.firstName);
    assert.equal(fixture.feedback('firstName').textContent, 'Ingresa tu nombre o nombres.');

    fixture.fields.firstName.value = 'María José';
    fixture.fields.firstName.dispatchEvent(new Event('input'));
    assert.equal(fixture.fields.firstName.getAttribute('aria-invalid'), 'false');
    assert.equal(fixture.submit().defaultPrevented, true);
    assert.equal(fixture.document.activeElement, fixture.fields.lastName);
});

test('submit: trims names and allows valid compound names without changing other values', async (t) => {
    const fixture = await registrationForm(t, { firstName: '  María  José  ', lastName: '  Muñoz de la Cruz  ' });
    assert.equal(fixture.submit().defaultPrevented, false);
    assert.equal(fixture.fields.firstName.value, 'María  José');
    assert.equal(fixture.fields.lastName.value, 'Muñoz de la Cruz');
    assert.equal(fixture.fields.firstName.dataset.valid, 'true');
    assert.equal(fixture.fields.lastName.dataset.valid, 'true');
    assert.equal(fixture.fields.phone.value, '5512345678');
    assert.equal(fixture.fields.email.value, 'maria@example.test');
    assert.equal(fixture.fields.password.value, 'Abcdef1!');
    assert.equal(fixture.fields.passwordConfirmation.value, 'Abcdef1!');
    assert.equal(fixture.fields.privacy.checked, true);
});

test('blur: trims names, while input preserves the space needed to type a compound name', async (t) => {
    const fixture = await registrationForm(t);
    for (const key of ['firstName', 'lastName']) {
        const field = fixture.fields[key];
        field.value = '  María  ';
        field.dispatchEvent(new Event('blur'));
        assert.equal(field.value, 'María');

        field.value = 'María ';
        field.dispatchEvent(new Event('input'));
        assert.equal(field.value, 'María ');
        assert.equal(field.getAttribute('aria-invalid'), 'false');

        field.value += 'José';
        field.dispatchEvent(new Event('input'));
        assert.equal(field.value, 'María José');
        assert.equal(field.validationMessage, '');
    }
});

test('server errors: retain contact uniqueness errors until their value is edited', async (t) => {
    const message = 'Este correo electrónico ya está registrado.';
    const fixture = await registrationForm(t, {}, { email: message });
    assert.equal(fixture.document.activeElement, fixture.fields.email);
    assert.equal(fixture.submit().defaultPrevented, true);
    assert.equal(fixture.fields.email.validationMessage, message);

    fixture.fields.email.value = 'otro@example.test';
    fixture.fields.email.dispatchEvent(new Event('input'));
    assert.equal(fixture.fields.email.validationMessage, '');
    assert.equal(fixture.submit().defaultPrevented, false);
});
