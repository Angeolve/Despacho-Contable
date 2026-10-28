<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\UsesIsolatedDatabase;
use Tests\TestCase;

class ClientDashboardTest extends TestCase
{
    use UsesIsolatedDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateIsolatedDatabase();
        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    private function account(string $role = Role::CLIENTE, bool $verified = true): User
    {
        return User::factory()->create([
            'name' => 'María José', 'last_name' => 'Muñoz de la Cruz',
            'email' => 'cliente@example.test', 'phone' => '5512345678',
            'role_id' => Role::where('name', $role)->sole()->id,
            'email_verified_at' => $verified ? now() : null,
            'password' => Hash::make('Abcdef1!'),
        ]);
    }

    public static function clientPages(): array
    {
        return [
            'home' => ['/cliente', 'Inicio'],
            'profile' => ['/cliente/perfil', 'Mi perfil'],
            'services' => ['/cliente/mis-servicios', 'Mis servicios'],
            'calculator' => ['/cliente/calculadora', 'Calculadora fiscal'],
            'results' => ['/cliente/resultados', 'Mis resultados'],
        ];
    }

    public static function clientPaths(): array
    {
        return array_map(fn (array $page) => [$page[0]], self::clientPages());
    }

    #[DataProvider('clientPages')]
    public function test_verified_clients_can_render_each_page_and_its_navigation(string $path, string $title): void
    {
        $user = $this->account();
        $response = $this->actingAs($user)->get($path)->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee($title)->assertSee($user->name)
            ->assertSee('images/wa-balance-logo.svg', false)
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('href="'.url('/').'#servicios"', false)
            ->assertSee('data-client-menu', false)
            ->assertSee('Saltar al contenido')
            ->assertDontSee('El panel se desarrollará posteriormente.')
            ->assertDontSee($user->password)->assertDontSee($user->remember_token);

        $document = new \DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//h1')->length);
        $activeLinks = $xpath->query('//nav//a[@aria-current="page"]');
        $this->assertSame(2, $activeLinks->length);
        foreach ($activeLinks as $link) {
            $this->assertSame(url($path), $link->getAttribute('href'));
        }
        foreach (self::clientPages() as [$destination]) {
            $response->assertSee('href="'.url($destination).'"', false);
        }
    }

    #[DataProvider('clientPaths')]
    public function test_guests_are_redirected_to_login_on_every_client_page(string $path): void
    {
        $this->get($path)->assertRedirect('/login');
    }

    #[DataProvider('clientPaths')]
    public function test_administrators_are_forbidden_on_every_client_page(string $path): void
    {
        $this->actingAs($this->account(Role::ADMINISTRADOR))->get($path)->assertForbidden();
    }

    #[DataProvider('clientPaths')]
    public function test_unverified_clients_are_redirected_to_verification_on_every_page(string $path): void
    {
        $this->actingAs($this->account(verified: false))->get($path)->assertRedirect('/email/verify');
    }

    public function test_home_is_personalized_and_only_advertises_future_features(): void
    {
        $this->actingAs($this->account())->get('/cliente')->assertOk()
            ->assertSee('Hola, María José.')->assertSee('Muñoz de la Cruz')
            ->assertSee('cliente@example.test')->assertSee('Explorar catálogo')
            ->assertSee('Sin contratación en línea')->assertSee('Próximamente')
            ->assertSee('Mis servicios')->assertSee('Calculadora fiscal')->assertSee('Mis resultados')
            ->assertDontSee('name="income"', false)->assertDontSee('name="ingresos"', false);
    }

    public function test_profile_only_shows_the_authenticated_accounts_basic_information(): void
    {
        $user = $this->account();
        $other = User::factory()->create([
            'name' => 'Otro cliente', 'last_name' => 'Datos privados',
            'phone' => '5598765432', 'email' => 'otra-cuenta@example.test',
        ]);
        foreach (['/cliente', '/cliente/perfil?user_id='.$other->id.'&id='.$other->id] as $path) {
            $response = $this->actingAs($user)->get($path)->assertOk()
                ->assertSee($user->name)->assertSee($user->last_name)->assertSee($user->email);
            foreach (['name', 'last_name', 'phone', 'email', 'password', 'remember_token'] as $field) {
                $response->assertDontSee($other->{$field});
            }
        }
        $this->get('/cliente/perfil')->assertOk()->assertSee($user->phone);
        $this->get('/cliente/perfil/'.$other->id)->assertNotFound();
    }

    public function test_profile_is_read_only_and_has_no_update_endpoint(): void
    {
        $user = $this->account();
        $before = $user->fresh()->getAttributes();
        $response = $this->actingAs($user)->get('/cliente/perfil')->assertOk()->assertSee('Solo lectura');
        $document = new \DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $this->assertSame(0, $xpath->query('//main//form | //main//input')->length);
        foreach (['post', 'put', 'patch', 'delete'] as $method) {
            $this->{$method}('/cliente/perfil', ['name' => 'Modificado', 'role_id' => 1])->assertStatus(405);
        }
        $this->assertSame($before, $user->fresh()->getAttributes());
    }

    public function test_account_values_are_escaped_in_the_home_header_and_profile(): void
    {
        $user = $this->account();
        $unsafe = '<script>alert("account")</script>';
        $user->forceFill(['name' => $unsafe, 'last_name' => '<img src=x onerror=alert(1)>'])->save();
        foreach (['/cliente', '/cliente/perfil'] as $path) {
            $this->actingAs($user)->get($path)->assertOk()
                ->assertSee($unsafe)->assertSee($user->last_name)
                ->assertDontSee($unsafe, false)->assertDontSee($user->last_name, false);
        }
    }

    public function test_future_sections_do_not_enable_capture_calculations_or_purchases(): void
    {
        $this->actingAs($this->account());
        foreach (['/cliente/mis-servicios', '/cliente/calculadora', '/cliente/resultados'] as $path) {
            $response = $this->get($path)->assertOk()->assertSee('Próximamente');
            $document = new \DOMDocument;
            $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new \DOMXPath($document);
            $this->assertSame(0, $xpath->query('//main//form | //main//input | //main//button')->length);
            $this->post($path, ['ingresos' => 10000])->assertStatus(405);
        }
        $this->get('/cliente/calculadora')->assertOk()
            ->assertSee('contratación del servicio')->assertSee('confirmación del pago')
            ->assertSee('12 horas desde la confirmación')->assertSee('Todavía no puedes capturar datos ni realizar cálculos.');
        Notification::assertNothingSent();
    }

    public function test_existing_login_and_logout_work_with_the_client_dashboard(): void
    {
        $this->account();
        $this->post('/login', ['email' => 'cliente@example.test', 'password' => 'Abcdef1!'])->assertRedirect('/cliente');
        $this->get('/cliente')->assertOk()->assertSee('Hola, María José.');
        $sessionId = session()->getId();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
        foreach (self::clientPages() as [$path]) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_administrator_keeps_the_existing_provisional_screen(): void
    {
        $this->actingAs($this->account(Role::ADMINISTRADOR))->get('/administrador')->assertOk()
            ->assertSee('Espacio de administrador')->assertSee('El panel se desarrollará posteriormente.')
            ->assertDontSee('data-client-menu', false);
    }
}
