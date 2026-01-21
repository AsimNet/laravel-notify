<?php

namespace Asimnet\Notify\Channels;

use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\DTOs\NotificationMessage;
use Asimnet\Notify\Models\DeviceToken;
use Asimnet\Notify\Services\FcmTopicService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Laravel notification channel for sending FCM notifications.
 *
 * قناة إشعارات Laravel لإرسال إشعارات FCM.
 *
 * Usage in a notification class:
 *
 * ```php
 * public function via($notifiable)
 * {
 *     return ['fcm'];
 * }
 *
 * public function toFcm($notifiable): NotificationMessage
 * {
 *     return NotificationMessage::create('Title', 'Body')
 *         ->withData(['key' => 'value']);
 * }
 * ```
 */
class FcmChannel
{
    public function __construct(
        private readonly FcmService $fcmService
    ) {}

    /**
     * Send the given notification.
     *
     * إرسال الإشعار المحدد.
     *
     * @param  mixed  $notifiable  The entity being notified (usually a User)
     * @param  Notification  $notification  The notification instance
     *
     * @throws InvalidArgumentException If notification doesn't have toFcm method
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        // 1. Check notification has toFcm method
        if (! method_exists($notification, 'toFcm')) {
            throw new InvalidArgumentException(
                __('Notification must have toFcm method returning NotificationMessage')
            );
        }

        // 2. Get FCM message from notification
        $message = $notification->toFcm($notifiable);

        if (! $message instanceof NotificationMessage) {
            throw new InvalidArgumentException(
                __('toFcm must return NotificationMessage instance')
            );
        }

        // 3. Get device tokens for notifiable
        $tokens = $this->getTokensForNotifiable($notifiable);

        if (empty($tokens)) {
            Log::debug('No FCM tokens for notifiable', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->getKey(),
            ]);

            return;
        }

        // 4. Send to all tokens via multicast
        $result = $this->fcmService->sendMulticast($tokens, $message);

        // 5. Handle invalid tokens (remove from database)
        $this->handleInvalidTokens($result['results']);

        // 6. Log result
        Log::info('FCM notification sent', [
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->getKey(),
            'success_count' => $result['success_count'],
            'failure_count' => $result['failure_count'],
        ]);
    }

    /**
     * Get FCM tokens for the notifiable entity.
     *
     * الحصول على رموز FCM للكيان المُعلَم.
     *
     * @return array<string>
     */
    private function getTokensForNotifiable(mixed $notifiable): array
    {
        // Check for custom routing method first
        if (method_exists($notifiable, 'routeNotificationForFcm')) {
            $tokens = $notifiable->routeNotificationForFcm();

            return is_array($tokens) ? $tokens : [$tokens];
        }

        // Default: look up device tokens via relationship
        if (method_exists($notifiable, 'deviceTokens')) {
            return $notifiable->deviceTokens()->pluck('token')->toArray();
        }

        // Try direct query if user has ID
        if (method_exists($notifiable, 'getKey')) {
            return DeviceToken::where('user_id', $notifiable->getKey())
                ->pluck('token')
                ->toArray();
        }

        return [];
    }

    /**
     * Handle invalid tokens by removing them from database.
     *
     * معالجة الرموز غير الصالحة عن طريق إزالتها من قاعدة البيانات.
     *
     * @param  array<string, array{success: bool, message_id: ?string, error: ?string}>  $results
     */
    private function handleInvalidTokens(array $results): void
    {
        foreach ($results as $token => $result) {
            if (! $result['success'] && $this->isUnregisteredError($result['error'])) {
                DeviceToken::where('token', $token)->delete();

                Log::info('Removed invalid FCM token', [
                    'token_prefix' => substr($token, 0, 20).'...',
                ]);
            }
        }
    }

    /**
     * Check if error indicates token is no longer valid.
     *
     * التحقق مما إذا كان الخطأ يشير إلى أن الرمز لم يعد صالحاً.
     */
    private function isUnregisteredError(?string $error): bool
    {
        if ($error === null) {
            return false;
        }

        // Use static helper from FcmTopicService
        return FcmTopicService::isUnregisteredError($error);
    }
}
