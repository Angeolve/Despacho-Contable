import assert from 'node:assert/strict';
import test from 'node:test';

let fixtureId = 0;

async function loadScript(t, path, document) {
    const previousDocument = Object.getOwnPropertyDescriptor(globalThis, 'document');
    globalThis.document = document;
    t.after(() => {
        if (previousDocument) Object.defineProperty(globalThis, 'document', previousDocument);
        else delete globalThis.document;
    });
    await import(`../../resources/js/${path}?fixture=${++fixtureId}`);
}

test('login: permits form submission to Laravel and focuses the first server error', async (t) => {
    const form = new EventTarget();
    let focused = false;
    form.querySelector = (selector) => selector === '[aria-invalid="true"]'
        ? { focus() { focused = true; } }
        : { hidden: true, textContent: '' };
    form.querySelectorAll = () => [];
    await loadScript(t, 'login-preview.js', { querySelector: () => form });

    const event = new Event('submit', { cancelable: true });
    form.dispatchEvent(event);
    assert.equal(event.defaultPrevented, false);
    assert.equal(focused, true);
});

test('login: leaves the recovery link available for normal navigation', async (t) => {
    const link = new EventTarget();
    const form = new EventTarget();
    form.querySelector = () => null;
    form.querySelectorAll = () => [link];
    await loadScript(t, 'login-preview.js', { querySelector: () => form });

    const event = new Event('click', { cancelable: true });
    link.dispatchEvent(event);
    assert.equal(event.defaultPrevented, false);
});

test('login: safely skips pages without the login form', async (t) => {
    await loadScript(t, 'login-preview.js', { querySelector: () => null });
});

test('password visibility: toggles type, icons and accessible labels without changing the password', async (t) => {
    const password = { type: 'password', value: ' Contraseña1! ' };
    const showIcon = { hidden: false };
    const hideIcon = { hidden: true };
    const attributes = new Map([['aria-controls', 'password'], ['aria-pressed', 'false']]);
    const toggle = new EventTarget();
    Object.assign(toggle, {
        hidden: true,
        dataset: {},
        getAttribute: (name) => attributes.get(name),
        setAttribute: (name, value) => attributes.set(name, value),
        querySelector: (selector) => selector === '[data-password-show]' ? showIcon : hideIcon,
    });
    await loadScript(t, 'password-toggle.js', {
        querySelectorAll: () => [toggle],
        getElementById: () => password,
    });
    assert.equal(toggle.hidden, false);

    toggle.dispatchEvent(new Event('click'));
    assert.equal(password.type, 'text');
    assert.equal(attributes.get('aria-pressed'), 'true');
    assert.equal(attributes.get('aria-label'), 'Ocultar contraseña');
    assert.equal(showIcon.hidden, true);
    assert.equal(hideIcon.hidden, false);

    toggle.dispatchEvent(new Event('click'));
    assert.equal(password.type, 'password');
    assert.equal(attributes.get('aria-pressed'), 'false');
    assert.equal(attributes.get('aria-label'), 'Mostrar contraseña');
    assert.equal(showIcon.hidden, false);
    assert.equal(hideIcon.hidden, true);
    assert.equal(password.value, ' Contraseña1! ');
});
