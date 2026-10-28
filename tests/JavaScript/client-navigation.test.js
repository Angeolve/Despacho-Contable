import assert from 'node:assert/strict';
import test from 'node:test';

let fixtureId = 0;

async function navigation(t, present = true) {
    const previousDocument = Object.getOwnPropertyDescriptor(globalThis, 'document');
    const previousWindow = Object.getOwnPropertyDescriptor(globalThis, 'window');
    const attributes = new Map();
    const summary = { focused: false, setAttribute: (key, value) => attributes.set(key, value), focus() { this.focused = true; } };
    const menu = new EventTarget();
    let open = false;
    Object.defineProperty(menu, 'open', { get: () => open, set(value) { open = value; menu.dispatchEvent(new Event('toggle')); } });
    menu.querySelector = () => summary;
    menu.contains = (target) => target === summary;
    const document = new EventTarget();
    document.querySelector = () => present ? menu : null;
    const media = new EventTarget();
    globalThis.document = document;
    globalThis.window = { matchMedia(query) { assert.equal(query, '(min-width: 1100px)'); return media; } };
    t.after(() => {
        if (previousDocument) Object.defineProperty(globalThis, 'document', previousDocument);
        else delete globalThis.document;
        if (previousWindow) Object.defineProperty(globalThis, 'window', previousWindow);
        else delete globalThis.window;
    });
    await import(`../../resources/js/client.js?fixture=${++fixtureId}`);
    return { menu, summary, attributes, document, media };
}

test('client menu: opening and closing keep accessible state in sync', async (t) => {
    const { menu, attributes } = await navigation(t);
    menu.open = true;
    assert.equal(attributes.get('aria-expanded'), 'true');
    assert.equal(attributes.get('aria-label'), 'Cerrar menú del cliente');
    menu.open = false;
    assert.equal(attributes.get('aria-expanded'), 'false');
    assert.equal(attributes.get('aria-label'), 'Abrir menú del cliente');
});

test('client menu: Escape closes it and returns keyboard focus to the summary', async (t) => {
    const { menu, summary, document } = await navigation(t);
    menu.open = true;
    const event = new Event('keydown', { cancelable: true });
    Object.defineProperty(event, 'key', { value: 'Escape' });
    document.dispatchEvent(event);
    assert.equal(menu.open, false);
    assert.equal(summary.focused, true);
    assert.equal(event.defaultPrevented, true);
    const closedEvent = new Event('keydown', { cancelable: true });
    Object.defineProperty(closedEvent, 'key', { value: 'Escape' });
    document.dispatchEvent(closedEvent);
    assert.equal(closedEvent.defaultPrevented, false);
});

test('client menu: following a navigation link closes it without blocking navigation', async (t) => {
    const { menu } = await navigation(t);
    menu.open = true;
    const event = new Event('click', { cancelable: true });
    Object.defineProperty(event, 'target', { value: { closest: () => ({ href: '/cliente/perfil' }) } });
    menu.dispatchEvent(event);
    assert.equal(menu.open, false);
    assert.equal(event.defaultPrevented, false);
});

test('client menu: only outside clicks dismiss it', async (t) => {
    const { menu, summary, document } = await navigation(t);
    menu.open = true;
    const inside = new Event('click');
    Object.defineProperty(inside, 'target', { value: summary });
    document.dispatchEvent(inside);
    assert.equal(menu.open, true);
    document.dispatchEvent(new Event('click'));
    assert.equal(menu.open, false);
});

test('client menu: switching to desktop closes an open mobile menu', async (t) => {
    const { menu, media } = await navigation(t);
    menu.open = true;
    const event = new Event('change');
    Object.defineProperty(event, 'matches', { value: true });
    media.dispatchEvent(event);
    assert.equal(menu.open, false);
});

test('client menu: safely skips pages without the client menu', async (t) => {
    await navigation(t, false);
});
