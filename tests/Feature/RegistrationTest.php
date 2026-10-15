<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\Concerns\UsesIsolatedDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use UsesIsolatedDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateIsolatedDatabase();
        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'María José', 'last_name' => 'Muñoz de la Cruz',
            'phone' => '5512345678', 'email' => 'maria@example.test',
            'password' => 'Abcdef1!', 'password_confirmation' => 'Abcdef1!', 'privacy' => '1',
        ], $overrides);
    }

    public function test_registration_stores_a_client_hash_and_server_side_consent(): void
    {
        $this->freezeTime();
        $this->post('/register', $this->payload())->assertRedirect(route('verification.notice'))->assertSessionHasNoErrors();
        $user = User::sole();
        $this->assertSame('María José', $user->name);
        $this->assertSame('Muñoz de la Cruz', $user->last_name);
        $this->assertSame('5512345678', $user->phone);
        $this->assertSame('maria@example.test', $user->email);
        $this->assertSame(Role::CLIENTE, $user->role->name);
        $this->assertTrue(Hash::check('Abcdef1!', $user->password));
        $this->assertNotSame('Abcdef1!', $user->password);
        $this->assertNull($user->email_verified_at);
        $this->assertSame(now()->getTimestamp(), $user->privacy_policy_accepted_at->getTimestamp());
        $this->assertSame(hash_file('sha256', public_path(config('privacy.document'))), $user->privacy_policy_version);
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get(route('verification.notice'))->assertOk()->assertSee('Verifica tu correo');
    }

    #[DataProvider('requiredFields')]
    public function test_every_registration_field_is_required(string $field): void
    {
        $data = $this->payload();
        unset($data[$field]);
        $this->from('/register')->post('/register', $data)->assertRedirect('/register')->assertSessionHasErrors($field);
        $this->assertDatabaseEmpty('users');
        $this->assertGuest();
        Notification::assertNothingSent();
    }

    public static function requiredFields(): array
    {
        return array_map(fn ($field) => [$field], ['name', 'last_name', 'phone', 'email', 'password', 'password_confirmation', 'privacy']);
    }

    #[DataProvider('invalidValues')]
    public function test_invalid_input_is_rejected_on_the_server(string $field, mixed $value): void
    {
        $this->from('/register')->post('/register', $this->payload([$field => $value]))->assertSessionHasErrors($field);
        $this->assertDatabaseEmpty('users');
        Notification::assertNothingSent();
    }

    public static function invalidValues(): array
    {
        return [
            ['name', 'Ana2'], ['name', 'Ana 😀'], ['name', 'Ana-María'], ['name', '   '],
            ['name', "\tAna"], ['name', ['Ana']], ['name', str_repeat('A', 256)],
            ['last_name', 'López3'], ['last_name', 'García!'], ['last_name', 'Muñoz😀'],
            ['phone', '551234567'], ['phone', '55123456789'], ['phone', '55 12345678'],
            ['phone', ' 5512345678 '], ['phone', '٥٥١٢٣٤٥٦٧٨'], ['phone', 5512345678],
            ['email', 'correo-invalido'], ['email', 'ana@@example.com'], ['email', ['ana@example.test']],
            ['password', 'Abcde1!'], ['password', 'Abcdefghijk1!'], ['password', 'abcdef1!'],
            ['password', 'Abcdefg!'], ['password', 'Abcdef12'], ['password', 'Abcdef1 '],
            ['password_confirmation', 'Abcdef2!'], ['privacy', '0'], ['privacy', false],
        ];
    }

    #[DataProvider('validNames')]
    public function test_unicode_names_and_multiple_surnames_are_accepted(string $name, string $lastName): void
    {
        $this->post('/register', $this->payload(['name' => $name, 'last_name' => $lastName]))->assertSessionHasNoErrors()->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', ['name' => $name, 'last_name' => $lastName]);
    }

    public static function validNames(): array
    {
        return [['Iñaki', 'Muñoz'], ['José', 'García de la Cruz'], ['李 明', '王']];
    }

    #[DataProvider('validPasswords')]
    public function test_password_boundaries_and_uppercase_without_lowercase_are_allowed(string $password): void
    {
        $this->post('/register', $this->payload(['password' => $password, 'password_confirmation' => $password]))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check($password, User::sole()->password));
    }

    public static function validPasswords(): array
    {
        return [['ABCDEF1!'], ['Abcdefghij1!'], ['Ñandú12!'], ['Abcde1😀x']];
    }

    #[DataProvider('uniqueFields')]
    public function test_duplicate_contact_details_are_rejected(string $field): void
    {
        User::factory()->create([$field => $this->payload()[$field]]);
        $this->post('/register', $this->payload())->assertSessionHasErrors($field);
        $this->assertDatabaseCount('users', 1);
        Notification::assertNothingSent();
    }

    public static function uniqueFields(): array
    {
        return [['email'], ['phone']];
    }

    public function test_role_verification_and_consent_cannot_be_injected_from_the_browser(): void
    {
        $administrator = Role::where('name', Role::ADMINISTRADOR)->sole();
        $this->post('/register', $this->payload([
            'role_id' => $administrator->id, 'role' => ['id' => $administrator->id],
            'email_verified_at' => '2000-01-01', 'remember_token' => 'attacker',
            'privacy_policy_accepted_at' => '2000-01-01', 'privacy_policy_version' => 'attacker',
        ]))->assertSessionHasNoErrors();
        $user = User::sole();
        $this->assertSame(Role::CLIENTE, $user->role->name);
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->remember_token);
        $this->assertSame(config('privacy.version'), $user->privacy_policy_version);
        $this->assertFalse($user->privacy_policy_accepted_at->isSameDay('2000-01-01'));
    }

    public function test_errors_preserve_contact_details_but_never_flash_passwords(): void
    {
        $this->from('/register')->post('/register', $this->payload(['email' => 'invalid']))
            ->assertSessionHasErrors('email')->assertSessionHasInput('name', 'María José')
            ->assertSessionHasInput('last_name', 'Muñoz de la Cruz')->assertSessionHasInput('phone', '5512345678')
            ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
        $this->get('/register')->assertOk()->assertSee('value="5512345678"', false)
            ->assertSee('Ingresa un correo electrónico válido.')->assertDontSee('value="Abcdef1!"', false);
    }

    public function test_signed_verification_completes_the_flow_and_unlocks_verified_routes(): void
    {
        $this->post('/register', $this->payload());
        $user = User::sole();
        $this->get(route('registration.complete'))->assertRedirect(route('verification.notice'));
        $notification = Notification::sent($user, VerifyEmail::class)->first();
        $url = $notification->toMail($user)->actionUrl;
        Event::fake([Verified::class]);
        $this->get($url)->assertRedirect(route('registration.complete'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class);
        $this->get(route('registration.complete'))->assertOk()->assertSee('Registro confirmado');
        $this->get($url)->assertRedirect(route('registration.complete'));
    }

    public function test_expired_tampered_and_other_user_verification_links_are_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();
        $this->actingAs($user);
        $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($expired)->assertForbidden();
        $otherUrl = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $other->id, 'hash' => sha1($other->email)]);
        $this->get($otherUrl)->assertForbidden();
        $this->get($otherUrl.'&extra=tampered')->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertNull($other->fresh()->email_verified_at);
    }

    public function test_verification_routes_require_the_registration_session(): void
    {
        $this->get(route('verification.notice'))->assertRedirect(route('login'));
        $this->post(route('verification.send'))->assertRedirect(route('login'));
        $this->get(route('registration.complete'))->assertRedirect(route('login'));
    }

    public function test_mail_failure_keeps_one_unverified_account_and_allows_resending(): void
    {
        Event::forget(Registered::class);
        Event::listen(Registered::class, function () {
            throw new TransportException('SMTP test failure');
        });
        $this->post('/register', $this->payload())->assertRedirect(route('verification.notice'))->assertSessionHas('mail_error');
        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email_verified_at);
        $this->get(route('verification.notice'))->assertOk()->assertSee('Tu cuenta se creó');
        $this->from(route('verification.notice'))->post(route('verification.send'))->assertSessionHas('verification_sent');
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_resending_verification_mail_is_throttled(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);
        for ($i = 0; $i < 6; $i++) {
            $this->from(route('verification.notice'))->post(route('verification.send'))->assertRedirect(route('verification.notice'));
        }
        $this->post(route('verification.send'))->assertStatus(429);
        Notification::assertSentToTimes($user, VerifyEmail::class, 6);
    }

    public function test_csrf_is_required_outside_the_testing_bypass(): void
    {
        $this->app->instance('env', 'local');
        $this->post('/register', $this->payload())->assertStatus(419);
        $this->assertDatabaseEmpty('users');
    }

    public function test_the_official_pdf_is_public_and_unchanged(): void
    {
        $response = $this->get(route('privacy.notice'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame(config('privacy.version'), hash_file('sha256', $response->baseResponse->getFile()->getPathname()));
        $this->assertSame(hash_file('sha256', base_path('requerimientos/Aviso de Privacidad - W&A Balance.pdf')), config('privacy.version'));
        $this->get('/register')->assertSee('target="_blank"', false)->assertSee(route('privacy.notice'));
    }

    public function test_registration_stops_if_the_pdf_does_not_match_its_approved_version(): void
    {
        config(['privacy.version' => str_repeat('0', 64)]);
        $this->post('/register', $this->payload())->assertStatus(503);
        $this->assertDatabaseEmpty('users');
        Notification::assertNothingSent();
    }
}
