<?php

namespace Asimnet\Notify\Services;

use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\DTOs\NotificationMessage;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Throwable;

/**
 * FCM message service using kreait/firebase-php.
 *
 * Handles sending notifications and topic subscriptions via Firebase Cloud Messaging.
 * Includes proper error handling, batching for multicast, and token validation.
 *
 * خدمة رسائل FCM باستخدام kreait/firebase-php.
 * تتعامل مع إرسال الإشعارات والاشتراكات في المواضيع عبر Firebase Cloud Messaging.
 */
class FcmMessageService implements FcmService
{
    /**
     * Maximum tokens per FCM multicast request.
     */
    private const MAX_MULTICAST_TOKENS = 500;

    /**
     * Maximum tokens per FCM topic subscription request.
     */
    private const MAX_TOPIC_TOKENS = 1000;

    public function __construct(
        private readonly Messaging $messaging
    ) {}

    // ========================================
    // Topic Subscription Methods
    // ========================================

    /**
     * {@inheritdoc}
     */
    public function subscribeToTopic(string $topic, array $tokens): array
    {
        if (empty($tokens)) {
            return ['success' => [], 'failures' => []];
        }

        $success = [];
        $failures = [];

        // Process in batches of 1000 (FCM topic subscription limit)
        $chunks = array_chunk($tokens, self::MAX_TOPIC_TOKENS);

        foreach ($chunks as $chunk) {
            try {
                $result = $this->messaging->subscribeToTopic($topic, $chunk);

                foreach ($result as $token => $status) {
                    if ($status === 'OK' || $status === true) {
                        $success[] = $token;
                    } else {
                        $failures[$token] = is_string($status) ? $status : 'UNKNOWN_ERROR';
                    }
                }
            } catch (MessagingException $e) {
                // If entire batch fails, mark all as failed
                foreach ($chunk as $token) {
                    $failures[$token] = $e->getMessage();
                }
            }
        }

        return ['success' => $success, 'failures' => $failures];
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribeFromTopic(string $topic, array $tokens): array
    {
        if (empty($tokens)) {
            return ['success' => [], 'failures' => []];
        }

        $success = [];
        $failures = [];

        $chunks = array_chunk($tokens, self::MAX_TOPIC_TOKENS);

        foreach ($chunks as $chunk) {
            try {
                $result = $this->messaging->unsubscribeFromTopic($topic, $chunk);

                foreach ($result as $token => $status) {
                    if ($status === 'OK' || $status === true) {
                        $success[] = $token;
                    } else {
                        $failures[$token] = is_string($status) ? $status : 'UNKNOWN_ERROR';
                    }
                }
            } catch (MessagingException $e) {
                foreach ($chunk as $token) {
                    $failures[$token] = $e->getMessage();
                }
            }
        }

        return ['success' => $success, 'failures' => $failures];
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribeFromAllTopics(array $tokens): array
    {
        if (empty($tokens)) {
            return ['success' => [], 'failures' => []];
        }

        try {
            $this->messaging->unsubscribeFromAllTopics($tokens);

            return ['success' => $tokens, 'failures' => []];
        } catch (MessagingException $e) {
            $failures = [];
            foreach ($tokens as $token) {
                $failures[$token] = $e->getMessage();
            }

            return ['success' => [], 'failures' => $failures];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateToken(string $token): bool
    {
        try {
            $this->messaging->validateRegistrationTokens([$token]);

            return true;
        } catch (MessagingException $e) {
            return false;
        }
    }

    // ========================================
    // Message Sending Methods
    // ========================================

    /**
     * {@inheritdoc}
     */
    public function send(string $token, NotificationMessage $message): array
    {
        try {
            $cloudMessage = $this->buildCloudMessage($message)
                ->withChangedTarget('token', $token);

            $result = $this->messaging->send($cloudMessage);

            return [
                'success' => true,
                'message_id' => $result['name'] ?? $result,
                'error' => null,
            ];
        } catch (NotFound $e) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'UNREGISTERED',
            ];
        } catch (Throwable $e) {
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
            $cloudMessage = $this->buildCloudMessage($message)
                ->withChangedTarget('topic', $topic);

            $result = $this->messaging->send($cloudMessage);

            return [
                'success' => true,
                'message_id' => $result['name'] ?? $result,
                'error' => null,
            ];
        } catch (Throwable $e) {
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
        if (empty($tokens)) {
            return [
                'success_count' => 0,
                'failure_count' => 0,
                'results' => [],
            ];
        }

        $cloudMessage = $this->buildCloudMessage($message);
        $successCount = 0;
        $failureCount = 0;
        $results = [];

        // Process in batches of 500 (FCM multicast limit)
        $chunks = array_chunk($tokens, self::MAX_MULTICAST_TOKENS);

        foreach ($chunks as $chunk) {
            try {
                $report = $this->messaging->sendMulticast($cloudMessage, $chunk);

                foreach ($report->getItems() as $item) {
                    $token = $item->target()->value();

                    if ($item->isSuccess()) {
                        $successCount++;
                        $results[$token] = [
                            'success' => true,
                            'message_id' => $item->result()['name'] ?? null,
                            'error' => null,
                        ];
                    } else {
                        $failureCount++;
                        $results[$token] = [
                            'success' => false,
                            'message_id' => null,
                            'error' => $item->error()?->getMessage() ?? 'UNKNOWN_ERROR',
                        ];
                    }
                }
            } catch (Throwable $e) {
                // If entire batch fails, mark all as failed
                foreach ($chunk as $token) {
                    $failureCount++;
                    $results[$token] = [
                        'success' => false,
                        'message_id' => null,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function sendData(string $token, array $data): array
    {
        try {
            $cloudMessage = CloudMessage::new()
                ->withData($this->stringifyData($data))
                ->withChangedTarget('token', $token);

            $result = $this->messaging->send($cloudMessage);

            return [
                'success' => true,
                'message_id' => $result['name'] ?? $result,
                'error' => null,
            ];
        } catch (NotFound $e) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'UNREGISTERED',
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ========================================
    // Helper Methods
    // ========================================

    /**
     * Build a CloudMessage from a NotificationMessage DTO.
     *
     * بناء CloudMessage من كائن NotificationMessage.
     */
    private function buildCloudMessage(NotificationMessage $message): CloudMessage
    {
        // Create notification
        $notification = Notification::create($message->title, $message->body);

        if ($message->imageUrl !== null) {
            $notification = $notification->withImageUrl($message->imageUrl);
        }

        // Start building cloud message
        $cloudMessage = CloudMessage::new()->withNotification($notification);

        // Build data payload
        $data = $message->data;

        if ($message->actionUrl !== null) {
            $data['action_url'] = $message->actionUrl;
        }

        if ($message->analyticsLabel !== null) {
            $data['analytics_label'] = $message->analyticsLabel;
        }

        if (! empty($data)) {
            $cloudMessage = $cloudMessage->withData($this->stringifyData($data));
        }

        return $cloudMessage;
    }

    /**
     * Convert all data values to strings (FCM requirement).
     *
     * تحويل جميع قيم البيانات إلى سلاسل نصية (متطلب FCM).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function stringifyData(array $data): array
    {
        return array_map(
            fn ($value) => is_string($value) ? $value : json_encode($value),
            $data
        );
    }

    /**
     * Check if an error indicates the token is no longer valid.
     *
     * التحقق مما إذا كان الخطأ يشير إلى أن الرمز لم يعد صالحاً.
     *
     * @param  string  $error  The error message from FCM
     * @return bool True if token should be removed
     */
    public static function isUnregisteredError(string $error): bool
    {
        $unregisteredPatterns = [
            'UNREGISTERED',
            'NOT_FOUND',
            'INVALID_ARGUMENT',
            'InvalidToken',
        ];

        foreach ($unregisteredPatterns as $pattern) {
            if (str_contains(strtoupper($error), strtoupper($pattern))) {
                return true;
            }
        }

        return false;
    }
}
