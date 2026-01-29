<?php

namespace Asimnet\Notify\Services;

use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\DTOs\NotificationMessage;
use Asimnet\Notify\Settings\NotifySettings;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use RuntimeException;

/**
 * Tenant-aware FCM service that lazily resolves Firebase Messaging per tenant.
 *
 * خدمة FCM مدركة للمستأجر تقوم بتحميل Firebase Messaging لكل مستأجر عند الحاجة.
 *
 * Each tenant gets its own FcmMessageService instance created from its own
 * service account JSON stored in NotifySettings. The cache is keyed by
 * tenant UUID so credentials never overlap between tenants.
 *
 * Queue workers: QueueTenancyBootstrapper restores tenant context before each job.
 * resolveService() calls tenant()->getTenantKey() at call time (not construction time),
 * so it always gets the correct tenant.
 */
class TenantAwareFcmService implements FcmService
{
    /**
     * Cached FcmMessageService instances keyed by tenant key.
     *
     * @var array<string, FcmMessageService>
     */
    private array $resolvedServices = [];

    /**
     * {@inheritdoc}
     */
    public function subscribeToTopic(string $topic, array $tokens): array
    {
        try {
            return $this->resolveService()->subscribeToTopic($topic, $tokens);
        } catch (RuntimeException $e) {
            Log::warning('notify.fcm.subscribe_failed', [
                'topic' => $topic,
                'token_count' => count($tokens),
                'error' => $e->getMessage(),
            ]);

            return ['success' => [], 'failures' => array_fill_keys($tokens, $e->getMessage())];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribeFromTopic(string $topic, array $tokens): array
    {
        try {
            return $this->resolveService()->unsubscribeFromTopic($topic, $tokens);
        } catch (RuntimeException $e) {
            Log::warning('notify.fcm.unsubscribe_failed', [
                'topic' => $topic,
                'token_count' => count($tokens),
                'error' => $e->getMessage(),
            ]);

            return ['success' => [], 'failures' => array_fill_keys($tokens, $e->getMessage())];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribeFromAllTopics(array $tokens): array
    {
        try {
            return $this->resolveService()->unsubscribeFromAllTopics($tokens);
        } catch (RuntimeException $e) {
            Log::warning('notify.fcm.unsubscribe_all_failed', [
                'token_count' => count($tokens),
                'error' => $e->getMessage(),
            ]);

            return ['success' => [], 'failures' => array_fill_keys($tokens, $e->getMessage())];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateToken(string $token): bool
    {
        try {
            return $this->resolveService()->validateToken($token);
        } catch (RuntimeException $e) {
            Log::warning('notify.fcm.validate_token_failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function send(string $token, NotificationMessage $message): array
    {
        try {
            return $this->resolveService()->send($token, $message);
        } catch (RuntimeException $e) {
            Log::warning('notify.fcm.send_failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendToTopic(string $topic, NotificationMessage $message): array
    {
        try {
            return $this->resolveService()->sendToTopic($topic, $message);
        } catch (RuntimeException $e) {
            Log::warning('notify.fcm.send_to_topic_failed', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendMulticast(array $tokens, NotificationMessage $message): array
    {
        try {
            return $this->resolveService()->sendMulticast($tokens, $message);
        } catch (RuntimeException $e) {
            Log::warning('notify.fcm.multicast_failed', [
                'token_count' => count($tokens),
                'error' => $e->getMessage(),
            ]);

            $results = [];
            foreach ($tokens as $token) {
                $results[$token] = [
                    'success' => false,
                    'message_id' => null,
                    'error' => $e->getMessage(),
                ];
            }

            return [
                'success_count' => 0,
                'failure_count' => count($tokens),
                'results' => $results,
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendData(string $token, array $data): array
    {
        try {
            return $this->resolveService()->sendData($token, $data);
        } catch (RuntimeException $e) {
            Log::warning('notify.fcm.send_data_failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Resolve the FcmMessageService for the current tenant.
     *
     * حل خدمة FcmMessageService للمستأجر الحالي.
     *
     * @throws RuntimeException If FCM is not configured for the current tenant
     */
    private function resolveService(): FcmMessageService
    {
        $tenantKey = $this->getCurrentTenantKey();

        if (isset($this->resolvedServices[$tenantKey])) {
            return $this->resolvedServices[$tenantKey];
        }

        /** @var NotifySettings $settings */
        $settings = app(NotifySettings::class);

        if (! $settings->isFcmConfigured()) {
            throw new RuntimeException(__('notify::notify.error_fcm_not_configured'));
        }

        $credentials = $settings->getFcmCredentials();

        $messaging = (new Factory)
            ->withServiceAccount($credentials)
            ->createMessaging();

        $service = new FcmMessageService($messaging);

        $this->resolvedServices[$tenantKey] = $service;

        return $service;
    }

    /**
     * Get the current tenant key, or a fallback for central context.
     *
     * الحصول على مفتاح المستأجر الحالي، أو قيمة افتراضية للسياق المركزي.
     */
    private function getCurrentTenantKey(): string
    {
        try {
            if (function_exists('tenant') && tenant()) {
                return tenant()->getTenantKey();
            }
        } catch (\Exception $e) {
            // Tenant context not available
        }

        return '__central__';
    }

    /**
     * Flush the resolved services cache.
     *
     * مسح ذاكرة التخزين المؤقت للخدمات المحلولة.
     *
     * Useful for testing or when tenant credentials are updated.
     */
    public function flushCache(): void
    {
        $this->resolvedServices = [];
    }
}
