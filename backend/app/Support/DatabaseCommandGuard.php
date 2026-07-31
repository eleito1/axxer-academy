<?php

namespace App\Support;

use Illuminate\Contracts\Config\Repository;
use RuntimeException;

class DatabaseCommandGuard
{
    /** @var list<string> */
    private const DESTRUCTIVE_COMMANDS = [
        'db:seed',
        'db:wipe',
        'migrate:fresh',
        'migrate:refresh',
        'migrate:reset',
        'migrate:rollback',
    ];

    public function __construct(private readonly Repository $config) {}

    public function assertCommandIsSafe(string $command): void
    {
        if (! $this->isDestructiveCommand($command) || $this->isIsolatedTestDatabase()) {
            return;
        }

        throw new RuntimeException($this->blockedMessage($command));
    }

    public function isDestructiveCommand(string $command): bool
    {
        $command = strtolower(trim($command));

        if (in_array($command, self::DESTRUCTIVE_COMMANDS, true)) {
            return true;
        }

        return preg_match('/(?:^|[:_-])(?:destroy|drop|fresh|purge|rebuild|reset|rollback|truncate|wipe)(?:$|[:_-])/', $command) === 1;
    }

    public function isIsolatedTestDatabase(): bool
    {
        $context = $this->context();

        return $context['environment'] === 'testing'
            && $context['connection'] === 'sqlite'
            && $context['driver'] === 'sqlite'
            && $context['database'] === ':memory:';
    }

    /**
     * @return array{environment: string, connection: string, driver: string, host: string, port: string, database: string}
     */
    public function context(): array
    {
        $connection = (string) $this->config->get('database.default', '');
        $settings = (array) $this->config->get("database.connections.{$connection}", []);

        return [
            'environment' => (string) $this->config->get('app.env', 'unknown'),
            'connection' => $connection ?: 'unknown',
            'driver' => (string) ($settings['driver'] ?? 'unknown'),
            'host' => (string) ($settings['host'] ?? 'n/a'),
            'port' => (string) ($settings['port'] ?? 'n/a'),
            'database' => (string) ($settings['database'] ?? 'unknown'),
        ];
    }

    public function blockedMessage(string $command): string
    {
        $context = $this->context();
        $reasons = $this->riskReasons($context);

        return implode(PHP_EOL, [
            'COMANDO DE BANCO BLOQUEADO ANTES DA EXECUÇÃO.',
            "Comando: {$command}",
            "Ambiente Laravel: {$context['environment']}",
            "Conexão: {$context['connection']}",
            "Driver: {$context['driver']}",
            "Host: {$context['host']}",
            "Porta: {$context['port']}",
            "Banco: {$context['database']}",
            'Motivo: '.implode('; ', $reasons),
            'Orientação: execute testes somente com APP_ENV=testing, DB_CONNECTION=sqlite e DB_DATABASE=:memory:.',
        ]);
    }

    /**
     * @param  array{environment: string, connection: string, driver: string, host: string, port: string, database: string}  $context
     * @return list<string>
     */
    private function riskReasons(array $context): array
    {
        $reasons = [];

        if (in_array($context['driver'], ['mysql', 'mariadb'], true)) {
            $reasons[] = 'driver operacional MySQL/MariaDB';
        }

        if (strtolower($context['database']) === 'axxer_academy') {
            $reasons[] = 'nome do banco operacional detectado';
        }

        if (in_array($context['environment'], ['local', 'staging', 'production'], true)) {
            $reasons[] = "ambiente {$context['environment']} não é ambiente de teste isolado";
        }

        if ($context['environment'] === 'testing' && ! $this->isExactInMemorySqlite($context)) {
            $reasons[] = 'testing herdou ou recebeu uma conexão que não é SQLite em memória';
        }

        if ($context['driver'] === 'sqlite' && $context['database'] !== ':memory:') {
            $reasons[] = 'arquivo SQLite persistente não autorizado';
        }

        if ($reasons === []) {
            $reasons[] = 'banco não foi identificado explicitamente como SQLite em memória dedicado a testes';
        }

        return $reasons;
    }

    /**
     * @param  array{environment: string, connection: string, driver: string, host: string, port: string, database: string}  $context
     */
    private function isExactInMemorySqlite(array $context): bool
    {
        return $context['connection'] === 'sqlite'
            && $context['driver'] === 'sqlite'
            && $context['database'] === ':memory:';
    }
}
