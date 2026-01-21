<?php

namespace Asimnet\Notify\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a device token is deleted.
 *
 * Used to clean up FCM topic subscriptions.
 */
class DeviceTokenDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $token,
        public readonly ?string $tenantId = null
    ) {}
}
