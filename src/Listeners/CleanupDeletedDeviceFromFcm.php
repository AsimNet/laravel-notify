<?php

namespace Asimnet\Notify\Listeners;

use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\Events\DeviceTokenDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Cleanup FCM subscriptions when a device is deleted.
 *
 * Unsubscribes the deleted device token from all FCM topics.
 */
class CleanupDeletedDeviceFromFcm implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The queue this job should run on.
     */
    public string $queue = 'notifications';

    /**
     * Number of times to retry.
     */
    public int $tries = 3;

    /**
     * Seconds to wait before retry.
     */
    public int $backoff = 60;

    public function __construct(
        private readonly FcmService $fcmService
    ) {}

    public function handle(DeviceTokenDeleted $event): void
    {
        $token = $event->token;

        // Unsubscribe from all topics
        $result = $this->fcmService->unsubscribeFromAllTopics([$token]);

        if (empty($result['failures'])) {
            Log::info('Notify: Device unsubscribed from all FCM topics', [
                'user_id' => $event->userId,
                'token_prefix' => substr($token, 0, 20).'...',
            ]);
        } else {
            // UNREGISTERED errors are expected for deleted devices
            Log::debug('Notify: FCM cleanup had failures (may be expected)', [
                'failures' => $result['failures'],
            ]);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(DeviceTokenDeleted $event, \Throwable $exception): void
    {
        Log::error('Notify: CleanupDeletedDeviceFromFcm failed', [
            'user_id' => $event->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
