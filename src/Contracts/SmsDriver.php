<?php

namespace Asimnet\Notify\Contracts;

use Asimnet\Notify\DTOs\SmsSendResult;

/**
 * Contract for SMS providers.
 */
interface SmsDriver
{
    /**
     * Send a text message to the given recipient.
     *
     * @param  string  $to  E.164 or provider-acceptable phone number
     * @param  string  $message  Message body
     * @param  array<string, mixed>  $options  Driver-specific options (sender id, template id, media, etc.)
     */
    public function send(string $to, string $message, array $options = []): SmsSendResult;

    /**
     * Driver name for logging/identification.
     */
    public function name(): string;
}
