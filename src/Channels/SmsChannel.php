<?php

namespace Asimnet\Notify\Channels;

use Asimnet\Notify\DTOs\SmsSendResult;
use Asimnet\Notify\SmsManager;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Laravel Notification channel that routes via SmsManager drivers.
 */
class SmsChannel
{
    public function __construct(protected SmsManager $smsManager) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            throw new InvalidArgumentException('Notification must implement toSms($notifiable): array{message:string, to?:string, options?:array}');
        }

        $payload = $notification->toSms($notifiable);
        $message = $payload['message'] ?? null;
        $to = $payload['to'] ?? $this->routeNotificationForSms($notifiable, $notification);
        $options = $payload['options'] ?? [];
        $driver = $payload['driver'] ?? null;

        if (! $message || ! $to) {
            Log::warning('SMS: missing message or recipient', [
                'has_message' => (bool) $message,
                'to' => $to,
                'notifiable' => is_object($notifiable) ? get_class($notifiable) : gettype($notifiable),
            ]);

            return;
        }

        $smsDriver = $this->smsManager->driver($driver);

        /** @var SmsSendResult $result */
        $result = $smsDriver->send($to, $message, $options);

        Log::info('SMS sent', [
            'driver' => $smsDriver->name(),
            'to' => $to,
            'success' => $result->success,
            'message_id' => $result->messageId,
            'error' => $result->error,
        ]);
    }

    protected function routeNotificationForSms(mixed $notifiable, Notification $notification): ?string
    {
        if (method_exists($notifiable, 'routeNotificationForSms')) {
            return $notifiable->routeNotificationForSms($notification);
        }

        if (method_exists($notifiable, 'routeNotificationForVonage')) {
            return $notifiable->routeNotificationForVonage($notification);
        }

        foreach (['mobile', 'phone', 'whatsapp_phone', 'phone_number'] as $attr) {
            $value = $this->safeGetAttribute($notifiable, $attr);
            if (! empty($value)) {
                return $value;
            }
        }

        return null;
    }

    protected function safeGetAttribute(mixed $notifiable, string $attr): ?string
    {
        if ($notifiable instanceof Model) {
            return $notifiable->getAttributes()[$attr] ?? null;
        }

        try {
            return $notifiable->{$attr} ?? null;
        } catch (MissingAttributeException) {
            return null;
        }
    }
}
