<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $name = ['bail', 'required', 'string', 'max:255', 'regex:/\A(?:\p{L}\p{M}*| )+\z/u'];

        return [
            'name' => $name,
            'last_name' => $name,
            'phone' => ['bail', 'required', 'string', 'regex:/\A[0-9]{10}\z/', Rule::unique('users', 'phone')],
            'email' => ['bail', 'required', 'string', 'max:255', 'email:rfc', Rule::unique('users', 'email')],
            'password' => ['bail', 'required', 'string', 'min:8', 'max:12', 'regex:/\A(?=.*\p{Lu})(?=.*[0-9])(?=.*[\p{P}\p{S}]).+\z/u'],
            'password_confirmation' => ['bail', 'required', 'string', 'same:password'],
            'privacy' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ingresa tu nombre o nombres.',
            'last_name.required' => 'Ingresa tus apellidos.',
            'name.regex' => 'Usa únicamente letras y espacios; se permiten acentos y ñ.',
            'last_name.regex' => 'Usa únicamente letras y espacios; se permiten acentos y ñ.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'last_name.max' => 'Los apellidos no pueden superar los 255 caracteres.',
            'phone.required' => 'Ingresa tu número de teléfono.',
            'phone.regex' => 'El teléfono debe contener exactamente 10 dígitos del 0 al 9, sin espacios ni símbolos.',
            'phone.unique' => 'Este número de teléfono ya está registrado.',
            'email.required' => 'Ingresa tu correo electrónico.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.max' => 'El correo no puede superar los 255 caracteres.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.required' => 'Ingresa una contraseña.',
            'password.min' => 'La contraseña debe tener de 8 a 12 caracteres.',
            'password.max' => 'La contraseña debe tener de 8 a 12 caracteres.',
            'password.regex' => 'Incluye al menos una mayúscula, un número y un carácter especial.',
            'password_confirmation.required' => 'Confirma tu contraseña.',
            'password_confirmation.same' => 'Las contraseñas no coinciden.',
            'privacy.required' => 'Debes aceptar el aviso de privacidad para continuar.',
            'privacy.accepted' => 'Debes aceptar el aviso de privacidad para continuar.',
            '*.string' => 'Este campo debe contener texto válido.',
        ];
    }
}
