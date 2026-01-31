<?php

namespace Asimnet\Notify\Channels;

use Asimnet\Notify\DTOs\SmsSendResult;
use Asimnet\Notify\SmsManager;
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
            throw new InvalidArgumentException(
                'SMS notification requires a message and destination phone. '.
                'Provide "to" in toSms() or implement routeNotificationForSms($notification).'
            );
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

        return null;
    }
}
