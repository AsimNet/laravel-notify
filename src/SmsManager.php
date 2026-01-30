<?php

namespace Asimnet\Notify;

use Asimnet\Notify\Contracts\SmsDriver;
use Asimnet\Notify\Services\GenericHttpSmsDriver;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Resolves SMS drivers by name with extend() support (similar to Mail manager).
 */
class SmsManager
{
    /**
     * @var array<string, callable(Container): SmsDriver>
     */
    protected array $customCreators = [];

    /**
     * @var array<string, SmsDriver>
     */
    protected array $drivers = [];

    public function __construct(protected Container $app) {}

    public function driver(?string $name = null): SmsDriver
    {
        $name ??= $this->defaultDriver();

        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        return $this->drivers[$name] = $this->resolve($name);
    }

    public function extend(string $name, callable $creator): self
    {
        $this->customCreators[$name] = $creator;

        return $this;
    }

    protected function resolve(string $name): SmsDriver
    {
        if (isset($this->customCreators[$name])) {
            return $this->customCreators[$name]($this->app);
        }

        $config = $this->driversConfig()[$name] ?? null;

        if ($config === null) {
            throw new InvalidArgumentException("SMS driver [{$name}] is not defined.");
        }

        // Apply settings override one more time to ensure precedence.
        $settingsCredentials = $this->settingsCredentialsFor($name);
        if ($settingsCredentials) {
            $config = array_replace_recursive($config, $settingsCredentials);
            if (isset($settingsCredentials['auth']['token'])) {
                $config['auth']['token'] = $settingsCredentials['auth']['token'];
            }
        }

        $type = $config['type'] ?? 'http';

        return match ($type) {
            'http' => new GenericHttpSmsDriver($config),
            default => throw new InvalidArgumentException("Unsupported SMS driver type [{$type}]."),
        };
    }

    protected function defaultDriver(): string
    {
        $settings = $this->getSettings();

        if ($settings && $settings->sms_default_driver) {
            return $settings->sms_default_driver;
        }

        return config('notify.sms.default_driver', 'http_generic');
    }

    /**
     * Check if SMS is enabled (settings override config).
     */
    public function enabled(): bool
    {
        $settings = $this->getSettings();

        if ($settings !== null) {
            return (bool) $settings->sms_enabled;
        }

        return (bool) config('notify.sms.enabled', false);
    }

    /**
     * Get drivers config merged with settings + tenant/dynamic overrides.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function driversConfig(): array
    {
        $drivers = config('notify.sms.drivers', []);
        $settings = $this->getSettings();

        if ($settings) {
            $credentials = $settings->sms_credentials;

            if (is_string($credentials)) {
                $credentials = json_decode($credentials, true) ?: [];
            }

            if ($credentials instanceof \Illuminate\Support\Collection) {
                $credentials = $credentials->toArray();
            }

            if (is_array($credentials)) {
                foreach ($credentials as $driver => $creds) {
                    if (isset($drivers[$driver]) && is_array($creds)) {
                        $drivers[$driver] = array_replace_recursive($drivers[$driver], $creds);
                    }
                }
            }
        }

        $tenantOverrides = $this->resolveTenantCredentials();
        if (is_array($tenantOverrides)) {
            foreach ($tenantOverrides as $driver => $creds) {
                if (isset($drivers[$driver]) && is_array($creds)) {
                    $drivers[$driver] = array_replace_recursive($drivers[$driver], $creds);
                }
            }
        }

        return $drivers;
    }

    protected function settingsCredentialsFor(string $driver): ?array
    {
        $settings = $this->getSettings();

        if (! $settings) {
            return null;
        }

        $credentials = $settings->sms_credentials;

        if ($credentials instanceof \Illuminate\Support\Collection) {
            $credentials = $credentials->toArray();
        }

        if (is_string($credentials)) {
            $credentials = json_decode($credentials, true) ?: [];
        }

        $creds = $credentials[$driver] ?? null;

        if ($creds === null) {
            $creds = $this->loadCredentialsDirectly($driver);
        }

        return $creds;
    }

    /**
     * Fallback: read raw payload from settings table when repository did not hydrate it.
     */
    protected function loadCredentialsDirectly(string $driver): ?array
    {
        $record = $this->app['db']->table('settings')
            ->where('group', 'notify')
            ->where('name', 'sms_credentials')
            ->value('payload');

        if ($record === null) {
            return null;
        }

        $payload = is_string($record) ? json_decode($record, true) : $record;

        return $payload[$driver] ?? null;
    }

    /**
     * Resolve tenant/dynamic credentials via config resolver or tenant data.
     *
     * @return array<string, array<string, mixed>>|null
     */
    protected function resolveTenantCredentials(): ?array
    {
        $resolver = config('notify.sms.credentials_resolver');

        if (is_callable($resolver)) {
            return $resolver();
        }

        if (function_exists('tenant') && tenant()) {
            $creds = tenant()->getInternal('notify_sms_credentials') ?? tenant()->getAttribute('notify_sms_credentials');

            if ($creds instanceof \Illuminate\Support\Collection) {
                return $creds->toArray();
            }

            if (is_string($creds)) {
                return json_decode($creds, true) ?: null;
            }

            if (is_array($creds)) {
                return $creds;
            }
        }

        return null;
    }

    protected function getSettings(): ?object
    {
        if (! $this->app->bound(\Asimnet\Notify\Settings\NotifySettings::class)) {
            return null;
        }

        return $this->app->make(\Asimnet\Notify\Settings\NotifySettings::class);
    }
}
