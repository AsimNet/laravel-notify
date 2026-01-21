<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // FCM Settings
        $this->migrator->add('notify.fcm_enabled', true);
        $this->migrator->add('notify.fcm_project_id', null);
        $this->migrator->add('notify.fcm_credentials_json', null);

        // Logging Settings
        $this->migrator->add('notify.logging_enabled', true);
        $this->migrator->add('notify.log_retention_days', 180);
        $this->migrator->add('notify.log_store_payload', false);

        // Queue Settings
        $this->migrator->add('notify.queue_connection', 'redis');
        $this->migrator->add('notify.queue_name', 'notifications');

        // Rate Limiting
        $this->migrator->add('notify.rate_limit_per_minute', null);
        $this->migrator->add('notify.rate_limit_per_user_per_hour', null);

        // Default Topic Settings
        $this->migrator->add('notify.auto_subscribe_to_defaults', true);

        // Campaign Settings
        $this->migrator->add('notify.campaign_batch_size', 500);
        $this->migrator->add('notify.campaign_retry_attempts', 3);
    }
};
