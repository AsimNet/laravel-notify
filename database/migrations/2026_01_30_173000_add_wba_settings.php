<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('notify.wba_enabled', false);
        $this->migrator->add('notify.wba_default_language', 'ar');
        $this->migrator->add('notify.wba_credentials', null);
    }
};
