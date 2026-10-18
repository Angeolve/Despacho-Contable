<?php

namespace Tests\Feature;

use App\Console\Commands\CreateAdministrator;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Testing\PendingCommand;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Tests\Concerns\UsesIsolatedDatabase;
use Tests\TestCase;

class CreateAdministratorCommandTest extends TestCase
{
    use UsesIsolatedDatabase;

    private string $password;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateIsolatedDatabase();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        $this->password = Str::random(6).'A1!';

        // The command requires local; the database stays explicitly in-memory.
        $this->app->instance('env', 'local');
    }

    private function answers(array $overrides = []): array
    {
        return array_replace([
            'name' => 'María José', 'last_name' => 'Muñoz de la Cruz',
            'phone' => '5512345678', 'email' => 'administracion@example.test',
            'password' => $this->password, 'password_confirmation' => $this->password,
        ], $overrides);
    }

    private function prompts(array $overrides = []): PendingCommand
    {
        $data = $this->answers($overrides);

        return $this->artisan('wbalance:create-admin')
            ->expectsQuestion('Nombre(s)', $data['name'])
            ->expectsQuestion('Apellidos', $data['last_name'])
            ->expectsQuestion('Teléfono (10 dígitos)', $data['phone'])
            ->expectsQuestion('Correo electrónico', $data['email'])
            ->expectsQuestion('Contraseña (entrada oculta)', $data['password'])
            ->expectsQuestion('Confirmar contraseña (entrada oculta)', $data['password_confirmation']);
    }

    public function test_command_creates_only_a_new_administrator_and_does_not_fabricate_verification_or_consent(): void
    {
        $this->prompts(['name' => '  María José  ', 'last_name' => '  Muñoz de la Cruz  '])
            ->expectsConfirmation('¿Confirmas crear esta nueva cuenta de administrador local?', 'yes')
            ->doesntExpectOutputToContain($this->password)->assertSuccessful();

        $user = User::sole();
        $this->assertSame('María José', $user->name);
        $this->assertSame('Muñoz de la Cruz', $user->last_name);
        $this->assertSame('5512345678', $user->phone);
        $this->assertSame('administracion@example.test', $user->email);
        $this->assertSame(Role::ADMINISTRADOR, $user->role->name);
        $this->assertTrue(Hash::check($this->password, $user->password));
        $this->assertNotSame($this->password, $user->password);
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->privacy_policy_accepted_at);
        $this->assertNull($user->privacy_policy_version);
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
        Notification::assertNothingSent();
    }

    #[DataProvider('invalidData')]
    public function test_public_registration_validation_is_enforced_before_any_insert(string $field, string $value): void
    {
        $this->prompts([$field => $value])->assertFailed();
        $this->assertDatabaseEmpty('users');
        Notification::assertNothingSent();
    }

    public static function invalidData(): array
    {
        return [
            ['name', ''], ['name', '   '], ['name', 'Ana2'], ['name', 'Ana?'],
            ['name', 'Ana😀'], ['name', "\tAna"], ['name', str_repeat('A', 256)],
            ['last_name', ''], ['last_name', '   '], ['last_name', 'López2'], ['last_name', 'López-Muñoz'],
            ['phone', ''], ['phone', '551234567'], ['phone', '55123456789'],
            ['phone', ' 5512345678 '], ['phone', '٥٥١٢٣٤٥٦٧٨'],
            ['email', ''], ['email', 'invalid'], ['email', 'ana@@example.test'],
            ['password', ''], ['password', 'Abcde1!'], ['password', 'Abcdefghijk1!'],
            ['password', 'abcdef1!'], ['password', 'Abcdefg!'], ['password', 'Abcdef12'],
            ['password_confirmation', ''], ['password_confirmation', 'NoCoincide1!'],
        ];
    }

    #[DataProvider('uniqueFields')]
    public function test_duplicates_never_modify_or_promote_an_existing_client(string $field): void
    {
        $data = $this->answers();
        $client = User::factory()->create([$field => $data[$field]]);
        $before = $client->fresh()->getAttributes();
        $this->prompts()->assertFailed();
        $this->assertSame($before, $client->fresh()->getAttributes());
        $this->assertSame(Role::CLIENTE, $client->fresh()->role->name);
        $this->assertDatabaseCount('users', 1);
    }

    public static function uniqueFields(): array
    {
        return [['email'], ['phone']];
    }

    public function test_operator_can_cancel_without_creating_an_account(): void
    {
        $this->prompts()->expectsConfirmation('¿Confirmas crear esta nueva cuenta de administrador local?', 'no')
            ->expectsOutputToContain('Creación cancelada.')->assertSuccessful();
        $this->assertDatabaseEmpty('users');
    }

    public function test_insert_failure_rolls_back_without_displaying_or_logging_sensitive_details(): void
    {
        $secret = $this->password;
        Log::spy();
        User::created(function () use ($secret): void {
            throw new \RuntimeException('Sensitive detail: '.$secret);
        });

        $this->prompts()->expectsConfirmation('¿Confirmas crear esta nueva cuenta de administrador local?', 'yes')
            ->doesntExpectOutputToContain($secret)->assertFailed();
        $this->assertDatabaseEmpty('users');
        foreach (['error', 'warning', 'debug', 'info', 'log'] as $method) {
            Log::shouldNotHaveReceived($method);
        }
    }

    #[DataProvider('forbiddenEnvironments')]
    public function test_command_is_blocked_outside_local(string $environment): void
    {
        $this->app->instance('env', $environment);
        $this->artisan('wbalance:create-admin')->expectsOutputToContain('solo está disponible en el entorno local')->assertFailed();
        $this->assertDatabaseEmpty('users');
    }

    public static function forbiddenEnvironments(): array
    {
        return [['production'], ['staging'], ['testing']];
    }

    public function test_noninteractive_execution_is_rejected(): void
    {
        $this->artisan('wbalance:create-admin', ['--no-interaction' => true])
            ->expectsOutputToContain('Se requiere una terminal interactiva')->assertFailed();
        $this->assertDatabaseEmpty('users');
    }

    public function test_a_missing_administrator_role_does_not_create_users_or_roles(): void
    {
        Role::where('name', Role::ADMINISTRADOR)->delete();
        $this->artisan('wbalance:create-admin')->expectsOutputToContain('El rol administrador no existe.')->assertFailed();
        $this->assertDatabaseEmpty('users');
        $this->assertDatabaseCount('roles', 1);
    }

    public function test_role_selection_uses_its_name_instead_of_a_fixed_id(): void
    {
        $role = Role::where('name', Role::ADMINISTRADOR)->sole();
        $role->id = 173;
        $role->save();
        $this->prompts()->expectsConfirmation('¿Confirmas crear esta nueva cuenta de administrador local?', 'yes')->assertSuccessful();
        $this->assertSame(173, User::sole()->role_id);
        $this->assertSame(Role::ADMINISTRADOR, User::sole()->role->name);
    }

    public function test_password_spaces_are_preserved_exactly_as_in_public_registration(): void
    {
        $password = $this->password.' ';
        $this->prompts(['password' => $password, 'password_confirmation' => $password])
            ->expectsConfirmation('¿Confirmas crear esta nueva cuenta de administrador local?', 'yes')->assertSuccessful();
        $this->assertTrue(Hash::check($password, User::sole()->password));
        $this->assertFalse(Hash::check(trim($password), User::sole()->password));
    }

    public function test_questions_disable_visible_password_fallback_and_default_confirmation_is_no(): void
    {
        $input = new ArrayInput([]);
        $buffer = new BufferedOutput;
        $output = Mockery::mock(OutputStyle::class.'[askQuestion]', [$input, $buffer]);
        $answers = array_values($this->answers());
        $index = 0;
        $hidden = 0;
        $output->shouldReceive('askQuestion')->times(7)->andReturnUsing(function (Question $question) use (&$index, &$hidden, $answers) {
            if ($question instanceof ConfirmationQuestion) {
                $this->assertFalse($question->getDefault());

                return false;
            }

            $this->assertFalse($question->isTrimmable());
            if ($index >= 4) {
                $this->assertTrue($question->isHidden());
                $this->assertFalse($question->isHiddenFallback());
                $hidden++;
            }

            return $answers[$index++];
        });
        $command = $this->app->make(CreateAdministrator::class);
        $command->setLaravel($this->app);
        $this->assertSame(Command::SUCCESS, $command->run($input, $output));
        $this->assertSame(2, $hidden);
        $this->assertStringNotContainsString($this->password, $buffer->fetch());
        $this->assertDatabaseEmpty('users');
    }

    #[DataProvider('terminalLineEndings')]
    public function test_terminal_line_endings_are_removed_before_validation_without_changing_the_answers(
        string $visibleEnding,
        string $passwordEnding,
        string $confirmationEnding,
    ): void {
        $expected = $this->answers([
            'password' => ' '.$this->password.' ',
            'password_confirmation' => ' '.$this->password.' ',
        ]);
        $stream = fopen('php://memory', 'r+');
        $visibleInput = implode($visibleEnding, [
            '  '.$expected['name'].'  ', '  '.$expected['last_name'].'  ',
            $expected['phone'], $expected['email'],
        ]).$visibleEnding;
        if (function_exists('sapi_windows_cp_conv')) {
            // Symfony reads visible Windows input in the terminal's OEM page.
            $visibleInput = sapi_windows_cp_conv(sapi_windows_cp_get(), sapi_windows_cp_get('oem'), $visibleInput);
        }
        fwrite($stream, $visibleInput);
        rewind($stream);

        $input = new ArrayInput([]);
        $input->setStream($stream);
        $buffer = new BufferedOutput;
        $output = Mockery::mock(OutputStyle::class.'[askQuestion]', [$input, $buffer]);
        // Use Symfony's real stream reader for visible fields. Simulate only the
        // OS-specific hidden-input helper, which requires a physical terminal.
        $output->shouldReceive('askQuestion')->with(Mockery::on(
            fn (Question $question) => ! $question->isHidden() && ! $question instanceof ConfirmationQuestion
        ))->times(4)->passthru();
        $hiddenAnswers = [
            $expected['password'].$passwordEnding,
            $expected['password_confirmation'].$confirmationEnding,
        ];
        $output->shouldReceive('askQuestion')->with(Mockery::on(
            fn (Question $question) => $question->isHidden() && ! $question->isHiddenFallback() && ! $question->isTrimmable()
        ))->twice()->andReturn(...$hiddenAnswers);
        $output->shouldReceive('askQuestion')->with(Mockery::type(ConfirmationQuestion::class))
            ->once()->andReturn(false);

        $factory = Validator::getFacadeRoot();
        $captured = null;
        Validator::shouldReceive('make')->once()->andReturnUsing(
            function (array $data, array $rules, array $messages) use ($factory, &$captured) {
                $captured = $data;

                return $factory->make($data, $rules, $messages);
            }
        );

        try {
            $command = $this->app->make(CreateAdministrator::class);
            $command->setLaravel($this->app);
            $status = $command->run($input, $output);
        } finally {
            fclose($stream);
        }

        foreach ($expected as $field => $value) {
            // Do not include captured passwords in assertion failure output.
            $this->assertTrue(isset($captured[$field]) && hash_equals($value, $captured[$field]), $field.' changed before validation.');
        }
        $this->assertSame(Command::SUCCESS, $status);
        $text = $buffer->fetch();
        $this->assertStringContainsString('Creación cancelada.', $text);
        $this->assertStringNotContainsString($this->password, $text);
        $this->assertDatabaseEmpty('users');
    }

    public static function terminalLineEndings(): array
    {
        return [
            'Windows CRLF' => ["\r\n", "\r\n", "\r\n"],
            'Unix LF' => ["\n", "\n", "\n"],
            'different visible and hidden readers' => ["\r\n", "\n", "\r\n"],
            'hidden helper without terminators' => ["\r\n", '', ''],
        ];
    }

    public function test_creation_stops_if_hidden_input_is_not_supported(): void
    {
        $input = new ArrayInput([]);
        $buffer = new BufferedOutput;
        $output = Mockery::mock(OutputStyle::class.'[askQuestion]', [$input, $buffer]);
        $answers = array_values($this->answers());
        $index = 0;
        $output->shouldReceive('askQuestion')->times(5)->andReturnUsing(function () use (&$index, $answers) {
            if ($index === 4) {
                throw new \RuntimeException('Hidden input unavailable.');
            }

            return $answers[$index++];
        });
        $command = $this->app->make(CreateAdministrator::class);
        $command->setLaravel($this->app);
        $this->assertSame(Command::FAILURE, $command->run($input, $output));
        $this->assertStringNotContainsString($this->password, $buffer->fetch());
        $this->assertDatabaseEmpty('users');
    }

    public function test_command_does_not_accept_password_or_role_arguments_or_a_force_option(): void
    {
        $definition = $this->app->make(CreateAdministrator::class)->getDefinition();
        foreach (['password', 'email', 'role', 'role_id', 'force'] as $name) {
            $this->assertFalse($definition->hasArgument($name));
            $this->assertFalse($definition->hasOption($name));
        }
    }

    public function test_created_account_can_verify_login_and_access_only_the_administrator_route(): void
    {
        $this->prompts()->expectsConfirmation('¿Confirmas crear esta nueva cuenta de administrador local?', 'yes')->assertSuccessful();
        $admin = User::sole();
        $this->app->instance('env', 'testing');
        $this->post('/login', ['email' => $admin->email, 'password' => $this->password])->assertRedirect('/email/verify');
        $this->get('/administrador')->assertRedirect('/email/verify');
        $this->post(route('verification.send'))->assertRedirect('/email/verify');
        $notification = Notification::sent($admin, VerifyEmail::class)->sole();
        $this->get($notification->toMail($admin)->actionUrl)->assertRedirect('/administrador');
        $this->get('/administrador')->assertOk();
        $this->get('/cliente')->assertForbidden();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/administrador')->assertRedirect('/login');
        $this->get('/cliente')->assertRedirect('/login');

        $client = User::factory()->create();
        $this->actingAs($client)->get('/administrador')->assertForbidden();
        $this->get('/cliente')->assertOk();
    }
}
