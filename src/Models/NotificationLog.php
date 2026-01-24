<?php

namespace Asimnet\Notify\Models;

use Asimnet\Notify\Models\Concerns\BelongsToTenantOptionally;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use BelongsToTenantOptionally;
    use HasFactory;
    use MassPrunable;

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_OPENED = 'opened';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'device_token_id',
        'channel',
        'status',
        'title',
        'body',
        'payload',
        'external_id',
        'error_message',
        'error_code',
        'sent_at',
        'delivered_at',
        'opened_at',
        'is_test',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'opened_at' => 'datetime',
            'is_test' => 'boolean',
        ];
    }

    public function getTable(): string
    {
        return config('notify.tables.logs', 'notify_logs');
    }

    /**
     * Get the prunable model query.
     *
     * Deletes logs older than configured retention days.
     * Uses MassPrunable for efficient bulk deletion.
     */
    public function prunable(): Builder
    {
        $retentionDays = config('notify.logging.retention_days', 180);

        return static::where('created_at', '<=', now()->subDays($retentionDays));
    }

    /**
     * Get the user that received the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'));
    }

    /**
     * Mark the notification as sent.
     *
     * @param  string|null  $externalId  FCM message ID or other external reference
     */
    public function markAsSent(?string $externalId = null): bool
    {
        return $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'external_id' => $externalId,
        ]);
    }

    /**
     * Mark the notification as delivered.
     *
     * Note: FCM HTTP v1 does not provide delivery receipts.
     * This is for future mobile app integration.
     */
    public function markAsDelivered(): bool
    {
        return $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark the notification as opened.
     *
     * Called when mobile app reports user opened the notification.
     */
    public function markAsOpened(): bool
    {
        return $this->update([
            'status' => self::STATUS_OPENED,
            'opened_at' => now(),
        ]);
    }

    /**
     * Mark the notification as failed.
     *
     * @param  string  $errorMessage  Human-readable error description
     * @param  string|null  $errorCode  Error code from provider (e.g., 'UNREGISTERED')
     */
    public function markAsFailed(string $errorMessage, ?string $errorCode = null): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'error_code' => $errorCode,
        ]);
    }

    /**
     * Check if the notification was successfully sent.
     */
    public function wasSuccessful(): bool
    {
        return in_array($this->status, [
            self::STATUS_SENT,
            self::STATUS_DELIVERED,
            self::STATUS_OPENED,
        ]);
    }

    /**
     * Scope to filter by channel.
     */
    public function scopeChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get recent logs.
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to exclude test notifications.
     */
    public function scopeNotTest(Builder $query): Builder
    {
        return $query->where('is_test', false);
    }

    /**
     * Scope to only test notifications.
     */
    public function scopeOnlyTest(Builder $query): Builder
    {
        return $query->where('is_test', true);
    }

    /**
     * Scope to filter by FCM channel.
     */
    public function scopeFcm(Builder $query): Builder
    {
        return $query->where('channel', 'fcm');
    }

    /**
     * Scope to get failed notifications.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to get successful notifications.
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_SENT,
            self::STATUS_DELIVERED,
            self::STATUS_OPENED,
        ]);
    }

    protected static function newFactory()
    {
        return \Asimnet\Notify\Database\Factories\NotificationLogFactory::new();
    }
}
