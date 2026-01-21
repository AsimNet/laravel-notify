<?php

namespace Asimnet\Notify\Models;

use Asimnet\Notify\Models\Concerns\BelongsToTenantOptionally;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    use BelongsToTenantOptionally;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'token',
        'platform',
        'device_name',
        'last_active_at',
    ];

    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return config('notify.tables.device_tokens', 'notify_device_tokens');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'));
    }

    /**
     * Update the last_active_at timestamp.
     */
    public function touchLastActive(): bool
    {
        $this->last_active_at = now();

        return $this->save();
    }

    /**
     * Scope to filter by platform.
     */
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to filter stale tokens (not active for X days).
     */
    public function scopeStale($query, int $days = 30)
    {
        return $query->where('last_active_at', '<', now()->subDays($days))
            ->orWhereNull('last_active_at');
    }

    protected static function newFactory()
    {
        return \Asimnet\Notify\Database\Factories\DeviceTokenFactory::new();
    }
}
