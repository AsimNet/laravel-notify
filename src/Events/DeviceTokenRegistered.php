<?php

namespace Asimnet\Notify\Events;

use Asimnet\Notify\Models\DeviceToken;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a new device token is registered.
 *
 * Used to trigger auto-subscription to default topics.
 */
class DeviceTokenRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly DeviceToken $deviceToken,
        public readonly bool $isFirstDevice = false
    ) {}
}
