<?php

namespace Asimnet\Notify\Events;

use Asimnet\Notify\Models\Topic;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a user unsubscribes from a topic.
 *
 * Used to sync unsubscription with FCM.
 */
class TopicUnsubscribed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Topic  $topic  The topic being unsubscribed from
     * @param  array<string>  $deviceTokens  User's FCM device tokens to unsubscribe
     */
    public function __construct(
        public readonly Topic $topic,
        public readonly array $deviceTokens
    ) {}
}
