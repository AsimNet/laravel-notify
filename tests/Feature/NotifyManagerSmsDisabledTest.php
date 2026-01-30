<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\DTOs\NotificationMessage;
use Asimnet\Notify\Facades\Notify;
use Asimnet\Notify\Settings\NotifySettings;
use Asimnet\Notify\Tests\TestCase;

class NotifyManagerSmsDisabledTest extends TestCase
{
    public function test_sms_send_respects_disabled_setting(): void
    {
        /** @var NotifySettings $settings */
        $settings = app(NotifySettings::class);
        $settings->sms_enabled = false;
        $settings->save();

        $user = \Illuminate\Foundation\Auth\User::forceCreate([
            'name' => 'NoSms',
            'email' => 'no-sms@example.com',
            'password' => bcrypt('password'),
            'phone' => '+10000000000',
        ]);

        $message = NotificationMessage::create('Hi', 'Body');

        $result = Notify::to($user->id)->via('sms')->send($message);

        $this->assertFalse($result['success']);
        $this->assertSame('SMS channel is disabled', $result['error']);
    }
}
