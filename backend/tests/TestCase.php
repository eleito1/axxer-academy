<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        $environment = $app->environment();
        $connection = $app['config']->get('database.default');
        $driver = $app['config']->get("database.connections.{$connection}.driver");
        $database = $app['config']->get("database.connections.{$connection}.database");

        if ($environment !== 'testing' || $connection !== 'sqlite' || $driver !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(sprintf(
                'Testes abortados antes do RefreshDatabase: esperado testing/sqlite/:memory:, detectado %s/%s/%s.',
                $environment,
                $driver,
                $database,
            ));
        }

        return $app;
    }
}
