<?php

namespace Asimnet\Notify\Listeners;

use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\Events\TopicSubscribed;
use Asimnet\Notify\Events\TopicUnsubscribed;
use Asimnet\Notify\Models\DeviceToken;
use Asimnet\Notify\Services\FcmTopicService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Sync topic subscription changes with FCM.
 *
 * Handles both subscribe and unsubscribe events,
 * syncing user's device tokens with FCM topic.
 */
class SyncTopicSubscriptionToFcm implements ShouldQueue
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

    /**
     * Handle TopicSubscribed event.
     */
    public function handleSubscription(TopicSubscribed $event): void
    {
        $subscription = $event->subscription;
        $deviceTokens = $event->deviceTokens;

        if (empty($deviceTokens)) {
            Log::debug('Notify: No device tokens to subscribe to topic', [
                'topic_id' => $subscription->topic_id,
                'user_id' => $subscription->user_id,
            ]);

            return;
        }

        $topic = $subscription->topic;
        $fcmTopicName = $topic->getFcmTopicName();

        $result = $this->fcmService->subscribeToTopic($fcmTopicName, $deviceTokens);

        // Mark subscription as synced if at least some tokens succeeded
        if (! empty($result['success'])) {
            $subscription->markSynced();
        }

        // Handle invalid tokens
        $this->handleInvalidTokens($result['failures']);

        Log::info('Notify: Topic subscription synced with FCM', [
            'topic' => $topic->slug,
            'success_count' => count($result['success']),
            'failure_count' => count($result['failures']),
        ]);
    }

    /**
     * Handle TopicUnsubscribed event.
     */
    public function handleUnsubscription(TopicUnsubscribed $event): void
    {
        $topic = $event->topic;
        $deviceTokens = $event->deviceTokens;

        if (empty($deviceTokens)) {
            return;
        }

        $fcmTopicName = $topic->getFcmTopicName();

        $result = $this->fcmService->unsubscribeFromTopic($fcmTopicName, $deviceTokens);

        // Handle invalid tokens
        $this->handleInvalidTokens($result['failures']);

        Log::info('Notify: Topic unsubscription synced with FCM', [
            'topic' => $topic->slug,
            'success_count' => count($result['success']),
            'failure_count' => count($result['failures']),
        ]);
    }

    /**
     * Handle invalid tokens returned from FCM.
     *
     * Removes tokens that FCM reports as UNREGISTERED or invalid.
     */
    private function handleInvalidTokens(array $failures): void
    {
        foreach ($failures as $token => $error) {
            if (FcmTopicService::isUnregisteredError($error)) {
                // Delete the invalid token from database
                DeviceToken::where('token', $token)->delete();

                Log::info('Notify: Removed invalid FCM token', [
                    'token_prefix' => substr($token, 0, 20).'...',
                    'error' => $error,
                ]);
            }
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(TopicSubscribed|TopicUnsubscribed $event, \Throwable $exception): void
    {
        Log::error('Notify: SyncTopicSubscriptionToFcm failed', [
            'event' => get_class($event),
            'error' => $exception->getMessage(),
        ]);
    }
}
