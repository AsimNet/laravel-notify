<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\DTOs\NotificationMessage;
use Asimnet\Notify\Facades\Notify;
use Asimnet\Notify\Models\DeviceToken;
use Asimnet\Notify\Models\NotificationLog;
use Asimnet\Notify\Testing\FakeFcmService;
use Asimnet\Notify\Tests\TestCase;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class TestNotificationTest extends TestCase
{
    protected FakeFcmService $fakeFcm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFcm = new FakeFcmService;
        $this->app->instance(FcmService::class, $this->fakeFcm);
    }

    public function test_admin_can_send_test_notification_to_self(): void
    {
        $user = $this->createUser();

        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'test_self_token',
        ]);

        Auth::login($user);

        $message = NotificationMessage::create('Test Title', 'Test Body');

        $result = Notify::sendTestToSelf($message);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['success_count']);

        $log = NotificationLog::latest()->first();

        $this->assertTrue($log->is_test);
        $this->assertEquals($user->id, $log->user_id);
    }

    public function test_test_notification_requires_authentication(): void
    {
        // Don't authenticate any user
        Auth::logout();

        $message = NotificationMessage::create('Test', 'Body');

        $this->expectException(InvalidArgumentException::class);

        Notify::sendTestToSelf($message);
    }

    public function test_test_notification_only_sends_to_own_devices(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        DeviceToken::factory()->create([
            'user_id' => $user1->id,
            'token' => 'user1_token',
        ]);

        DeviceToken::factory()->create([
            'user_id' => $user2->id,
            'token' => 'user2_token',
        ]);

        Auth::login($user1);

        $message = NotificationMessage::create('Test', 'Only for me');

        Notify::sendTestToSelf($message);

        // Verify only user1's token received the message
        $this->fakeFcm->assertMulticastIncludesToken('user1_token');

        // Verify user2's token was NOT in any multicast
        $allTokens = collect($this->fakeFcm->multicastMessages)
            ->flatMap(fn ($m) => $m['tokens'])
            ->all();

        $this->assertNotContains('user2_token', $allTokens);
    }

    public function test_test_notifications_can_be_filtered_in_logs(): void
    {
        // Create regular log entries
        NotificationLog::factory()->count(5)->create(['is_test' => false]);

        // Create test log entries
        NotificationLog::factory()->count(3)->test()->create();

        $this->assertEquals(5, NotificationLog::notTest()->count());
        $this->assertEquals(3, NotificationLog::onlyTest()->count());
    }

    public function test_test_notification_with_no_devices_returns_error(): void
    {
        $user = $this->createUser();
        // Don't create any devices for this user

        Auth::login($user);

        $message = NotificationMessage::create('Test', 'No devices');

        $result = Notify::sendTestToSelf($message);

        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
    }

    public function test_test_notification_logs_with_correct_user_id(): void
    {
        $user = $this->createUser();

        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'correct_user_token',
        ]);

        Auth::login($user);

        $message = NotificationMessage::create('User Test', 'Verify user ID');

        Notify::sendTestToSelf($message);

        $logs = NotificationLog::where('user_id', $user->id)
            ->where('is_test', true)
            ->get();

        $this->assertNotEmpty($logs);
        $this->assertEquals($user->id, $logs->first()->user_id);
    }

    public function test_multiple_test_notifications_all_marked_as_test(): void
    {
        $user = $this->createUser();

        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'multi_test_token',
        ]);

        Auth::login($user);

        // Send multiple test notifications
        Notify::sendTestToSelf(NotificationMessage::create('Test 1', 'First'));
        Notify::sendTestToSelf(NotificationMessage::create('Test 2', 'Second'));
        Notify::sendTestToSelf(NotificationMessage::create('Test 3', 'Third'));

        $testLogs = NotificationLog::where('user_id', $user->id)
            ->where('is_test', true)
            ->get();

        $this->assertGreaterThanOrEqual(3, $testLogs->count());

        foreach ($testLogs as $log) {
            $this->assertTrue($log->is_test);
        }
    }
}
