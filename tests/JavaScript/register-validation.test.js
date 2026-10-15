import assert from 'node:assert/strict';
import test from 'node:test';
import { validateRegistrationField } from '../../resources/js/register-validation.js';

for (const field of ['firstName', 'lastName']) {
    test(`${field}: accepts Unicode letters, accents, spaces and multiple names`, () => {
        for (const value of ['María José', 'Iñaki', 'Muñoz García', 'De la Cruz', 'Jose\u0301', '李 明']) {
            assert.equal(validateRegistrationField(field, value), '', value);
        }
    });

    test(`${field}: rejects empty input, digits, punctuation, emoji and non-space whitespace`, () => {
        for (const value of ['', '   ', 'Ana2', 'Ana-María', "O'Connor", 'Ana 😀', 'Ana\tMaría', 'Ana\nMaría', 'Ana\n', '\u0301']) {
            assert.notEqual(validateRegistrationField(field, value), '', JSON.stringify(value));
        }
    });
}

test('phone: requires exactly ten ASCII digits', () => {
    for (const value of ['5512345678', '0000000000']) {
        assert.equal(validateRegistrationField('phone', value), '');
    }
    for (const value of ['', '551234567', '55123456789', '55 12345678', '+521234567', '551234567a', '5512345678\n', '５５１２３４５６７８', '٥٥١٢٣٤٥٦٧٨']) {
        assert.notEqual(validateRegistrationField('phone', value), '', value);
    }
});

test('email: checks HTML-compatible syntax without claiming uniqueness or existence', () => {
    for (const value of ['ana@example.com', 'ana+despacho@example.com.mx', 'ana@dominio']) {
        assert.equal(validateRegistrationField('email', value), '', value);
    }
    for (const value of ['', 'ana', 'ana@', '@dominio.com', 'ana@@dominio.com', 'ana apellido@dominio.com', 'ana@-dominio.com', 'ana@dominio..com', 'ana@example.com\n']) {
        assert.notEqual(validateRegistrationField('email', value), '', value);
    }
});

test('password: accepts the length boundaries and required character classes', () => {
    for (const value of ['Abcdef1!', 'Abcdefghij1!', 'Ñandú12!', 'Abcde1😀x']) {
        assert.equal(validateRegistrationField('password', value), '', value);
    }
});

test('password: explains each missing requirement and does not count whitespace as special', () => {
    for (const [value, message] of [
        ['', 'Ingresa una contraseña'],
        ['Abcde1!', '8 a 12'],
        ['Abcdefghijk1!', '8 a 12'],
        ['abcdef1!', 'mayúscula'],
        ['Abcdefg!', 'número'],
        ['Abcdef12', 'carácter especial'],
        ['Abcdef1 ', 'carácter especial'],
    ]) {
        assert.ok(validateRegistrationField('password', value).includes(message), value);
    }
});

test('confirmation: requires an exact match, preserving spaces and case', () => {
    assert.equal(validateRegistrationField('passwordConfirmation', 'Abcdef1!', 'Abcdef1!'), '');
    for (const value of ['', 'abcdef1!', 'Abcdef1! ']) {
        assert.notEqual(validateRegistrationField('passwordConfirmation', value, 'Abcdef1!'), '');
    }
});

test('privacy: requires affirmative checkbox acceptance', () => {
    assert.equal(validateRegistrationField('privacy', true), '');
    for (const value of [false, '', 'true', null]) {
        assert.notEqual(validateRegistrationField('privacy', value), '');
    }
});
