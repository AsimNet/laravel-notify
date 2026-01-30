<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Channels\SmsChannel;
use Asimnet\Notify\Contracts\SmsDriver;
use Asimnet\Notify\SmsManager;
use Asimnet\Notify\Tests\TestCase;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

class SmsChannelTest extends TestCase
{
    public function test_sms_channel_sends_via_manager_driver(): void
    {
        $fakeDriver = new FakeSmsDriver;

        $manager = $this->app->make(SmsManager::class);
        $manager->extend('fake', fn () => $fakeDriver);
        config(['notify.sms.default_driver' => 'fake']);

        $notifiable = new class
        {
            use Notifiable;

            public string $phone = '+123456789';

            public function routeNotificationForSms(): string
            {
                return $this->phone;
            }
        };

        $notification = new class extends Notification
        {
            public function via($notifiable): array
            {
                return [SmsChannel::class];
            }

            public function toSms($notifiable): array
            {
                return [
                    'message' => 'Hello SMS',
                ];
            }
        };

        $notifiable->notify($notification);

        $this->assertCount(1, $fakeDriver->sent);
        $this->assertSame('+123456789', $fakeDriver->sent[0]['to']);
        $this->assertSame('Hello SMS', $fakeDriver->sent[0]['message']);
    }

    public function test_sms_channel_can_be_used_via_string_alias(): void
    {
        $fakeDriver = new FakeSmsDriver;

        $manager = $this->app->make(SmsManager::class);
        $manager->extend('fake', fn () => $fakeDriver);
        config(['notify.sms.default_driver' => 'fake']);

        $notifiable = new class
        {
            use Notifiable;

            public string $phone = '+111222333';

            public function routeNotificationForSms(): string
            {
                return $this->phone;
            }
        };

        $notification = new class extends Notification
        {
            public function via($notifiable): array
            {
                return ['sms']; // using alias registered in provider
            }

            public function toSms($notifiable): array
            {
                return ['message' => 'Alias route'];
            }
        };

        $notifiable->notify($notification);

        $this->assertSame('+111222333', $fakeDriver->sent[0]['to']);
        $this->assertSame('Alias route', $fakeDriver->sent[0]['message']);
    }
}

class FakeSmsDriver implements SmsDriver
{
    public array $sent = [];

    public function send(string $to, string $message, array $options = []): \Asimnet\Notify\DTOs\SmsSendResult
    {
        $this->sent[] = compact('to', 'message', 'options');

        return \Asimnet\Notify\DTOs\SmsSendResult::success('fake-id', ['to' => $to], $this->name());
    }

    public function name(): string
    {
        return 'fake';
    }
}
