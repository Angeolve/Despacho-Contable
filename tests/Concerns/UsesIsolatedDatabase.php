<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

trait UsesIsolatedDatabase
{
    protected function migrateIsolatedDatabase(): void
    {
        $this->assertTrue($this->app->runningUnitTests());
        config([
            'database.default' => 'wbalance_testing',
            'database.connections.wbalance_testing' => [
                'driver' => 'sqlite', 'url' => null, 'database' => ':memory:',
                'prefix' => '', 'foreign_key_constraints' => true,
            ],
            'cache.default' => 'array',
        ]);
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->assertSame(1, (int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys);

        // A new in-memory connection per test; no destructive migration commands.
        $this->artisan('migrate', [
            '--database' => 'wbalance_testing',
            '--path' => [
                'database/migrations/0001_01_01_000000_create_users_table.php',
                'database/migrations/2026_10_15_054134_create_roles_table.php',
                'database/migrations/2026_10_15_054215_add_role_id_to_users_table.php',
                'database/migrations/2026_10_15_073546_add_last_name_to_users_table.php',
                'database/migrations/2026_10_15_075825_add_privacy_acceptance_to_users_table.php',
            ],
        ])->assertExitCode(0);
    }
}
