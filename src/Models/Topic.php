<?php

namespace Asimnet\Notify\Models;

use Asimnet\Notify\Models\Concerns\BelongsToTenantOptionally;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Topic extends Model
{
    use BelongsToTenantOptionally;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'is_public',
        'is_default',
        'subscriber_count',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_default' => 'boolean',
            'subscriber_count' => 'integer',
        ];
    }

    public function getTable(): string
    {
        return config('notify.tables.topics', 'notify_topics');
    }

    protected static function booted(): void
    {
        static::creating(function (Topic $topic) {
            if (empty($topic->slug)) {
                $topic->slug = Str::slug($topic->name);
            }
        });
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TopicSubscription::class);
    }

    /**
     * Check if a user is subscribed to this topic.
     */
    public function isSubscribedByUser($user): bool
    {
        $userId = is_object($user) ? $user->id : $user;

        return $this->subscriptions()
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Scope to filter public topics.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to filter default topics (for auto-subscription).
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the FCM topic name (without /topics/ prefix).
     */
    public function getFcmTopicName(): string
    {
        $tenantId = $this->tenant_id;

        // Prefix with tenant ID if multi-tenant
        if ($tenantId) {
            return "{$tenantId}_{$this->slug}";
        }

        return $this->slug;
    }

    protected static function newFactory()
    {
        return \Asimnet\Notify\Database\Factories\TopicFactory::new();
    }
}
