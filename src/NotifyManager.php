<?php

namespace Asimnet\Notify;

use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\DTOs\NotificationMessage;
use Asimnet\Notify\Models\DeviceToken;
use Asimnet\Notify\Models\ScheduledNotification;
use Asimnet\Notify\Services\NotificationLogger;
use DateTimeInterface;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

/**
 * The main entry point for the Notify package.
 *
 * This manager provides a fluent interface for sending notifications
 * across multiple channels (FCM, WhatsApp, Telegram, etc.)
 *
 * @example
 * Notify::to($user)->via('fcm')->send($notification);
 * Notify::to($users)->send($notification);
 */
class NotifyManager
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The notification recipients.
     */
    protected mixed $recipients = null;

    /**
     * The notification channels.
     *
     * @var array<string>
     */
    protected array $channels = ['fcm'];

    /**
     * Create a new NotifyManager instance.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Set the notification recipients.
     *
     * @param  mixed  $recipients  User ID, array of user IDs, or 'topic:slug'
     * @return $this
     */
    public function to(mixed $recipients): self
    {
        $this->recipients = $recipients;

        return $this;
    }

    /**
     * Set the notification channel(s).
     *
     * @param  string|array<string>  $channels  Channel name(s): 'fcm'
     * @return $this
     */
    public function via(string|array $channels): self
    {
        $this->channels = is_array($channels) ? $channels : [$channels];

        return $this;
    }

    /**
     * Send the notification.
     *
     * @param  NotificationMessage|null  $message  The notification to send
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    public function send(?NotificationMessage $message = null): array
    {
        if ($message === null) {
            throw new InvalidArgumentException('Message is required');
        }

        if ($this->recipients === null) {
            throw new InvalidArgumentException('Recipients are required. Call to() first.');
        }

        // Handle topic: prefix
        if (is_string($this->recipients) && str_starts_with($this->recipients, 'topic:')) {
            $topic = substr($this->recipients, 6);

            return $this->sendToTopic($topic, $message);
        }

        // Handle single user ID
        if (is_int($this->recipients)) {
            return $this->sendToUser($this->recipients, $message);
        }

        // Handle array of user IDs
        if (is_array($this->recipients)) {
            return $this->sendToUsers($this->recipients, $message);
        }

        throw new InvalidArgumentException('Invalid recipients format');
    }

    /**
     * Send notification to a single user by ID.
     *
     * @param  int  $userId  The user ID to send to
     * @param  NotificationMessage  $message  The notification content
     * @return array{success: bool, success_count: int, failure_count: int, error: ?string}
     */
    public function sendToUser(int $userId, NotificationMessage $message): array
    {
        $tokens = DeviceToken::where('user_id', $userId)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return [
                'success' => false,
                'success_count' => 0,
                'failure_count' => 0,
                'error' => 'No devices registered for user',
            ];
        }

        $result = $this->getFcmService()->sendMulticast($tokens, $message);

        $response = [
            'success' => $result['success_count'] > 0,
            'success_count' => $result['success_count'],
            'failure_count' => $result['failure_count'],
            'error' => null,
        ];

        // Log the send operation
        $this->getLogger()->logBatchSend($message, $result, 'fcm', [$userId]);

        return $response;
    }

    /**
     * Send notification to multiple users by IDs.
     *
     * @param  array<int>  $userIds  Array of user IDs
     * @param  NotificationMessage  $message  The notification content
     * @return array{success: bool, success_count: int, failure_count: int, error: ?string}
     */
    public function sendToUsers(array $userIds, NotificationMessage $message): array
    {
        if (empty($userIds)) {
            return [
                'success' => false,
                'success_count' => 0,
                'failure_count' => 0,
                'error' => 'No user IDs provided',
            ];
        }

        $tokens = DeviceToken::whereIn('user_id', $userIds)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return [
                'success' => false,
                'success_count' => 0,
                'failure_count' => 0,
                'error' => 'No devices registered for users',
            ];
        }

        $result = $this->getFcmService()->sendMulticast($tokens, $message);

        $response = [
            'success' => $result['success_count'] > 0,
            'success_count' => $result['success_count'],
            'failure_count' => $result['failure_count'],
            'error' => null,
        ];

        // Log the send operation
        $this->getLogger()->logBatchSend($message, $result, 'fcm', $userIds);

        return $response;
    }

    /**
     * Send notification to a topic.
     *
     * All subscribers of the topic will receive the notification.
     *
     * @param  string  $topicSlug  The topic slug (e.g., 'public', 'news')
     * @param  NotificationMessage  $message  The notification content
     * @return array{success: bool, message_id: ?string, error: ?string}
     */
    public function sendToTopic(string $topicSlug, NotificationMessage $message): array
    {
        $result = $this->getFcmService()->sendToTopic($topicSlug, $message);

        // Log the topic send
        $this->getLogger()->logTopicSend($message, $result, $topicSlug);

        return $result;
    }

    /**
     * Broadcast notification to all users.
     *
     * This sends to the 'public' topic that all devices subscribe to by default.
     *
     * @param  NotificationMessage  $message  The notification content
     * @return array{success: bool, message_id: ?string, error: ?string}
     */
    public function broadcast(NotificationMessage $message): array
    {
        $publicTopic = $this->config('default_topics.0', 'public');

        $result = $this->getFcmService()->sendToTopic($publicTopic, $message);

        // Log the broadcast
        $this->getLogger()->logTopicSend($message, $result, $publicTopic);

        return $result;
    }

    /**
     * Send data-only message to a user (no visible notification).
     *
     * @param  int  $userId  The user ID
     * @param  array<string, mixed>  $data  Custom data payload
     * @return array{success: bool, success_count: int, failure_count: int, error: ?string}
     */
    public function sendDataToUser(int $userId, array $data): array
    {
        $tokens = DeviceToken::where('user_id', $userId)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return [
                'success' => false,
                'success_count' => 0,
                'failure_count' => 0,
                'error' => 'No devices registered for user',
            ];
        }

        $fcm = $this->getFcmService();
        $successCount = 0;
        $failureCount = 0;

        foreach ($tokens as $token) {
            $result = $fcm->sendData($token, $data);
            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        $response = [
            'success' => $successCount > 0,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'error' => null,
        ];

        // Log as a data-only message (create a minimal message for logging)
        if ($this->loggingEnabled()) {
            $logMessage = NotificationMessage::create('[Data Message]', json_encode($data));
            $this->getLogger()->logBatchSend($logMessage, $response, 'fcm', [$userId]);
        }

        return $response;
    }

    /**
     * Get the FCM service instance.
     */
    protected function getFcmService(): FcmService
    {
        return $this->app->make(FcmService::class);
    }

    /**
     * Get the notification logger service.
     *
     * الحصول على خدمة تسجيل الإشعارات.
     */
    protected function getLogger(): NotificationLogger
    {
        return $this->app->make(NotificationLogger::class);
    }

    /**
     * Get a package configuration value.
     *
     * @param  string|null  $key  The config key (relative to 'notify.')
     * @param  mixed  $default  Default value if key doesn't exist
     */
    public function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return config('notify');
        }

        return config("notify.{$key}", $default);
    }

    /**
     * Check if multi-tenancy is enabled.
     */
    public function tenancyEnabled(): bool
    {
        return (bool) $this->config('tenancy.enabled', false);
    }

    /**
     * Check if logging is enabled.
     */
    public function loggingEnabled(): bool
    {
        return (bool) $this->config('logging.enabled', true);
    }

    /**
     * Get the configured queue connection.
     */
    public function getQueueConnection(): string
    {
        return $this->config('queue.connection', 'redis');
    }

    /**
     * Get the configured queue name.
     */
    public function getQueueName(): string
    {
        return $this->config('queue.queue', 'notifications');
    }

    /**
     * Schedule a notification for future delivery.
     *
     * جدولة إشعار للتسليم المستقبلي.
     *
     * @param  int  $userId  The user to send to / المستخدم المراد الإرسال إليه
     * @param  NotificationMessage  $message  The notification content / محتوى الإشعار
     * @param  DateTimeInterface  $scheduledAt  When to send / وقت الإرسال
     * @param  bool  $isTest  Whether this is a test notification / هل هذا إشعار اختباري
     */
    public function schedule(
        int $userId,
        NotificationMessage $message,
        DateTimeInterface $scheduledAt,
        bool $isTest = false
    ): ScheduledNotification {
        return ScheduledNotification::create([
            'tenant_id' => $this->getCurrentTenantId(),
            'user_id' => $userId,
            'channel' => 'fcm',
            'title' => $message->title,
            'body' => $message->body,
            'image_url' => $message->imageUrl ?? null,
            'action_url' => $message->data['action_url'] ?? null,
            'payload' => $message->data ?? null,
            'scheduled_at' => $scheduledAt,
            'is_test' => $isTest,
        ]);
    }

    /**
     * Get the current tenant ID if tenancy is enabled.
     *
     * الحصول على معرف المستأجر الحالي إذا كان تعدد المستأجرين مفعلاً.
     */
    protected function getCurrentTenantId(): ?string
    {
        if (! $this->tenancyEnabled()) {
            return null;
        }

        if (function_exists('tenant') && tenant()) {
            return tenant()->getTenantKey();
        }

        return null;
    }

    /**
     * Send a test notification to the currently authenticated user.
     *
     * Only sends to the admin's own registered devices.
     *
     * إرسال إشعار اختباري إلى المستخدم المصادق عليه حالياً.
     * يرسل فقط إلى أجهزة المسؤول المسجلة.
     *
     * @param  NotificationMessage  $message  The notification to send / الإشعار المراد إرساله
     * @return array{success: bool, success_count: int, failure_count: int, error: ?string}
     *
     * @throws InvalidArgumentException If no authenticated user
     */
    public function sendTestToSelf(NotificationMessage $message): array
    {
        $user = auth()->user();

        if (! $user) {
            throw new InvalidArgumentException(
                __('notify::notify.test_requires_auth')
            );
        }

        // Get tokens directly to send without the default logging
        $tokens = DeviceToken::where('user_id', $user->id)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return [
                'success' => false,
                'success_count' => 0,
                'failure_count' => 0,
                'error' => __('notify::notify.no_devices'),
            ];
        }

        $result = $this->getFcmService()->sendMulticast($tokens, $message);

        $response = [
            'success' => $result['success_count'] > 0,
            'success_count' => $result['success_count'],
            'failure_count' => $result['failure_count'],
            'error' => null,
        ];

        // Log as test notification with is_test = true
        $this->getLogger()->logBatchSend($message, $result, 'fcm', [$user->id], true);

        return $response;
    }
}
