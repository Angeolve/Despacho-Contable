<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\UsesIsolatedDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use UsesIsolatedDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateIsolatedDatabase();
    }

    public function test_new_users_are_persisted_with_the_client_role(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::create([
            ...$this->userAttributes(),
            'name' => 'María José',
            'last_name' => 'Muñoz de la Cruz',
        ]);
        $persisted = User::findOrFail($user->id);
        $client = Role::where('name', Role::CLIENTE)->firstOrFail();

        $this->assertSame($client->id, $persisted->role_id);
        $this->assertTrue($persisted->role->is($client));
        $this->assertSame('María José', $persisted->name);
        $this->assertSame('Muñoz de la Cruz', $persisted->last_name);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'María José',
            'last_name' => 'Muñoz de la Cruz',
            'role_id' => $client->id,
        ]);
    }

    public function test_last_names_can_be_updated_without_changing_names_or_roles(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create(['name' => 'María José']);
        $roleId = $user->role_id;

        $user->update(['last_name' => 'García López']);
        $persisted = $user->fresh();

        $this->assertSame('María José', $persisted->name);
        $this->assertSame('García López', $persisted->last_name);
        $this->assertSame($roleId, $persisted->role_id);
        $this->assertSame(Role::CLIENTE, $persisted->role->name);
    }

    #[DataProvider('missingLastNames')]
    public function test_the_database_requires_last_name(array $overrides): void
    {
        $this->seed(RoleSeeder::class);
        $attributes = $this->userAttributes();
        unset($attributes['last_name']);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('NOT NULL constraint failed: users.last_name');

        User::create([...$attributes, ...$overrides]);
    }

    public static function missingLastNames(): array
    {
        return [
            'omitted' => [[]],
            'null' => [['last_name' => null]],
        ];
    }

    public function test_mass_assignment_cannot_create_an_administrator(): void
    {
        $this->seed(RoleSeeder::class);
        $administrator = Role::where('name', Role::ADMINISTRADOR)->firstOrFail();

        $user = User::create([
            ...$this->userAttributes(),
            'role_id' => $administrator->id,
            'role' => $administrator->toArray(),
        ]);

        $this->assertSame(Role::CLIENTE, $user->fresh()->role->name);
        $this->assertDatabaseMissing('users', ['role_id' => $administrator->id]);
    }

    public function test_mass_assignment_cannot_change_an_existing_users_role(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $clientRoleId = $user->role_id;
        $administrator = Role::where('name', Role::ADMINISTRADOR)->firstOrFail();

        $user->update(['name' => 'Updated client', 'role_id' => $administrator->id]);

        $this->assertSame($clientRoleId, $user->fresh()->role_id);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated client',
            'role_id' => $clientRoleId,
        ]);
    }

    public function test_a_role_associated_by_trusted_code_is_preserved_before_insertion(): void
    {
        $this->seed(RoleSeeder::class);
        $administrator = Role::where('name', Role::ADMINISTRADOR)->firstOrFail();
        $user = new User($this->userAttributes());
        $user->role()->associate($administrator);

        // The default-role listener runs first; cancel insertion so no admin is created.
        User::creating(function (User $creating) use ($administrator): bool {
            $this->assertSame($administrator->id, $creating->role_id);

            return false;
        });

        $this->assertFalse($user->save());
        $this->assertFalse($user->exists);
        $this->assertTrue($user->role->is($administrator));
        $this->assertDatabaseEmpty('users');
    }

    public function test_creation_fails_if_the_client_role_has_not_been_seeded(): void
    {
        try {
            User::create($this->userAttributes());
            $this->fail('Creation must fail when the client role is missing.');
        } catch (ModelNotFoundException $exception) {
            $this->assertSame(Role::class, $exception->getModel());
        }

        $this->assertDatabaseEmpty('users');
    }

    public function test_the_database_rejects_a_null_role_id(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('NOT NULL constraint failed: users.role_id');

        DB::table('users')->insert([...$this->userAttributes(), 'role_id' => null]);
    }

    public function test_the_database_rejects_a_nonexistent_role(): void
    {
        $user = new User($this->userAttributes());
        $user->role_id = 999;

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('FOREIGN KEY constraint failed');

        $user->save();
    }

    public function test_user_and_role_relationships_load_persisted_records(): void
    {
        $this->seed(RoleSeeder::class);
        $users = User::factory()->count(2)->create();
        $role = Role::with('users')->where('name', Role::CLIENTE)->firstOrFail();

        $this->assertCount(2, $role->users);
        $this->assertEqualsCanonicalizing($users->modelKeys(), $role->users->modelKeys());

        foreach (User::with('role')->get() as $user) {
            $this->assertTrue($user->role->is($role));
        }
    }

    public function test_a_role_with_users_cannot_be_deleted(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('FOREIGN KEY constraint failed');

        $user->role->delete();
    }

    public function test_passwords_are_hashed_in_the_database_and_hidden(): void
    {
        $this->seed(RoleSeeder::class);
        $password = bin2hex(random_bytes(16));
        $user = User::create([...$this->userAttributes(), 'password' => $password]);
        $storedPassword = DB::table('users')->where('id', $user->id)->value('password');

        $this->assertNotSame($password, $storedPassword);
        $this->assertTrue(Hash::check($password, $storedPassword));
        $this->assertArrayNotHasKey('password', $user->fresh()->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->fresh()->toArray());
    }

    public function test_an_existing_password_hash_is_not_hashed_again(): void
    {
        $this->seed(RoleSeeder::class);
        $hash = Hash::make(bin2hex(random_bytes(16)));
        $user = User::create([...$this->userAttributes(), 'password' => $hash]);

        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_role_seeding_is_repeatable_without_creating_users(): void
    {
        $this->seed(RoleSeeder::class);
        $originalIds = Role::orderBy('name')->pluck('id', 'name')->all();

        $this->seed(RoleSeeder::class);

        $this->assertSame($originalIds, Role::orderBy('name')->pluck('id', 'name')->all());
        $this->assertDatabaseCount('roles', 2);
        $this->assertDatabaseHas('roles', ['name' => Role::ADMINISTRADOR]);
        $this->assertDatabaseHas('roles', ['name' => Role::CLIENTE]);
        $this->assertDatabaseEmpty('users');
    }

    public function test_database_seeder_restores_events_for_subsequent_user_creation(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseEmpty('users');

        $user = User::factory()->create();

        $this->assertSame(Role::CLIENTE, $user->fresh()->role->name);
        $this->assertDatabaseCount('users', 1);
    }

    #[DataProvider('uniqueFields')]
    public function test_the_database_rejects_duplicate_contact_details(string $field): void
    {
        $this->seed(RoleSeeder::class);
        $existing = User::factory()->create();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('UNIQUE constraint failed: users.'.$field);

        User::create([...$this->userAttributes(), $field => $existing->{$field}]);
    }

    public static function uniqueFields(): array
    {
        return [
            'email' => ['email'],
            'phone' => ['phone'],
        ];
    }

    private function userAttributes(): array
    {
        return User::factory()->make()->only(['name', 'last_name', 'phone', 'email', 'password']);
    }
}
