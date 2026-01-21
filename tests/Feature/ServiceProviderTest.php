<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Facades\Notify;
use Asimnet\Notify\NotifyManager;
use Asimnet\Notify\NotifyServiceProvider;
use Asimnet\Notify\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    /** @test */
    public function it_registers_the_service_provider(): void
    {
        $this->assertTrue(
            $this->app->providerIsLoaded(NotifyServiceProvider::class),
            __('The NotifyServiceProvider should be registered.')
        );
    }

    /** @test */
    public function it_provides_notify_config(): void
    {
        $config = config('notify');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('tenancy', $config);
        $this->assertArrayHasKey('queue', $config);
        $this->assertArrayHasKey('horizon', $config);
        $this->assertArrayHasKey('logging', $config);
        $this->assertArrayHasKey('tables', $config);
    }

    /** @test */
    public function it_provides_correct_default_config_values(): void
    {
        $this->assertFalse(config('notify.tenancy.enabled'));
        $this->assertEquals('sync', config('notify.queue.connection'));
        $this->assertEquals('notifications', config('notify.queue.queue'));
        $this->assertTrue(config('notify.logging.enabled'));
        $this->assertEquals(180, config('notify.logging.retention_days'));
    }

    /** @test */
    public function it_resolves_notify_facade_to_manager(): void
    {
        $resolved = Notify::getFacadeRoot();

        $this->assertInstanceOf(NotifyManager::class, $resolved);
    }

    /** @test */
    public function it_resolves_notify_binding_as_singleton(): void
    {
        $instance1 = app('notify');
        $instance2 = app('notify');

        $this->assertSame($instance1, $instance2);
    }

    /** @test */
    public function it_registers_vendor_publish_tags(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'notify-config', '--force' => true])
            ->assertExitCode(0);
    }
}
