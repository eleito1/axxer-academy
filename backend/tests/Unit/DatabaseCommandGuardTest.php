<?php

namespace Tests\Unit;

use App\Support\DatabaseCommandGuard;
use Illuminate\Config\Repository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseCommandGuardTest extends TestCase
{
    #[DataProvider('destructiveCommands')]
    public function test_it_blocks_destructive_commands_on_the_operational_mysql_database(string $command): void
    {
        $guard = $this->mysqlGuard();

        try {
            $guard->assertCommandIsSafe($command);
            $this->fail("{$command} deveria ter sido bloqueado.");
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString("Comando: {$command}", $exception->getMessage());
            $this->assertStringContainsString('Driver: mysql', $exception->getMessage());
            $this->assertStringContainsString('Banco: axxer_academy', $exception->getMessage());
        }
    }

    public function test_force_does_not_change_the_command_safety_decision(): void
    {
        $this->expectException(RuntimeException::class);

        $this->mysqlGuard()->assertCommandIsSafe('migrate:fresh');
    }

    public function test_testing_environment_cannot_bypass_a_real_mysql_connection(): void
    {
        $guard = $this->mysqlGuard('testing');

        $this->expectExceptionMessage('testing herdou ou recebeu uma conexão que não é SQLite em memória');

        $guard->assertCommandIsSafe('db:wipe');
    }

    public function test_sqlite_memory_allows_test_database_commands(): void
    {
        $guard = $this->sqliteMemoryGuard();

        $guard->assertCommandIsSafe('migrate:fresh');
        $guard->assertCommandIsSafe('db:seed');

        $this->assertTrue($guard->isIsolatedTestDatabase());
    }

    public function test_persistent_sqlite_file_is_blocked(): void
    {
        $guard = $this->guard('testing', 'sqlite', 'sqlite', 'database/testing.sqlite');

        $this->expectExceptionMessage('arquivo SQLite persistente não autorizado');

        $guard->assertCommandIsSafe('migrate:rollback');
    }

    public function test_safe_commands_remain_available_on_mysql(): void
    {
        $this->mysqlGuard()->assertCommandIsSafe('migrate:status');

        $this->addToAssertionCount(1);
    }

    public function test_custom_destructive_command_names_are_blocked(): void
    {
        $this->expectException(RuntimeException::class);

        $this->mysqlGuard()->assertCommandIsSafe('academy:truncate-content');
    }

    public function test_block_message_never_exposes_credentials(): void
    {
        $config = $this->mysqlConfig('production');
        $config->set('database.connections.mysql.password', 'segredo-super-sensivel');
        $guard = new DatabaseCommandGuard($config);

        try {
            $guard->assertCommandIsSafe('db:wipe');
            $this->fail('db:wipe deveria ter sido bloqueado.');
        } catch (RuntimeException $exception) {
            $this->assertStringNotContainsString('segredo-super-sensivel', $exception->getMessage());
            $this->assertStringNotContainsString('password', strtolower($exception->getMessage()));
        }
    }

    public function test_guard_decides_before_any_database_connection_is_needed(): void
    {
        $guard = $this->mysqlGuard();

        $this->expectException(RuntimeException::class);

        $guard->assertCommandIsSafe('migrate:fresh');
    }

    /** @return array<string, array{string}> */
    public static function destructiveCommands(): array
    {
        return [
            'migrate fresh' => ['migrate:fresh'],
            'database wipe' => ['db:wipe'],
            'migration rollback' => ['migrate:rollback'],
            'migration reset' => ['migrate:reset'],
            'forced seeding command' => ['db:seed'],
        ];
    }

    private function mysqlGuard(string $environment = 'production'): DatabaseCommandGuard
    {
        return new DatabaseCommandGuard($this->mysqlConfig($environment));
    }

    private function sqliteMemoryGuard(): DatabaseCommandGuard
    {
        return $this->guard('testing', 'sqlite', 'sqlite', ':memory:');
    }

    private function mysqlConfig(string $environment): Repository
    {
        return new Repository([
            'app' => ['env' => $environment],
            'database' => [
                'default' => 'mysql',
                'connections' => [
                    'mysql' => [
                        'driver' => 'mysql',
                        'host' => '127.0.0.1',
                        'port' => '3306',
                        'database' => 'axxer_academy',
                        'username' => 'root',
                        'password' => 'never-read-by-the-guard',
                    ],
                ],
            ],
        ]);
    }

    private function guard(string $environment, string $connection, string $driver, string $database): DatabaseCommandGuard
    {
        return new DatabaseCommandGuard(new Repository([
            'app' => ['env' => $environment],
            'database' => [
                'default' => $connection,
                'connections' => [
                    $connection => compact('driver', 'database'),
                ],
            ],
        ]));
    }
}
