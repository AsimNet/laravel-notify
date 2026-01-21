<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Add flag to check credentials without decrypting
        $this->migrator->add('notify.fcm_has_credentials', false);
    }
};
