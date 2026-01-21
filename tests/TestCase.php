<?php

namespace Asimnet\Notify\Tests;

use Asimnet\Notify\NotifyServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Load package factories if needed
        // $this->withFactories(__DIR__.'/../database/factories');
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            NotifyServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'Notify' => \Asimnet\Notify\Facades\Notify::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set notify package configuration defaults
        $app['config']->set('notify.tenancy.enabled', false);
        $app['config']->set('notify.queue.connection', 'sync');
        $app['config']->set('notify.queue.queue', 'notifications');
        $app['config']->set('notify.logging.enabled', true);
        $app['config']->set('notify.logging.retention_days', 180);

        // Configure authentication for API testing
        $app['config']->set('auth.guards.api', [
            'driver' => 'token',
            'provider' => 'users',
            'hash' => false,
        ]);

        // Configure user model
        $app['config']->set('auth.providers.users.model', TestUser::class);
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        // Run package migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Create users table for testing
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->string('gender')->nullable();
            $table->string('city')->nullable();
            $table->date('dob')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Helper to enable tenancy for testing.
     */
    protected function enableTenancy(): void
    {
        config(['notify.tenancy.enabled' => true]);
    }

    /**
     * Helper to disable tenancy for testing.
     */
    protected function disableTenancy(): void
    {
        config(['notify.tenancy.enabled' => false]);
    }

    /**
     * Create a test user for API authentication.
     */
    protected function createUser(): TestUser
    {
        return TestUser::forceCreate([
            'name' => 'Test User '.rand(1, 9999),
            'email' => 'test'.rand(1, 99999).'@example.com',
            'password' => bcrypt('password'),
        ]);
    }
}

/**
 * Simple test user for package testing.
 */
class TestUser extends \Illuminate\Foundation\Auth\User
{
    protected $table = 'users';
}
