<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\UsesIsolatedDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use UsesIsolatedDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateIsolatedDatabase();
        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    private function user(string $role = Role::CLIENTE, bool $verified = true): User
    {
        $user = User::factory()->make([
            'email' => 'persona@example.test',
            'password' => Hash::make('Abcdef1!'),
            'email_verified_at' => $verified ? now() : null,
        ]);
        $user->role()->associate(Role::where('name', $role)->sole());
        $user->save();

        return $user;
    }

    private function credentials(array $overrides = []): array
    {
        return array_replace(['email' => 'persona@example.test', 'password' => 'Abcdef1!'], $overrides);
    }

    public function test_login_uses_a_post_form_with_csrf_and_existing_password_controls(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('method="post"', false)->assertSee('name="_token"', false)
            ->assertSee('name="email"', false)->assertSee('name="password"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('data-password-toggle', false)->assertSee('aria-controls="password"', false)
            ->assertDontSee('data-login-preview', false);
    }

    #[DataProvider('accountDestinations')]
    public function test_valid_credentials_redirect_by_stored_role_and_verification(string $role, bool $verified, string $destination): void
    {
        $user = $this->user($role, $verified);
        $hash = $user->password;
        $this->post('/login', $this->credentials())->assertRedirect($destination)->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertDatabaseCount('users', 1);
        Notification::assertNothingSent();
    }

    public static function accountDestinations(): array
    {
        return [
            'verified client' => [Role::CLIENTE, true, '/cliente'],
            'verified administrator' => [Role::ADMINISTRADOR, true, '/administrador'],
            'unverified client' => [Role::CLIENTE, false, '/email/verify'],
            'unverified administrator' => [Role::ADMINISTRADOR, false, '/email/verify'],
        ];
    }

    #[DataProvider('incorrectCredentials')]
    public function test_invalid_credentials_use_one_generic_error_and_never_flash_passwords(array $credentials): void
    {
        $this->user();
        $message = 'El correo electrónico o la contraseña son incorrectos.';
        $this->from('/login')->post('/login', $credentials)
            ->assertRedirect('/login')->assertSessionHasErrors(['email' => $message])
            ->assertSessionHasInput('email', $credentials['email'])
            ->assertSessionMissing('_old_input.password');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);

        $this->get('/login')->assertOk()->assertSee($message)
            ->assertSee('value="'.$credentials['email'].'"', false)
            ->assertSee('id="email-feedback"', false)->assertSee('role="alert"', false)
            ->assertSee('aria-describedby="email-feedback"', false)
            ->assertDontSee('value="'.$credentials['password'].'"', false);
    }

    public static function incorrectCredentials(): array
    {
        return [
            'incorrect password' => [['email' => 'persona@example.test', 'password' => 'Incorrecta9!']],
            'nonexistent email' => [['email' => 'noexiste@example.test', 'password' => 'Incorrecta9!']],
        ];
    }

    #[DataProvider('invalidLoginFields')]
    public function test_required_and_malformed_fields_are_rejected_on_the_server(string $field, mixed $value): void
    {
        $this->post('/login', $this->credentials([$field => $value]))
            ->assertRedirect('/login')->assertSessionHasErrors($field)
            ->assertSessionMissing('_old_input.password');
        $this->assertGuest();
        $this->assertDatabaseEmpty('users');
    }

    #[DataProvider('malformedLoginFields')]
    public function test_array_input_is_rejected_and_login_can_render_after_the_redirect(string $field, array $value): void
    {
        $this->from('/login')->post('/login', $this->credentials([$field => $value]))
            ->assertRedirect('/login')
            ->assertSessionHasErrors([$field => 'Este campo debe contener texto válido.'])
            ->assertSessionMissing('_old_input.password');
        $this->assertGuest();
        $this->assertDatabaseEmpty('users');
        Notification::assertNothingSent();

        $response = $this->get('/login')->assertOk()
            ->assertSee('Este campo debe contener texto válido.')
            ->assertDontSee('malformed-array-value');
        $document = new \DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $expectedEmail = $field === 'email' ? '' : $this->credentials()['email'];
        $this->assertSame($expectedEmail, $xpath->query('//input[@name="email"]')->item(0)->getAttribute('value'));
        $this->assertSame('', $xpath->query('//input[@name="password"]')->item(0)->getAttribute('value'));
    }

    public static function malformedLoginFields(): array
    {
        return [
            'email indexed array' => ['email', ['malformed-array-value']],
            'email nested array' => ['email', ['nested' => ['malformed-array-value']]],
            'password indexed array' => ['password', ['malformed-array-value']],
            'password nested array' => ['password', ['nested' => ['malformed-array-value']]],
        ];
    }

    public static function invalidLoginFields(): array
    {
        return [
            ['email', null], ['email', ''], ['email', '   '], ['email', 'correo-invalido'],
            ['email', ['persona@example.test']], ['email', str_repeat('a', 256).'@example.test'],
            ['password', null], ['password', ''], ['password', '   '], ['password', ['Abcdef1!']],
        ];
    }

    public function test_login_preserves_password_spaces_and_does_not_apply_registration_complexity_rules(): void
    {
        $user = $this->user();
        $user->password = ' contraseña anterior ';
        $user->save();
        $this->post('/login', $this->credentials(['password' => ' contraseña anterior ']))->assertRedirect('/cliente');
        $this->assertAuthenticatedAs($user);
    }

    public function test_five_failed_attempts_block_even_correct_credentials_until_the_limit_expires(): void
    {
        $this->freezeTime();
        $this->user();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', $this->credentials(['password' => 'Incorrecta9!']))->assertSessionHasErrors('email');
        }

        $this->post('/login', $this->credentials())->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Demasiados intentos. Inténtalo de nuevo en 60 segundos.']);
        $this->assertGuest();
        $this->travel(61)->seconds();
        $this->post('/login', $this->credentials())->assertRedirect('/cliente')->assertSessionHasNoErrors();
        $this->assertAuthenticated();
    }

    public function test_changing_email_case_does_not_bypass_the_attempt_limit(): void
    {
        $this->user();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', $this->credentials(['email' => 'PERSONA@example.test', 'password' => 'Incorrecta9!']));
        }
        $this->post('/login', $this->credentials())->assertSessionHasErrors('email');
        $this->assertStringContainsString('Demasiados intentos.', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_successful_login_clears_previous_failed_attempts(): void
    {
        $this->user();
        for ($cycle = 0; $cycle < 2; $cycle++) {
            for ($i = 0; $i < 4; $i++) {
                $this->post('/login', $this->credentials(['password' => 'Incorrecta9!']))->assertSessionHasErrors('email');
            }
            $this->post('/login', $this->credentials())->assertRedirect('/cliente');
            $this->post('/logout')->assertRedirect('/login');
        }
    }

    public function test_ip_limit_also_blocks_attempts_that_rotate_email_addresses(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->post('/login', $this->credentials(['email' => 'persona'.$i.'@example.test']))->assertRedirect('/login');
        }
        $this->post('/login', $this->credentials())->assertStatus(429);
        $this->assertGuest();
    }

    public function test_login_rotates_session_id_and_csrf_token_and_destroys_the_old_database_session(): void
    {
        config(['session.driver' => 'database', 'session.connection' => 'wbalance_testing']);
        $user = $this->user();
        $this->get('/login')->assertOk();
        $oldId = session()->getId();
        $oldToken = session()->token();
        $this->assertDatabaseHas('sessions', ['id' => $oldId]);

        $this->withCookie(config('session.cookie'), $oldId)->post('/login', $this->credentials())->assertRedirect('/cliente');
        $this->assertNotSame($oldId, session()->getId());
        $this->assertNotSame($oldToken, session()->token());
        $this->assertDatabaseMissing('sessions', ['id' => $oldId]);
        $this->assertDatabaseHas('sessions', ['id' => session()->getId(), 'user_id' => $user->id]);
    }

    public function test_logout_invalidates_session_rotates_csrf_and_blocks_private_routes(): void
    {
        config(['session.driver' => 'database', 'session.connection' => 'wbalance_testing']);
        $this->user();
        $this->post('/login', $this->credentials())->assertRedirect('/cliente');
        $oldId = session()->getId();
        $oldToken = session()->token();
        $this->withSession(['private_marker' => 'must be removed']);

        $this->withCookie(config('session.cookie'), $oldId)->post('/logout')
            ->assertRedirect('/login')->assertSessionMissing('private_marker');
        $this->assertGuest();
        $this->assertNotSame($oldId, session()->getId());
        $this->assertNotSame($oldToken, session()->token());
        $this->assertDatabaseMissing('sessions', ['id' => $oldId]);
        $this->get('/cliente')->assertRedirect('/login');
        $this->get('/administrador')->assertRedirect('/login');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_logout_requires_post_and_is_available_before_verification(): void
    {
        $user = $this->user(verified: false);
        $this->actingAs($user)->get('/logout')->assertStatus(405);
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    #[DataProvider('privatePaths')]
    public function test_guests_are_redirected_to_login_for_private_routes(string $path): void
    {
        $this->get($path)->assertRedirect('/login');
    }

    public static function privatePaths(): array
    {
        return [['/cliente'], ['/administrador'], ['/email/verify'], ['/registro/completado']];
    }

    #[DataProvider('roleAccess')]
    public function test_private_routes_enforce_the_persisted_role(string $role, string $allowed, string $forbidden): void
    {
        $user = $this->user($role);
        $other = User::factory()->create();
        $this->actingAs($user)->get($allowed)->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('El panel se desarrollará posteriormente.')
            ->assertSee('action="'.route('logout').'"', false)
            ->assertDontSee($other->email)->assertDontSee($other->password);
        $this->get($forbidden.'?role='.$role.'&role_id='.$user->role_id)->assertForbidden();
    }

    public static function roleAccess(): array
    {
        return [
            [Role::CLIENTE, '/cliente', '/administrador'],
            [Role::ADMINISTRADOR, '/administrador', '/cliente'],
        ];
    }

    public function test_role_ids_are_not_assumed_to_be_fixed_numbers(): void
    {
        $client = Role::where('name', Role::CLIENTE)->sole();
        $client->id = 173;
        $client->save();
        $user = $this->user();
        $this->assertSame(173, $user->role_id);
        $this->post('/login', $this->credentials())->assertRedirect('/cliente');
        $this->get('/cliente')->assertOk();
        $this->get('/administrador')->assertForbidden();
    }

    public function test_an_unknown_role_fails_closed_for_both_private_routes(): void
    {
        $user = $this->user();
        $user->role()->associate(Role::create(['name' => 'sin_acceso']));
        $user->save();
        $this->post('/login', $this->credentials())->assertForbidden();
        $this->get('/cliente')->assertForbidden();
        $this->get('/administrador')->assertForbidden();
        $this->post('/logout')->assertRedirect('/login');
    }

    public function test_manipulated_login_fields_cannot_change_role_or_verification_or_redirect(): void
    {
        $user = $this->user();
        $admin = Role::where('name', Role::ADMINISTRADOR)->sole();
        $this->withSession(['url.intended' => 'https://attacker.example/']);
        $this->post('/login', $this->credentials([
            'role_id' => $admin->id, 'role' => Role::ADMINISTRADOR,
            'redirect' => 'https://attacker.example/', 'next' => '//attacker.example/',
            'email_verified_at' => '2000-01-01', 'remember' => true,
        ]))->assertRedirect('/cliente')->assertSessionMissing('url.intended');
        $this->assertSame(Role::CLIENTE, $user->fresh()->role->name);
        $this->assertFalse($user->fresh()->email_verified_at->isSameDay('2000-01-01'));
        $this->get('/administrador')->assertForbidden();
    }

    public function test_failed_login_ignores_an_external_referrer(): void
    {
        $this->from('https://attacker.example/')->post('/login', $this->credentials())
            ->assertRedirect('/login')->assertSessionHasErrors('email');
    }

    #[DataProvider('accountDestinations')]
    public function test_authenticated_users_visiting_guest_pages_return_to_their_destination(string $role, bool $verified, string $destination): void
    {
        $this->actingAs($this->user($role, $verified));
        $this->get('/login')->assertRedirect($destination);
        $this->get('/register')->assertRedirect($destination);
        $this->post('/login', $this->credentials())->assertRedirect($destination);
    }

    #[DataProvider('roleAccess')]
    public function test_unverified_accounts_cannot_access_private_pages(string $role, string $allowed, string $forbidden): void
    {
        $this->actingAs($this->user($role, false));
        $this->get($allowed)->assertRedirect('/email/verify');
        $this->get($forbidden)->assertRedirect('/email/verify');
        $this->get('/registro/completado')->assertRedirect('/email/verify');
        $this->get('/email/verify')->assertOk()->assertSee('Reenviar enlace de verificación')->assertSee('Cerrar sesión');
    }

    public function test_login_verification_resend_and_signed_link_preserve_the_existing_client_flow(): void
    {
        $user = $this->user(verified: false);
        $this->post('/login', $this->credentials())->assertRedirect('/email/verify');
        $this->from('https://attacker.example/')->post(route('verification.send'))->assertRedirect('/email/verify');
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get('/cliente')->assertRedirect('/email/verify');

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($url)->assertRedirect('/registro/completado');
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get('/registro/completado')->assertOk()->assertSee('Registro confirmado')->assertSee('Ir a mi espacio');
        $this->get('/cliente')->assertOk();
        $this->get('/administrador')->assertForbidden();
    }

    public function test_public_registration_logout_login_and_verification_work_together(): void
    {
        $this->post('/register', [
            ...$this->credentials(), 'password_confirmation' => 'Abcdef1!',
            'name' => 'María José', 'last_name' => 'Muñoz de la Cruz',
            'phone' => '5512345678', 'privacy' => '1',
            'role_id' => Role::where('name', Role::ADMINISTRADOR)->sole()->id,
        ])->assertRedirect('/email/verify')->assertSessionHasNoErrors();
        $user = User::sole();
        $this->assertSame(Role::CLIENTE, $user->role->name);
        $this->assertNotNull($user->privacy_policy_accepted_at);
        $this->assertTrue(Hash::check('Abcdef1!', $user->password));

        $this->post('/logout')->assertRedirect('/login');
        $this->post('/login', $this->credentials())->assertRedirect('/email/verify');
        $this->get('/cliente')->assertRedirect('/email/verify');
        $notification = Notification::sent($user, VerifyEmail::class)->first();
        $this->get($notification->toMail($user)->actionUrl)->assertRedirect('/registro/completado');
        $this->get('/registro/completado')->assertOk();
        $this->get('/cliente')->assertOk();
        $this->post('/logout')->assertRedirect('/login');
        $this->post('/login', $this->credentials())->assertRedirect('/cliente');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_verification_resend_is_rate_limited_after_login(): void
    {
        $user = $this->user(verified: false);
        $this->post('/login', $this->credentials())->assertRedirect('/email/verify');
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('verification.send'))->assertRedirect('/email/verify');
        }
        $this->post(route('verification.send'))->assertStatus(429);
        Notification::assertSentToTimes($user, VerifyEmail::class, 6);
    }

    public function test_administrator_verification_returns_to_the_administrator_route(): void
    {
        $user = $this->user(Role::ADMINISTRADOR, false);
        $this->post('/login', $this->credentials())->assertRedirect('/email/verify');
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($url)->assertRedirect('/administrador');
        $this->get('/administrador')->assertOk();
        $this->get('/registro/completado')->assertForbidden();
    }

    #[DataProvider('csrfRoutes')]
    public function test_authentication_posts_require_csrf_outside_the_test_bypass(string $path, bool $authenticated): void
    {
        $user = $this->user(verified: false);
        if ($authenticated) {
            $this->actingAs($user);
        }
        $this->app->instance('env', 'local');
        $this->post($path, $this->credentials())->assertStatus(419);
        $this->assertDatabaseCount('users', 1);
        Notification::assertNothingSent();
    }

    public static function csrfRoutes(): array
    {
        return [['/login', false], ['/logout', true], ['/email/verification-notification', true]];
    }

    public function test_valid_csrf_tokens_allow_login_and_logout(): void
    {
        $user = $this->user();
        $this->app->instance('env', 'local');
        $this->get('/login')->assertOk();
        $this->withCookie(config('session.cookie'), session()->getId())
            ->post('/login', $this->credentials(['_token' => session()->token()]))->assertRedirect('/cliente');
        $this->assertAuthenticatedAs($user);
        $this->withCookie(config('session.cookie'), session()->getId())
            ->post('/logout', ['_token' => session()->token()])->assertRedirect('/login');
        $this->assertGuest();
    }
}
