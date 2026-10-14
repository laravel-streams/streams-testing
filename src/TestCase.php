<?php

namespace Streams\Testing;

use Streams\Testing\Concerns\InteractsWithStreams;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use InteractsWithStreams;

    /**
     * PHPUnit environment is the source of truth. The runtime application
     * does not load a project .env file.
     */
    protected $loadEnvironmentVariables = false;

    protected function setUp(): void
    {
        RuntimeApplication::boot();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        RuntimeApplication::restoreStreams();

        parent::tearDown();
    }

    public static function applicationBasePath()
    {
        return RuntimeApplication::path();
    }

    protected function getPackageProviders($app)
    {
        return $this->streamsPackageProviders();
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.env', 'testing');

        // Test fixture, not a secret. Used only when APP_KEY is unset.
        if (! $app['config']->get('app.key')) {
            $app['config']->set('app.key', 'base64:aiGINJ0oFnqrMGUwJYWJuhe6meZoW+GqppwDJD4YZeM=');
        }

        $connection = $_SERVER['DB_CONNECTION'] ?? $_ENV['DB_CONNECTION'] ?? null;

        if (! is_string($connection) || $connection === '') {
            $app['config']->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ]);

            $app['config']->set('database.default', 'testing');
        }

        $mailer = $_SERVER['MAIL_MAILER'] ?? $_ENV['MAIL_MAILER'] ?? null;

        if (! is_string($mailer) || $mailer === '') {
            $app['config']->set('mail.default', 'array');
        }
    }
}
