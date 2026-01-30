<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('notify.sms_enabled', false);
        $this->migrator->add('notify.sms_default_driver', null);
        $this->migrator->add('notify.sms_credentials', []);
    }
};
