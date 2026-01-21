<?php

namespace Asimnet\Notify\Listeners;

use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\Events\DeviceTokenRegistered;
use Asimnet\Notify\Models\Topic;
use Asimnet\Notify\Models\TopicSubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Subscribe newly registered devices to default topics.
 *
 * When a user's first device is registered, this listener
 * auto-subscribes them to all default topics (is_default=true).
 */
class SyncDeviceToDefaultTopics implements ShouldQueue
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

    public function handle(DeviceTokenRegistered $event): void
    {
        $deviceToken = $event->deviceToken;
        $token = $deviceToken->token;

        // Find default topics (respecting tenant context)
        $defaultTopics = Topic::query()
            ->default()
            ->get();

        if ($defaultTopics->isEmpty()) {
            Log::debug('Notify: No default topics found for auto-subscription');

            return;
        }

        foreach ($defaultTopics as $topic) {
            // Create or find subscription
            $subscription = TopicSubscription::firstOrCreate(
                [
                    'user_id' => $deviceToken->user_id,
                    'topic_id' => $topic->id,
                    'tenant_id' => $deviceToken->tenant_id,
                ],
                [
                    'fcm_synced' => false,
                ]
            );

            // Sync with FCM
            $result = $this->fcmService->subscribeToTopic(
                $topic->getFcmTopicName(),
                [$token]
            );

            if (empty($result['failures'])) {
                $subscription->markSynced();

                // Increment subscriber count if this is a new subscription
                if ($subscription->wasRecentlyCreated) {
                    $topic->increment('subscriber_count');
                }

                Log::info('Notify: Device subscribed to default topic', [
                    'topic' => $topic->slug,
                    'user_id' => $deviceToken->user_id,
                ]);
            } else {
                Log::warning('Notify: Failed to subscribe device to default topic', [
                    'topic' => $topic->slug,
                    'failures' => $result['failures'],
                ]);
            }
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(DeviceTokenRegistered $event, \Throwable $exception): void
    {
        Log::error('Notify: SyncDeviceToDefaultTopics failed', [
            'user_id' => $event->deviceToken->user_id,
            'error' => $exception->getMessage(),
        ]);
    }
}
