<?php

namespace Asimnet\Notify\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the Notify package.
 *
 * @method static \Asimnet\Notify\NotifyManager to(mixed $recipients) Set the notification recipients
 * @method static \Asimnet\Notify\NotifyManager via(string|array $channels) Set the notification channel(s)
 * @method static \Asimnet\Notify\NotifyManager send(mixed $notification = null) Send the notification
 * @method static mixed config(?string $key = null, mixed $default = null) Get a package configuration value
 * @method static bool tenancyEnabled() Check if multi-tenancy is enabled
 * @method static bool loggingEnabled() Check if logging is enabled
 * @method static string getQueueConnection() Get the configured queue connection
 * @method static string getQueueName() Get the configured queue name
 *
 * @see \Asimnet\Notify\NotifyManager
 */
class Notify extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'notify';
    }
}
