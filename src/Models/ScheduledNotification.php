<?php

namespace Asimnet\Notify\Models;

use Asimnet\Notify\Models\Concerns\BelongsToTenantOptionally;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Scheduled notification model for database-driven notification scheduling.
 *
 * نموذج الإشعارات المجدولة للجدولة المدفوعة بقاعدة البيانات.
 *
 * Status is determined by timestamps (no status column):
 * - cancelled_at set → STATUS_CANCELLED
 * - failed_at set → STATUS_FAILED
 * - sent_at set → STATUS_SENT
 * - Otherwise → STATUS_PENDING
 */
class ScheduledNotification extends Model
{
    use BelongsToTenantOptionally;
    use HasFactory;

    /**
     * Status constants (computed from timestamps).
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing'; // Reserved for future job status tracking

    public const STATUS_SENT = 'sent';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'channel',
        'title',
        'body',
        'image_url',
        'action_url',
        'payload',
        'scheduled_at',
        'sent_at',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'failed_at',
        'error_message',
        'is_test',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'failed_at' => 'datetime',
            'is_test' => 'boolean',
        ];
    }

    public function getTable(): string
    {
        return config('notify.tables.scheduled_notifications', 'notify_scheduled_notifications');
    }

    /**
     * Get the user that will receive the notification.
     *
     * الحصول على المستخدم الذي سيتلقى الإشعار.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'));
    }

    /**
     * Get the user who cancelled the notification.
     *
     * الحصول على المستخدم الذي ألغى الإشعار.
     */
    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'cancelled_by');
    }

    /**
     * Get the computed status based on timestamps.
     *
     * الحصول على الحالة المحسوبة بناءً على الطوابع الزمنية.
     *
     * Priority: cancelled -> failed -> sent -> pending
     */
    public function getStatusAttribute(): string
    {
        if ($this->cancelled_at !== null) {
            return self::STATUS_CANCELLED;
        }

        if ($this->failed_at !== null) {
            return self::STATUS_FAILED;
        }

        if ($this->sent_at !== null) {
            return self::STATUS_SENT;
        }

        return self::STATUS_PENDING;
    }

    /**
     * Check if the notification can be cancelled.
     *
     * التحقق مما إذا كان يمكن إلغاء الإشعار.
     *
     * Can only cancel if:
     * - Status is pending (not sent, cancelled, or failed)
     * - Scheduled time is in the future
     */
    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->scheduled_at->isFuture();
    }

    /**
     * Cancel the scheduled notification.
     *
     * إلغاء الإشعار المجدول.
     *
     * @throws InvalidArgumentException if the notification cannot be cancelled
     */
    public function cancel(?int $cancelledBy = null, ?string $reason = null): bool
    {
        if (! $this->canBeCancelled()) {
            throw new InvalidArgumentException(
                __('notify::messages.cannot_cancel_notification')
            );
        }

        return $this->update([
            'cancelled_at' => now(),
            'cancelled_by' => $cancelledBy,
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Mark the notification as sent.
     *
     * تحديد الإشعار كمُرسل.
     */
    public function markAsSent(): bool
    {
        return $this->update([
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark the notification as failed.
     *
     * تحديد الإشعار كفاشل.
     */
    public function markAsFailed(string $errorMessage): bool
    {
        return $this->update([
            'failed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Scope to get notifications due for sending.
     *
     * نطاق للحصول على الإشعارات المستحقة للإرسال.
     *
     * Due = pending (no sent/cancelled/failed timestamps) AND scheduled_at <= now
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->whereNull('sent_at')
            ->whereNull('cancelled_at')
            ->whereNull('failed_at')
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope to get pending notifications (not yet processed).
     *
     * نطاق للحصول على الإشعارات المعلقة (لم تتم معالجتها بعد).
     */
    public function scopePending(Builder $query): Builder
    {
        return $query
            ->whereNull('sent_at')
            ->whereNull('cancelled_at')
            ->whereNull('failed_at');
    }

    /**
     * Scope to filter by user.
     *
     * نطاق للتصفية حسب المستخدم.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by scheduled time range.
     *
     * نطاق للتصفية حسب نطاق الوقت المجدول.
     *
     * @param  \DateTimeInterface|string  $start
     * @param  \DateTimeInterface|string  $end
     */
    public function scopeScheduledBetween(Builder $query, $start, $end): Builder
    {
        return $query->whereBetween('scheduled_at', [$start, $end]);
    }

    /**
     * Scope to exclude test notifications.
     *
     * نطاق لاستبعاد إشعارات الاختبار.
     */
    public function scopeNotTest(Builder $query): Builder
    {
        return $query->where('is_test', false);
    }

    /**
     * Scope to only include test notifications.
     *
     * نطاق لتضمين إشعارات الاختبار فقط.
     */
    public function scopeOnlyTest(Builder $query): Builder
    {
        return $query->where('is_test', true);
    }

    protected static function newFactory()
    {
        return \Asimnet\Notify\Database\Factories\ScheduledNotificationFactory::new();
    }
}
