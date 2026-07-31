<?php

namespace Tests\Feature;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class DatabaseCommandProtectionTest extends TestCase
{
    public function test_artisan_event_blocks_operational_mysql_before_any_query(): void
    {
        config()->set('app.env', 'testing');
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'axxer_academy',
            'username' => 'root',
            'password' => 'must-not-appear',
        ]);

        $queries = 0;
        Event::listen(QueryExecuted::class, function () use (&$queries): void {
            $queries++;
        });

        try {
            Event::dispatch(new CommandStarting('migrate:fresh', new ArrayInput(['--force' => true]), new BufferedOutput));
            $this->fail('migrate:fresh deveria ter sido bloqueado no evento CommandStarting.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('COMANDO DE BANCO BLOQUEADO ANTES DA EXECUÇÃO.', $exception->getMessage());
            $this->assertStringContainsString('testing herdou ou recebeu uma conexão', $exception->getMessage());
            $this->assertStringNotContainsString('must-not-appear', $exception->getMessage());
            $this->assertSame(0, $queries);
        }
    }

    public function test_artisan_event_allows_migrate_status(): void
    {
        Event::dispatch(new CommandStarting('migrate:status', new ArrayInput([]), new BufferedOutput));

        $this->addToAssertionCount(1);
    }
}
