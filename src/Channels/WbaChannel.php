<?php

namespace Asimnet\Notify\Channels;

use Asimnet\Notify\Services\NotificationLogger;
use Asimnet\WbaFilament\Services\WbaService;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * WhatsApp Business (WBA) notification channel using wba-filament package.
 *
 * Notification must implement toWba($notifiable): array with:
 * - template_id OR template_name & language
 * - parameters: array
 * - metadata: array (optional)
 * - phone: string (optional; otherwise resolved from notifiable)
 */
class WbaChannel
{
    public function __construct(
        private readonly WbaService $wba,
        private readonly NotificationLogger $logger
    ) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWba')) {
            throw new InvalidArgumentException('Notification must define toWba($notifiable) for WBA channel.');
        }

        $payload = $notification->toWba($notifiable);
        $phone = $payload['phone'] ?? $this->resolvePhone($notifiable);

        if (! $phone) {
            Log::warning('WBA: missing phone for notifiable', ['notifiable' => get_class($notifiable)]);

            return;
        }

        $templateId = $payload['template_id'] ?? null;
        $templateName = $payload['template_name'] ?? null;
        $language = $payload['language'] ?? config('notify.wba.default_language', 'ar');
        $parameters = $payload['parameters'] ?? [];
        $metadata = $payload['metadata'] ?? [];

        if (! $templateId && ! $templateName) {
            throw new InvalidArgumentException('WBA payload requires template_id or template_name.');
        }

        $message = $templateId
            ? $this->wba->send($phone, $templateId, $parameters, null, null, $metadata)
            : $this->wba->sendByTemplateName($phone, $templateName, $language, $parameters);

        // Log as sms-like channel 'wba'
        $this->logger->logSend(
            new \Asimnet\Notify\DTOs\NotificationMessage(
                title: $payload['title'] ?? $templateName ?? 'WBA',
                body: $payload['body'] ?? null,
                data: $parameters
            ),
            [
                'success' => true,
                'message_id' => $message->id,
            ],
            'wba',
            method_exists($notifiable, 'getKey') ? $notifiable->getKey() : null
        );
    }

    protected function resolvePhone(mixed $notifiable): ?string
    {
        if (method_exists($notifiable, 'routeNotificationForWba')) {
            return $notifiable->routeNotificationForWba();
        }

        if (method_exists($notifiable, 'routeNotificationForSms')) {
            return $notifiable->routeNotificationForSms();
        }

        foreach (['phone', 'mobile', 'whatsapp_phone', 'phone_number'] as $attr) {
            $value = $this->safeGetAttribute($notifiable, $attr);
            if (! empty($value)) {
                return $value;
            }
        }

        return null;
    }

    protected function safeGetAttribute(mixed $notifiable, string $attr): ?string
    {
        // If it's a model, use raw attributes to avoid MissingAttributeException.
        if ($notifiable instanceof Model) {
            $attrs = $notifiable->getAttributes();

            return $attrs[$attr] ?? null;
        }

        try {
            return $notifiable->{$attr} ?? null;
        } catch (MissingAttributeException) {
            return null;
        }
    }
}
