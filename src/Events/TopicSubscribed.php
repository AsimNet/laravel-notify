<?php

namespace Asimnet\Notify\Events;

use Asimnet\Notify\Models\TopicSubscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a user subscribes to a topic.
 *
 * Used to sync subscription with FCM.
 */
class TopicSubscribed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  TopicSubscription  $subscription  The subscription record
     * @param  array<string>  $deviceTokens  User's FCM device tokens to subscribe
     */
    public function __construct(
        public readonly TopicSubscription $subscription,
        public readonly array $deviceTokens
    ) {
        $this->subscription->fcm_enabled ??= true;
        $this->subscription->sms_enabled ??= false;
        $this->subscription->wba_enabled ??= false;
    }
}
