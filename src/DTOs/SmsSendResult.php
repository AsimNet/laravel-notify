<?php

namespace Asimnet\Notify\DTOs;

/**
 * Standard result object for SMS drivers.
 */
class SmsSendResult
{
    public function __construct(
        public bool $success,
        public ?string $messageId = null,
        public ?string $error = null,
        public mixed $raw = null,
        public ?string $provider = null,
    ) {}

    public static function success(?string $messageId = null, mixed $raw = null, ?string $provider = null): self
    {
        return new self(true, $messageId, null, $raw, $provider);
    }

    public static function failure(?string $error = null, mixed $raw = null, ?string $provider = null): self
    {
        return new self(false, null, $error, $raw, $provider);
    }

    /**
     * Convert result to array for logging/serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message_id' => $this->messageId,
            'error' => $this->error,
            'raw' => $this->raw,
            'provider' => $this->provider,
        ];
    }
}
