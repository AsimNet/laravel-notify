<?php

namespace Asimnet\Notify\Models\Concerns;

use Asimnet\Notify\Models\DeviceToken;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Add this trait to your User model to enable FCM notifications.
 *
 * أضف هذه الخاصية إلى نموذج المستخدم الخاص بك لتمكين إشعارات FCM.
 *
 * Usage:
 *
 * ```php
 * class User extends Authenticatable
 * {
 *     use HasDeviceTokens;
 * }
 * ```
 *
 * @method HasMany deviceTokens()
 */
trait HasDeviceTokens
{
    /**
     * Get the user's device tokens.
     *
     * الحصول على رموز أجهزة المستخدم.
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class, 'user_id');
    }

    /**
     * Route notifications for the FCM channel.
     *
     * توجيه الإشعارات لقناة FCM.
     *
     * @return array<string>
     */
    public function routeNotificationForFcm(): array
    {
        return $this->deviceTokens()->pluck('token')->toArray();
    }
}
