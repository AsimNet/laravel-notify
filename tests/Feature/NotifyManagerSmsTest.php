<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\DTOs\NotificationMessage;
use Asimnet\Notify\Facades\Notify;
use Asimnet\Notify\SmsManager;
use Asimnet\Notify\Tests\TestCase;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class NotifyManagerSmsTest extends TestCase
{
    public function test_send_sms_to_user_id(): void
    {
        // Create fake user with phone
        $user = TestNotifyUser::forceCreate([
            'name' => 'SMS User',
            'email' => 'sms@example.com',
            'password' => bcrypt('password'),
            'phone' => '+19995551234',
        ]);

        // Register fake SMS driver
        $fake = new FakeDriver;
        $this->app->make(SmsManager::class)->extend('fake', fn () => $fake);
        config(['notify.sms.default_driver' => 'fake', 'notify.sms.enabled' => true]);

        $message = NotificationMessage::create('Hi', 'SMS body');

        $result = Notify::to($user->id)->via('sms')->send($message);

        $this->assertTrue($result['success']);
        $this->assertSame('+19995551234', $fake->lastTo);

        // Assert log recorded with channel sms
        $log = DB::table(config('notify.tables.logs', 'notify_logs'))->first();
        $this->assertSame('sms', $log->channel);
        $this->assertSame('Hi', $log->title);
    }
}

class TestNotifyUser extends \Illuminate\Foundation\Auth\User
{
    use Notifiable;

    protected $table = 'users';
}

class FakeDriver implements \Asimnet\Notify\Contracts\SmsDriver
{
    public ?string $lastTo = null;

    public function send(string $to, string $message, array $options = []): \Asimnet\Notify\DTOs\SmsSendResult
    {
        $this->lastTo = $to;

        return \Asimnet\Notify\DTOs\SmsSendResult::success('fake-id');
    }

    public function name(): string
    {
        return 'fake';
    }
}
