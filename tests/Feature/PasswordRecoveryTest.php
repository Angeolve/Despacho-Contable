<?php

namespace Tests\Feature;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\Concerns\UsesIsolatedDatabase;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use UsesIsolatedDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateIsolatedDatabase();
        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_replace([
            'email' => 'ana@example.test', 'password' => Hash::make('Anterior1!'),
        ], $overrides));
    }

    private function payload(string $token, array $overrides = []): array
    {
        return array_replace([
            'email' => 'ana@example.test', 'token' => $token,
            'password' => 'Nueva123!', 'password_confirmation' => 'Nueva123!',
        ], $overrides);
    }

    public function test_recovery_views_reuse_accessible_forms_and_the_login_link_is_functional(): void
    {
        $this->get('/login')->assertOk()->assertSee('href="'.route('password.request').'"', false)
            ->assertDontSee('data-preview-link', false);
        $this->get('/forgot-password')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Recuperar contraseña')->assertSee('name="_token"', false)
            ->assertSee('name="email"', false)->assertSee('method="post"', false);
        $this->get('/reset-password/'.str_repeat('a', 64).'?email=ana%40example.test')->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('value="ana@example.test"', false)->assertSee('autocomplete="new-password"', false)
            ->assertSee('data-password-toggle', false)->assertSee('aria-describedby="reset-password-requirements', false)
            ->assertSee('name="password_confirmation"', false)->assertSee('name="_token"', false);
    }

    public function test_request_sends_a_native_notification_and_stores_only_a_hashed_token(): void
    {
        $user = $this->user();
        $hash = $user->password;
        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect('/forgot-password')
            ->assertSessionHas('status')->assertSessionHasNoErrors()->assertSessionHasInput('email', $user->email);
        $notification = Notification::sent($user, ResetPassword::class)->sole();
        $this->assertSame(['mail'], $notification->via($user));
        $this->assertSame(64, strlen($notification->token));
        $record = DB::table('password_reset_tokens')->sole();
        $this->assertNotSame($notification->token, $record->token);
        $this->assertTrue(Hash::check($notification->token, $record->token));
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertGuest();
    }

    public function test_unknown_and_registered_addresses_receive_identical_public_responses_even_when_repeated(): void
    {
        $user = $this->user();
        $statuses = [];
        foreach ([$user->email, 'noexiste@example.test', $user->email, 'noexiste@example.test'] as $email) {
            $this->post('/forgot-password', ['email' => $email])->assertRedirect('/forgot-password')->assertSessionHasNoErrors();
            $statuses[] = session('status');
        }
        $this->assertCount(1, array_unique($statuses));
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
        $this->assertDatabaseCount('password_reset_tokens', 1);
        $this->assertDatabaseCount('users', 1);
    }

    #[DataProvider('invalidEmails')]
    public function test_recovery_email_is_validated_and_can_render_errors_safely(mixed $email): void
    {
        $this->from('https://attacker.example/')->post('/forgot-password', ['email' => $email])
            ->assertRedirect('/forgot-password')->assertSessionHasErrors('email');
        $this->get('/forgot-password')->assertOk()->assertSee('role="alert"', false);
        Notification::assertNothingSent();
        $this->assertDatabaseEmpty('password_reset_tokens');
    }

    public static function invalidEmails(): array
    {
        return [[null], [''], ['   '], ['invalid'], [['ana@example.test']], [str_repeat('a', 256).'@example.test']];
    }

    public function test_request_cooldown_expires_and_new_links_invalidate_old_links(): void
    {
        $this->freezeTime();
        $user = $this->user();
        $this->post('/forgot-password', ['email' => $user->email]);
        $first = Notification::sent($user, ResetPassword::class)->sole()->token;
        $this->post('/forgot-password', ['email' => $user->email]);
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
        $this->assertTrue(Password::tokenExists($user, $first));

        $this->travel(61)->seconds();
        $this->post('/forgot-password', ['email' => $user->email]);
        Notification::assertSentToTimes($user, ResetPassword::class, 2);
        $second = Notification::sent($user, ResetPassword::class)->last()->token;
        $this->assertNotSame($first, $second);
        $this->assertFalse(Password::tokenExists($user, $first));
        $this->assertTrue(Password::tokenExists($user, $second));
    }

    public function test_cooldown_cannot_be_bypassed_by_changing_case_or_ip(): void
    {
        $user = $this->user();
        $this->post('/forgot-password', ['email' => $user->email]);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2'])
            ->post('/forgot-password', ['email' => 'ANA@example.test'])->assertSessionHasNoErrors();
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_request_rate_limit_is_independent_of_account_existence(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/forgot-password', ['email' => 'persona'.$i.'@example.test'])->assertRedirect('/forgot-password');
        }
        $this->post('/forgot-password', ['email' => 'otra@example.test'])->assertStatus(429);
        Notification::assertNothingSent();
    }

    public function test_link_requests_do_not_exhaust_the_reset_or_registration_limits(): void
    {
        $user = $this->user();
        for ($i = 0; $i < 6; $i++) {
            $this->post('/forgot-password', ['email' => 'persona'.$i.'@example.test'])->assertRedirect('/forgot-password');
        }
        $this->post('/register', [])->assertSessionHasErrors('name');
        $this->post('/reset-password', $this->payload(Password::createToken($user)))->assertRedirect('/login');
        $this->post('/login', ['email' => $user->email, 'password' => 'Nueva123!'])->assertRedirect('/cliente');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_notification_has_spanish_content_and_a_trusted_origin_and_never_uses_the_log_mailer(): void
    {
        $user = $this->user();
        config(['app.url' => 'http://127.0.0.1:8000', 'mail.default' => 'log']);
        $notification = new ResetPassword(Password::createToken($user));
        $this->withHeader('Host', 'attacker.example')->get('/forgot-password');
        $mail = $notification->toMail($user);
        $this->assertSame('smtp', $mail->mailer);
        $this->assertSame('Restablece tu contraseña · W&A Balance', $mail->subject);
        $this->assertStringStartsWith('http://127.0.0.1:8000/reset-password/', $mail->actionUrl);
        $this->assertStringContainsString('email=ana%40example.test', $mail->actionUrl);
        $this->assertStringContainsString('60 minutos', implode(' ', $mail->outroLines));
    }

    public function test_native_mail_pipeline_renders_and_delivers_the_recovery_message(): void
    {
        // Use the real notification pipeline with an in-memory mail transport.
        Notification::swap(new ChannelManager($this->app));
        config(['mail.mailers.smtp' => ['transport' => 'array']]);
        $user = $this->user();
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors();
        $messages = Mail::mailer('smtp')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $message = $messages->sole()->getOriginalMessage();
        $this->assertSame('ana@example.test', $message->getTo()[0]->getAddress());
        $this->assertStringContainsString('Restablecer contraseña', $message->getHtmlBody());
        $this->assertStringContainsString('/reset-password/', $message->getHtmlBody());
    }

    public function test_smtp_failure_returns_the_same_generic_response_without_logging_secrets(): void
    {
        Password::shouldReceive('sendResetLink')->once()->andThrow(new TransportException('SECRET reset URL'));
        Log::spy();
        $this->post('/forgot-password', ['email' => 'ana@example.test'])
            ->assertRedirect('/forgot-password')->assertSessionHas('status')->assertSessionHasNoErrors();
        Log::shouldHaveReceived('warning')->once()->with('No se pudo enviar un correo de recuperación de contraseña.');
    }

    public function test_valid_reset_hashes_password_preserves_account_data_and_requires_fresh_login(): void
    {
        Event::fake([PasswordReset::class]);
        $user = $this->user();
        $before = $user->fresh()->getAttributes();
        $token = Password::createToken($user);
        $this->post('/reset-password', $this->payload($token, [
            'role_id' => Role::where('name', Role::ADMINISTRADOR)->sole()->id,
            'role' => Role::ADMINISTRADOR, 'name' => 'Manipulado', 'email_verified_at' => null,
            'redirect' => 'https://attacker.example/',
        ]))->assertRedirect('/login')->assertSessionHasNoErrors()->assertSessionHas('status');
        $fresh = $user->fresh();
        $this->assertTrue(Hash::check('Nueva123!', $fresh->password));
        $this->assertFalse(Hash::check('Anterior1!', $fresh->password));
        $this->assertNotSame($before['password'], $fresh->password);
        $this->assertNotSame($before['remember_token'], $fresh->remember_token);
        foreach (['role_id', 'name', 'last_name', 'phone', 'email', 'email_verified_at', 'privacy_policy_accepted_at', 'privacy_policy_version'] as $field) {
            $this->assertSame($before[$field], $fresh->getAttributes()[$field], $field);
        }
        $this->assertDatabaseEmpty('password_reset_tokens');
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
        Event::assertDispatchedTimes(PasswordReset::class, 1);
        $this->get('/login')->assertOk()->assertSee('Tu contraseña se restableció correctamente.');
        $this->post('/login', ['email' => $user->email, 'password' => 'Anterior1!'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => $user->email, 'password' => 'Nueva123!'])->assertRedirect('/cliente');
        $this->assertAuthenticatedAs($fresh);
    }

    public function test_reset_does_not_bypass_email_verification(): void
    {
        $user = $this->user(['email_verified_at' => null]);
        $this->post('/reset-password', $this->payload(Password::createToken($user)))->assertRedirect('/login');
        $this->assertNull($user->fresh()->email_verified_at);
        $this->post('/login', ['email' => $user->email, 'password' => 'Nueva123!'])->assertRedirect('/email/verify');
    }

    #[DataProvider('invalidPasswords')]
    public function test_invalid_passwords_and_confirmations_preserve_the_hash_and_token(string $field, mixed $value): void
    {
        $user = $this->user();
        $hash = $user->password;
        $token = Password::createToken($user);
        $this->from('https://attacker.example/')->post('/reset-password', $this->payload($token, [$field => $value]))
            ->assertRedirect(route('password.reset', ['token' => $token]))->assertSessionHasErrors($field)
            ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation')
            ->assertSessionMissing('_old_input.token');
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertTrue(Password::tokenExists($user, $token));
        $this->assertGuest();
    }

    public static function invalidPasswords(): array
    {
        return [
            ['password', null], ['password', ''], ['password', ['Nueva123!']],
            ['password', 'Abcde1!'], ['password', 'Abcdefghijk1!'], ['password', 'abcdef1!'],
            ['password', 'Abcdefg!'], ['password', 'Abcdef12'], ['password', 'Abcdef1 '],
            ['password_confirmation', null], ['password_confirmation', ''],
            ['password_confirmation', ['Nueva123!']], ['password_confirmation', 'Diferente1!'],
        ];
    }

    #[DataProvider('validPasswords')]
    public function test_password_boundaries_and_unicode_match_registration(string $password): void
    {
        $user = $this->user();
        $this->post('/reset-password', $this->payload(Password::createToken($user), [
            'password' => $password, 'password_confirmation' => $password,
        ]))->assertRedirect('/login')->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check($password, $user->fresh()->password));
    }

    public static function validPasswords(): array
    {
        return [['ABCDEF1!'], ['Abcdefghij1!'], ['Ñandú12!'], ['Abcde1😀x']];
    }

    public function test_password_and_confirmation_rules_are_identical_to_registration(): void
    {
        foreach (['password', 'password_confirmation'] as $field) {
            $this->assertSame((new RegisterRequest)->rules()[$field], (new ResetPasswordRequest)->rules()[$field]);
        }
    }

    public function test_invalid_expired_and_reused_tokens_never_change_the_password(): void
    {
        $this->freezeTime();
        $user = $this->user();
        $originalHash = $user->password;
        $token = Password::createToken($user);
        $this->post('/reset-password', $this->payload(str_repeat('0', 64)))->assertSessionHasErrors('token');
        $this->assertSame($originalHash, $user->fresh()->password);
        $this->travel(61)->minutes();
        $this->post('/reset-password', $this->payload($token))->assertSessionHasErrors('token');
        $this->assertSame($originalHash, $user->fresh()->password);

        $token = Password::createToken($user);
        $this->post('/reset-password', $this->payload($token))->assertRedirect('/login');
        $newHash = $user->fresh()->password;
        $this->post('/reset-password', $this->payload($token, [
            'password' => 'Otra1234!', 'password_confirmation' => 'Otra1234!',
        ]))->assertRedirect('/forgot-password')->assertSessionHasErrors('token');
        $this->assertSame($newHash, $user->fresh()->password);
    }

    public function test_token_cannot_be_used_for_another_or_nonexistent_account(): void
    {
        $user = $this->user();
        $other = $this->user(['email' => 'otra@example.test']);
        $token = Password::createToken($user);
        $hash = $other->password;
        $errors = [];
        foreach ([$other->email, 'noexiste@example.test'] as $email) {
            $this->post('/reset-password', $this->payload($token, ['email' => $email]))->assertSessionHasErrors('token');
            $errors[] = session('errors')->first('token');
        }
        $this->assertSame($errors[0], $errors[1]);
        $this->assertSame($hash, $other->fresh()->password);
        $this->assertTrue(Password::tokenExists($user, $token));
    }

    public function test_malformed_token_is_rejected_without_flashing_secrets(): void
    {
        $this->post('/reset-password', $this->payload('invalid'))->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('token')->assertSessionMissing('_old_input.token')
            ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
        $this->get('/forgot-password')->assertOk()->assertSee('El enlace de recuperación es inválido o ha vencido.');
    }

    public function test_reset_removes_only_the_target_users_database_sessions_and_blocks_replay(): void
    {
        config(['session.driver' => 'database', 'session.connection' => 'wbalance_testing']);
        $user = $this->user();
        $other = $this->user(['email' => 'otra@example.test']);
        $oldSessions = [Str::random(40), Str::random(40)];
        $otherSession = Str::random(40);
        foreach ([[$oldSessions[0], $user], [$oldSessions[1], $user], [$otherSession, $other]] as [$id, $owner]) {
            DB::table('sessions')->insert([
                'id' => $id, 'user_id' => $owner->id, 'last_activity' => now()->timestamp,
                'payload' => base64_encode(serialize([Auth::guard()->getName() => $owner->id])),
            ]);
        }
        $this->post('/reset-password', $this->payload(Password::createToken($user)))->assertRedirect('/login');
        foreach ($oldSessions as $id) {
            $this->assertDatabaseMissing('sessions', ['id' => $id]);
            Auth::forgetGuards();
            $this->withCookie(config('session.cookie'), $id)->get('/cliente')->assertRedirect('/login');
        }
        $this->assertDatabaseHas('sessions', ['id' => $otherSession, 'user_id' => $other->id]);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_native_session_middleware_rejects_a_stale_password_fingerprint(): void
    {
        $user = $this->user();
        $oldHash = $user->password;
        $this->post('/reset-password', $this->payload(Password::createToken($user)))->assertRedirect('/login');
        $this->actingAs($user->fresh())->withSession(['password_hash_web' => $oldHash])
            ->get('/cliente')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_stores_the_native_session_fingerprint_before_any_private_request(): void
    {
        $user = $this->user();
        $this->post('/login', ['email' => $user->email, 'password' => 'Anterior1!'])->assertRedirect('/cliente');
        $this->assertSame(Auth::guard()->hashPasswordForCookie($user->password), session('password_hash_web'));
    }

    #[DataProvider('csrfPaths')]
    public function test_both_recovery_posts_require_csrf(string $path): void
    {
        $this->app->instance('env', 'local');
        $this->post($path, $this->payload(str_repeat('a', 64)))->assertStatus(419);
        $this->assertDatabaseEmpty('users');
        Notification::assertNothingSent();
    }

    public static function csrfPaths(): array
    {
        return [['/forgot-password'], ['/reset-password']];
    }
}
