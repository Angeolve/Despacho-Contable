<?php

namespace App\Http\Requests\Auth;

class ResetPasswordRequest extends PasswordResetLinkRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'token' => ['bail', 'required', 'string', 'regex:/\A[a-f0-9]{64}\z/'],
            'password' => ['bail', 'required', 'string', 'min:8', 'max:12', 'regex:/\A(?=.*\p{Lu})(?=.*[0-9])(?=.*[\p{P}\p{S}]).+\z/u'],
            'password_confirmation' => ['bail', 'required', 'string', 'same:password'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'token.*' => 'El enlace de recuperación es inválido o ha vencido. Solicita uno nuevo.',
            'password.required' => 'Ingresa una contraseña.',
            'password.string' => 'Este campo debe contener texto válido.',
            'password.min' => 'La contraseña debe tener de 8 a 12 caracteres.',
            'password.max' => 'La contraseña debe tener de 8 a 12 caracteres.',
            'password.regex' => 'Incluye al menos una mayúscula, un número y un carácter especial.',
            'password_confirmation.required' => 'Confirma tu contraseña.',
            'password_confirmation.string' => 'Este campo debe contener texto válido.',
            'password_confirmation.same' => 'Las contraseñas no coinciden.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        $token = $this->input('token');

        return is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token)
            ? route('password.reset', ['token' => $token])
            : route('password.request');
    }
}
