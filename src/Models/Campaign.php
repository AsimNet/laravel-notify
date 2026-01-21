<?php

namespace Asimnet\Notify\Models;

use Asimnet\Notify\Models\Concerns\BelongsToTenantOptionally;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Campaign model for managing notification campaigns.
 *
 * نموذج الحملة لإدارة حملات الإشعارات.
 *
 * Represents a notification campaign that can target segments,
 * topics, or specific recipients through various channels.
 *
 * يمثل حملة إشعارات يمكن أن تستهدف شرائح أو مواضيع
 * أو مستلمين محددين من خلال قنوات مختلفة.
 */
class Campaign extends Model
{
    use BelongsToTenantOptionally;
    use HasFactory;

    /**
     * Status constants for campaign lifecycle.
     *
     * ثوابت الحالة لدورة حياة الحملة.
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Campaign type constants.
     *
     * ثوابت نوع الحملة.
     */
    public const TYPE_DIRECT = 'direct';

    public const TYPE_TOPIC = 'topic';

    public const TYPE_BROADCAST = 'broadcast';

    public const TYPE_SEGMENT = 'segment';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'status',
        'title',
        'body',
        'image_url',
        'action_url',
        'payload',
        'template_id',
        'segment_id',
        'channels',
        'recipient_query',
        'recipient_count',
        'sent_count',
        'delivered_count',
        'failed_count',
        'scheduled_at',
        'sent_at',
    ];

    /**
     * Get the casts for the model attributes.
     *
     * الحصول على التحويلات لسمات النموذج.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'channels' => 'array',
            'recipient_query' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'recipient_count' => 'integer',
            'sent_count' => 'integer',
            'delivered_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    /**
     * Get the table associated with the model.
     *
     * الحصول على الجدول المرتبط بالنموذج.
     */
    public function getTable(): string
    {
        return config('notify.tables.campaigns', 'notify_campaigns');
    }

    /**
     * Get the template associated with this campaign.
     *
     * الحصول على القالب المرتبط بهذه الحملة.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class);
    }

    /**
     * Get the segment associated with this campaign.
     *
     * الحصول على الشريحة المرتبطة بهذه الحملة.
     */
    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }

    /**
     * Get all notification logs for this campaign.
     *
     * الحصول على جميع سجلات الإشعارات لهذه الحملة.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    /**
     * Check if the campaign can be sent.
     *
     * التحقق مما إذا كان يمكن إرسال الحملة.
     *
     * Campaign can be sent if status is draft or scheduled.
     * يمكن إرسال الحملة إذا كانت الحالة مسودة أو مجدولة.
     */
    public function canBeSent(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_SCHEDULED,
        ]);
    }

    /**
     * Check if the campaign can be cancelled.
     *
     * التحقق مما إذا كان يمكن إلغاء الحملة.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_SCHEDULED,
        ]);
    }

    /**
     * Check if the campaign has been sent.
     *
     * التحقق مما إذا تم إرسال الحملة.
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if the campaign is currently sending.
     *
     * التحقق مما إذا كانت الحملة قيد الإرسال حالياً.
     */
    public function isSending(): bool
    {
        return $this->status === self::STATUS_SENDING;
    }

    /**
     * Mark the campaign as sending.
     *
     * تعليم الحملة على أنها قيد الإرسال.
     */
    public function markAsSending(): bool
    {
        return $this->update(['status' => self::STATUS_SENDING]);
    }

    /**
     * Mark the campaign as sent.
     *
     * تعليم الحملة على أنها تم إرسالها.
     */
    public function markAsSent(): bool
    {
        return $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark the campaign as failed.
     *
     * تعليم الحملة على أنها فشلت.
     */
    public function markAsFailed(): bool
    {
        return $this->update(['status' => self::STATUS_FAILED]);
    }

    /**
     * Mark the campaign as cancelled.
     *
     * تعليم الحملة على أنها ملغاة.
     */
    public function markAsCancelled(): bool
    {
        return $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Update campaign statistics.
     *
     * تحديث إحصائيات الحملة.
     */
    public function updateStats(int $sentCount, int $failedCount): bool
    {
        return $this->update([
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
        ]);
    }

    /**
     * Increment success count.
     *
     * زيادة عداد النجاح.
     */
    public function incrementSuccessCount(int $count = 1): void
    {
        $this->increment('sent_count', $count);
    }

    /**
     * Increment failure count.
     *
     * زيادة عداد الفشل.
     */
    public function incrementFailureCount(int $count = 1): void
    {
        $this->increment('failed_count', $count);
    }

    /**
     * Get all available statuses.
     *
     * الحصول على جميع الحالات المتاحة.
     *
     * @return array<string, string>
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => __('notify::filament.campaigns.statuses.draft'),
            self::STATUS_SCHEDULED => __('notify::filament.campaigns.statuses.scheduled'),
            self::STATUS_SENDING => __('notify::filament.campaigns.statuses.sending'),
            self::STATUS_SENT => __('notify::filament.campaigns.statuses.sent'),
            self::STATUS_FAILED => __('notify::filament.campaigns.statuses.failed'),
            self::STATUS_CANCELLED => __('notify::filament.campaigns.statuses.cancelled'),
        ];
    }

    /**
     * Get all available types.
     *
     * الحصول على جميع الأنواع المتاحة.
     *
     * @return array<string, string>
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_DIRECT => __('notify::filament.campaigns.types.direct'),
            self::TYPE_TOPIC => __('notify::filament.campaigns.types.topic'),
            self::TYPE_BROADCAST => __('notify::filament.campaigns.types.broadcast'),
            self::TYPE_SEGMENT => __('notify::filament.campaigns.types.segment'),
        ];
    }

    /**
     * Scope to filter draft campaigns.
     *
     * نطاق لتصفية الحملات المسودة.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope to filter scheduled campaigns.
     *
     * نطاق لتصفية الحملات المجدولة.
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope to filter sent campaigns.
     *
     * نطاق لتصفية الحملات المرسلة.
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope to filter by type.
     *
     * نطاق للتصفية حسب النوع.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter campaigns ready to send.
     *
     * نطاق لتصفية الحملات الجاهزة للإرسال.
     */
    public function scopeReadyToSend(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now());
    }

    protected static function newFactory()
    {
        return \Asimnet\Notify\Database\Factories\CampaignFactory::new();
    }
}
