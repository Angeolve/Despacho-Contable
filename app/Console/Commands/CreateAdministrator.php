<?php

namespace App\Console\Commands;

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Question\Question;
use Throwable;

class CreateAdministrator extends Command
{
    protected $signature = 'wbalance:create-admin';

    protected $description = 'Crear una cuenta administrativa autorizada mediante entrada interactiva, solo en local';

    public function handle(): int
    {
        if (! $this->laravel->environment('local')) {
            $this->error('Este comando solo está disponible en el entorno local.');

            return self::FAILURE;
        }

        if (! $this->input->isInteractive()) {
            $this->error('Se requiere una terminal interactiva para capturar las credenciales de forma segura.');

            return self::FAILURE;
        }

        try {
            $role = Role::where('name', Role::ADMINISTRADOR)->first();
        } catch (Throwable) {
            $this->error('No fue posible consultar los roles. Revisa la conexión local antes de continuar.');

            return self::FAILURE;
        }

        if (! $role) {
            $this->error('El rol administrador no existe. No se creó ninguna cuenta ni se modificaron los roles.');

            return self::FAILURE;
        }

        $this->info('La cuenta requerirá verificar su correo antes de acceder a /administrador.');

        try {
            $data = [
                'name' => trim($this->askExact('Nombre(s)'), ' '),
                'last_name' => trim($this->askExact('Apellidos'), ' '),
                'phone' => $this->askExact('Teléfono (10 dígitos)'),
                'email' => $this->askExact('Correo electrónico'),
                'password' => $this->askExact('Contraseña (entrada oculta)', hidden: true),
                'password_confirmation' => $this->askExact('Confirmar contraseña (entrada oculta)', hidden: true),
            ];
        } catch (Throwable) {
            // Hidden input must fail closed; never display or log the captured response.
            $this->error('Captura cancelada o entrada oculta no disponible. Usa una terminal compatible. No se creó ninguna cuenta.');

            return self::FAILURE;
        }

        try {
            $registration = new RegisterRequest;
            $validator = Validator::make($data,
                Arr::only($registration->rules(), array_keys($data)),
                $registration->messages()
            );

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $this->error($message);
                }

                return self::FAILURE;
            }

            if (! $this->confirm('¿Confirmas crear esta nueva cuenta de administrador local?', false)) {
                $this->info('Creación cancelada. No se creó ninguna cuenta.');

                return self::SUCCESS;
            }

            $this->createAdministrator($validator->validated(), $role);
        } catch (UniqueConstraintViolationException) {
            $this->error('El correo o el teléfono ya están registrados. No se creó una cuenta duplicada.');

            return self::FAILURE;
        } catch (Throwable) {
            // Do not report exception details: console traces can expose sensitive input.
            $this->error('No fue posible completar la creación. Revisa la conexión y verifica si la cuenta existe antes de repetir el comando.');

            return self::FAILURE;
        }

        $this->info('Cuenta de administrador creada. Inicia sesión y solicita el enlace de verificación desde la pantalla de verificación.');

        return self::SUCCESS;
    }

    private function askExact(string $label, bool $hidden = false): string
    {
        $question = (new Question($label))->setTrimmable(false);

        if ($hidden) {
            $question->setHidden(true)->setHiddenFallback(false);
        }

        // With trimming disabled, Symfony preserves Enter's CRLF/LF terminator.
        // Remove only that terminator, keeping password spaces and other input.
        return preg_replace('/\r?\n\z/', '', (string) $this->output->askQuestion($question));
    }

    private function createAdministrator(#[\SensitiveParameter] array $data, Role $role): void
    {
        DB::transaction(function () use ($data, $role): void {
            $user = new User([
                ...Arr::only($data, ['name', 'last_name', 'phone', 'email']),
                'password' => Hash::make($data['password']),
            ]);
            $user->role()->associate($role);
            $user->save();
        });
    }
}
