<?php

namespace Asimnet\Notify\Models;

use Asimnet\Notify\Models\Concerns\BelongsToTenantOptionally;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicSubscription extends Model
{
    use BelongsToTenantOptionally;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'topic_id',
        'user_id',
        'fcm_synced',
        'fcm_enabled',
        'sms_enabled',
        'wba_enabled',
    ];

    protected function casts(): array
    {
        return [
            'fcm_synced' => 'boolean',
            'fcm_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'wba_enabled' => 'boolean',
        ];
    }

    public function getTable(): string
    {
        return config('notify.tables.topic_subscriptions', 'notify_topic_subscriptions');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'));
    }

    /**
     * Mark subscription as synced with FCM.
     */
    public function markSynced(): bool
    {
        return $this->update(['fcm_synced' => true]);
    }

    /**
     * Mark subscription as unsynced (needs FCM update).
     */
    public function markUnsynced(): bool
    {
        return $this->update(['fcm_synced' => false]);
    }

    /**
     * Scope to filter unsynced subscriptions.
     */
    public function scopeUnsynced($query)
    {
        return $query->where('fcm_synced', false);
    }

    /**
     * Scope to filter synced subscriptions.
     */
    public function scopeSynced($query)
    {
        return $query->where('fcm_synced', true);
    }

    protected static function newFactory()
    {
        return \Asimnet\Notify\Database\Factories\TopicSubscriptionFactory::new();
    }
}
