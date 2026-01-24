<?php

namespace Asimnet\Notify\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Settings for Notify package.
 *
 * إعدادات حزمة الإشعارات.
 */
class NotifySettings extends Settings
{
    // FCM Settings
    public bool $fcm_enabled = true;

    public ?string $fcm_credentials_json = null;

    public bool $fcm_has_credentials = false;

    // Logging Settings
    public bool $logging_enabled = true;

    public int $log_retention_days = 180;

    public bool $log_store_payload = false;

    // Queue Settings
    public string $queue_connection = 'redis';

    public string $queue_name = 'notifications';

    // Rate Limiting
    public ?int $rate_limit_per_minute = null;

    public ?int $rate_limit_per_user_per_hour = null;

    // Default Topic Settings
    public bool $auto_subscribe_to_defaults = true;

    public static function group(): string
    {
        return 'notify';
    }

    /**
     * Properties that should be encrypted.
     *
     * الخصائص التي يجب تشفيرها.
     *
     * @return array<string>
     */
    public static function encrypted(): array
    {
        return [
            'fcm_credentials_json',
        ];
    }

    /**
     * Check if FCM is properly configured.
     *
     * التحقق من تكوين FCM بشكل صحيح.
     */
    public function isFcmConfigured(): bool
    {
        if (! $this->fcm_enabled || empty($this->fcm_credentials_json)) {
            return false;
        }

        $credentials = $this->getFcmCredentials();

        return ! empty($credentials['project_id']) && ! empty($credentials['private_key']);
    }

    /**
     * Get FCM credentials as array.
     *
     * الحصول على بيانات اعتماد FCM كمصفوفة.
     */
    public function getFcmCredentials(): ?array
    {
        if (empty($this->fcm_credentials_json)) {
            return null;
        }

        return json_decode($this->fcm_credentials_json, true);
    }

    /**
     * Get FCM project ID from credentials.
     *
     * الحصول على معرف المشروع من بيانات الاعتماد.
     */
    public function getFcmProjectId(): ?string
    {
        return $this->getFcmCredentials()['project_id'] ?? null;
    }
}
