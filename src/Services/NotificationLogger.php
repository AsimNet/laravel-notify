<?php

namespace Asimnet\Notify\Services;

use Asimnet\Notify\DTOs\NotificationMessage;
use Asimnet\Notify\Models\NotificationLog;

/**
 * Service for logging notification send operations.
 *
 * خدمة لتسجيل عمليات ارسال الاشعارات.
 */
class NotificationLogger
{
    /**
     * Log a notification send operation.
     *
     * @param  NotificationMessage  $message  The notification that was sent
     * @param  array<string, mixed>  $result  Result from send operation
     * @param  string  $channel  Channel used (e.g., 'fcm')
     * @param  int|null  $userId  Recipient user ID
     * @param  string|null  $deviceTokenId  Device token ID (for per-token logging)
     * @param  int|null  $campaignId  Campaign ID if from a campaign
     * @param  bool  $isTest  Whether this is a test notification
     * @return NotificationLog|null Returns null if logging is disabled
     */
    public function logSend(
        NotificationMessage $message,
        array $result,
        string $channel = 'fcm',
        ?int $userId = null,
        ?string $deviceTokenId = null,
        ?int $campaignId = null,
        bool $isTest = false
    ): ?NotificationLog {
        if (! $this->isEnabled()) {
            return null;
        }

        $success = $result['success'] ?? false;
        $messageId = $result['message_id'] ?? null;

        // For multicast results, get message_id from first success if available
        if ($messageId === null && isset($result['success_count']) && $result['success_count'] > 0) {
            $messageId = $result['message_ids'][0] ?? null;
        }

        return NotificationLog::create([
            'tenant_id' => $this->getCurrentTenantId(),
            'campaign_id' => $campaignId,
            'user_id' => $userId,
            'device_token_id' => $deviceTokenId,
            'channel' => $channel,
            'status' => $success ? NotificationLog::STATUS_SENT : NotificationLog::STATUS_FAILED,
            'title' => $message->title,
            'body' => $message->body,
            'payload' => $this->shouldStorePayload() ? $message->toArray() : null,
            'external_id' => $messageId,
            'error_message' => $result['error'] ?? null,
            'error_code' => $result['error_code'] ?? null,
            'sent_at' => $success ? now() : null,
            'is_test' => $isTest,
        ]);
    }

    /**
     * Log a batch/multicast send operation.
     *
     * Creates a single log entry for the batch with recipient count.
     *
     * @param  NotificationMessage  $message  The notification that was sent
     * @param  array<string, mixed>  $result  Result with success_count and failure_count
     * @param  string  $channel  Channel used
     * @param  array<int>  $userIds  Array of recipient user IDs
     * @param  int|null  $campaignId  Campaign ID if from a campaign
     * @param  bool  $isTest  Whether this is a test notification
     */
    public function logBatchSend(
        NotificationMessage $message,
        array $result,
        string $channel = 'fcm',
        array $userIds = [],
        ?int $campaignId = null,
        bool $isTest = false
    ): ?NotificationLog {
        if (! $this->isEnabled()) {
            return null;
        }

        $successCount = $result['success_count'] ?? 0;
        $failureCount = $result['failure_count'] ?? 0;
        $totalCount = $successCount + $failureCount;

        // Determine overall status
        $status = NotificationLog::STATUS_FAILED;
        if ($successCount > 0 && $failureCount === 0) {
            $status = NotificationLog::STATUS_SENT;
        } elseif ($successCount > 0) {
            // Partial success - still mark as sent
            $status = NotificationLog::STATUS_SENT;
        }

        $payload = $this->shouldStorePayload() ? $message->toArray() : null;
        if ($payload !== null) {
            $payload['recipient_count'] = $totalCount;
            $payload['success_count'] = $successCount;
            $payload['failure_count'] = $failureCount;
            $payload['user_ids'] = $userIds;
        }

        return NotificationLog::create([
            'tenant_id' => $this->getCurrentTenantId(),
            'campaign_id' => $campaignId,
            'user_id' => count($userIds) === 1 ? $userIds[0] : null,
            'device_token_id' => null, // Batch sends don't track individual tokens
            'channel' => $channel,
            'status' => $status,
            'title' => $message->title,
            'body' => $message->body,
            'payload' => $payload,
            'external_id' => $result['message_ids'][0] ?? $result['message_id'] ?? null,
            'error_message' => $failureCount > 0 ? "{$failureCount} of {$totalCount} failed" : null,
            'error_code' => null,
            'sent_at' => $successCount > 0 ? now() : null,
            'is_test' => $isTest,
        ]);
    }

    /**
     * Log a topic send operation.
     *
     * @param  NotificationMessage  $message  The notification that was sent
     * @param  array<string, mixed>  $result  Result from topic send
     * @param  string  $topic  Topic that was sent to
     * @param  int|null  $campaignId  Campaign ID if from a campaign
     * @param  bool  $isTest  Whether this is a test notification
     */
    public function logTopicSend(
        NotificationMessage $message,
        array $result,
        string $topic,
        ?int $campaignId = null,
        bool $isTest = false
    ): ?NotificationLog {
        if (! $this->isEnabled()) {
            return null;
        }

        $payload = $this->shouldStorePayload() ? $message->toArray() : null;
        if ($payload !== null) {
            $payload['topic'] = $topic;
        }

        return NotificationLog::create([
            'tenant_id' => $this->getCurrentTenantId(),
            'campaign_id' => $campaignId,
            'user_id' => null, // Topics don't have a specific user
            'device_token_id' => null,
            'channel' => 'fcm',
            'status' => ($result['success'] ?? false) ? NotificationLog::STATUS_SENT : NotificationLog::STATUS_FAILED,
            'title' => $message->title,
            'body' => $message->body,
            'payload' => $payload,
            'external_id' => $result['message_id'] ?? null,
            'error_message' => $result['error'] ?? null,
            'error_code' => null,
            'sent_at' => ($result['success'] ?? false) ? now() : null,
            'is_test' => $isTest,
        ]);
    }

    /**
     * Check if logging is enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) config('notify.logging.enabled', true);
    }

    /**
     * Check if payload should be stored.
     */
    protected function shouldStorePayload(): bool
    {
        return (bool) config('notify.logging.store_payload', false);
    }

    /**
     * Get the current tenant ID if available.
     */
    protected function getCurrentTenantId(): ?string
    {
        if (! config('notify.tenancy.enabled', false)) {
            return null;
        }

        if (function_exists('tenant') && tenant()) {
            return tenant()->getTenantKey();
        }

        return null;
    }
}
