<?php

namespace Asimnet\Notify;

use Asimnet\Notify\Events\DeviceTokenDeleted;
use Asimnet\Notify\Events\DeviceTokenRegistered;
use Asimnet\Notify\Events\TopicSubscribed;
use Asimnet\Notify\Events\TopicUnsubscribed;
use Asimnet\Notify\Listeners\CleanupDeletedDeviceFromFcm;
use Asimnet\Notify\Listeners\SyncDeviceToDefaultTopics;
use Asimnet\Notify\Listeners\SyncTopicSubscriptionToFcm;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class NotifyServiceProvider extends PackageServiceProvider
{
    public static string $name = 'notify';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasRoute('api')
            ->hasCommand(\Asimnet\Notify\Console\Commands\ProcessScheduledNotifications::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations();
            });
    }

    /**
     * Register package services and publish migrations.
     *
     * تسجيل خدمات الحزمة ونشر الهجرات.
     */
    public function boot(): void
    {
        parent::boot();

        $this->publishMigrations();
    }

    /**
     * Publish migrations for settings and tenant databases.
     *
     * نشر الهجرات لإعدادات الحزمة وقواعد بيانات المستأجرين.
     *
     * Settings migrations: Package settings (Spatie Settings), run on main database
     * Tenant migrations: Per-tenant tables (device_tokens, topics, logs, etc.)
     *
     * Usage:
     * - php artisan vendor:publish --tag=notify-migrations (all)
     * - php artisan vendor:publish --tag=notify-migrations-settings (settings only)
     * - php artisan vendor:publish --tag=notify-migrations-tenant (tenant only)
     *
     * For multi-tenancy with Stancl/Tenancy:
     * 1. Publish tenant migrations to your tenant path
     * 2. Or add package path directly to config/tenancy.php migration_parameters:
     *    base_path('vendor/asimnet/laravel-notify/database/migrations/tenant')
     */
    protected function publishMigrations(): void
    {
        $settingsMigrations = __DIR__.'/../database/migrations';
        $tenantMigrations = __DIR__.'/../database/migrations/tenant';

        // Publish settings migrations (main database)
        $this->publishes([
            $settingsMigrations => database_path('migrations'),
        ], 'notify-migrations-settings');

        // Publish tenant migrations (per-tenant database)
        $this->publishes([
            $tenantMigrations => database_path('migrations/tenant'),
        ], 'notify-migrations-tenant');

        // Publish all migrations
        $this->publishes([
            $settingsMigrations => database_path('migrations'),
            $tenantMigrations => database_path('migrations/tenant'),
        ], 'notify-migrations');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton('notify', function ($app) {
            return new NotifyManager($app);
        });

        // Register FcmService - tenant-aware service that lazily resolves
        // Firebase Messaging per tenant from NotifySettings credentials.
        // Tests that need FakeFcmService can bind it via $this->mock() or $this->app->instance().
        $this->app->singleton(\Asimnet\Notify\Contracts\FcmService::class, function () {
            return new \Asimnet\Notify\Services\TenantAwareFcmService;
        });

        // Register SmsManager for resolving SMS drivers (extensible).
        $this->app->singleton(SmsManager::class, function ($app) {
            return new SmsManager($app);
        });

        // Bind SmsChannel so it can be resolved by the notification channel manager.
        $this->app->bind(\Asimnet\Notify\Channels\SmsChannel::class, function ($app) {
            return new \Asimnet\Notify\Channels\SmsChannel($app->make(SmsManager::class));
        });

        // Bind WbaChannel if wba-filament is installed.
        if (class_exists(\Asimnet\WbaFilament\Services\WbaService::class)) {
            $this->app->bind(\Asimnet\Notify\Channels\WbaChannel::class, function ($app) {
                return new \Asimnet\Notify\Channels\WbaChannel(
                    $app->make(\Asimnet\WbaFilament\Services\WbaService::class),
                    $app->make(\Asimnet\Notify\Services\NotificationLogger::class)
                );
            });
        }
    }

    public function packageBooted(): void
    {
        if (config('notify.horizon.enabled', true)) {
            $this->registerHorizonQueues();
        }

        $this->registerEventListeners();
        $this->registerRouteModelBindings();
        $this->extendNotificationChannels();
    }

    /**
     * Register route model bindings for package models.
     */
    protected function registerRouteModelBindings(): void
    {
        // Bind 'device' route parameter to DeviceToken model
        Route::bind('device', function ($value) {
            return \Asimnet\Notify\Models\DeviceToken::findOrFail($value);
        });

        // Bind 'topic' route parameter to Topic model
        Route::bind('topic', function ($value) {
            // Allow binding by ID or slug
            $topic = \Asimnet\Notify\Models\Topic::find($value);

            if (! $topic) {
                $topic = \Asimnet\Notify\Models\Topic::where('slug', $value)->first();
            }

            if (! $topic) {
                abort(404, __('notify::notify.error_topic_not_found'));
            }

            return $topic;
        });
    }

    /**
     * Register event listeners for FCM synchronization.
     */
    protected function registerEventListeners(): void
    {
        // Device token registered -> subscribe to default topics
        Event::listen(
            DeviceTokenRegistered::class,
            SyncDeviceToDefaultTopics::class
        );

        // Device token deleted -> cleanup FCM subscriptions
        Event::listen(
            DeviceTokenDeleted::class,
            CleanupDeletedDeviceFromFcm::class
        );

        // Topic subscribed -> sync with FCM
        Event::listen(
            TopicSubscribed::class,
            [SyncTopicSubscriptionToFcm::class, 'handleSubscription']
        );

        // Topic unsubscribed -> sync with FCM
        Event::listen(
            TopicUnsubscribed::class,
            [SyncTopicSubscriptionToFcm::class, 'handleUnsubscription']
        );
    }

    /**
     * Register notification queues with Laravel Horizon.
     *
     * This method merges the notify package's supervisor configuration
     * into Horizon's existing configuration when Horizon is installed.
     */
    protected function registerHorizonQueues(): void
    {
        // Check if Horizon is installed by verifying the service provider is loaded
        if (! $this->app->providerIsLoaded('Laravel\Horizon\HorizonServiceProvider')) {
            return;
        }

        $supervisorConfig = config('notify.horizon.supervisor', []);

        if (empty($supervisorConfig)) {
            return;
        }

        // Merge the notification supervisor into Horizon's defaults
        $currentDefaults = Config::get('horizon.defaults', []);
        $currentDefaults['supervisor-notifications'] = $supervisorConfig;
        Config::set('horizon.defaults', $currentDefaults);

        // Merge into all environments
        $environments = Config::get('horizon.environments', []);
        foreach ($environments as $env => $supervisors) {
            $environments[$env]['supervisor-notifications'] = array_merge(
                $supervisorConfig,
                $supervisors['supervisor-notifications'] ?? []
            );
        }
        Config::set('horizon.environments', $environments);
    }

    /**
     * Register the custom SMS channel with Laravel's notification manager.
     */
    protected function extendNotificationChannels(): void
    {
        Notification::resolved(function (ChannelManager $manager) {
            $manager->extend('sms', function ($app) {
                return $app->make(\Asimnet\Notify\Channels\SmsChannel::class);
            });
            if (class_exists(\Asimnet\Notify\Channels\WbaChannel::class)) {
                $manager->extend('wba', function ($app) {
                    return $app->make(\Asimnet\Notify\Channels\WbaChannel::class);
                });
            }
        });
    }
}
