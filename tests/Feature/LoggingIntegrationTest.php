<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\DTOs\NotificationMessage;
use Asimnet\Notify\Facades\Notify;
use Asimnet\Notify\Models\DeviceToken;
use Asimnet\Notify\Models\NotificationLog;
use Asimnet\Notify\Testing\FakeFcmService;
use Asimnet\Notify\Tests\TestCase;
use Asimnet\Notify\Tests\TestUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoggingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected FakeFcmService $fakeFcm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFcm = new FakeFcmService;
        $this->app->instance(FcmService::class, $this->fakeFcm);

        // Ensure logging is enabled
        config(['notify.logging.enabled' => true]);
    }

    protected function createUserWithDevice(): TestUser
    {
        $user = TestUser::forceCreate([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'test_token_123',
        ]);

        return $user;
    }

    public function test_send_to_user_creates_log_entry(): void
    {
        $user = $this->createUserWithDevice();
        $message = NotificationMessage::create('Test Title', 'Test Body');

        Notify::sendToUser($user->id, $message);

        $this->assertDatabaseCount('notify_logs', 1);

        $log = NotificationLog::first();
        $this->assertEquals('fcm', $log->channel);
        $this->assertEquals(NotificationLog::STATUS_SENT, $log->status);
        $this->assertEquals('Test Title', $log->title);
        $this->assertEquals('Test Body', $log->body);
        $this->assertNotNull($log->sent_at);
    }

    public function test_send_to_users_creates_batch_log_entry(): void
    {
        $user1 = $this->createUserWithDevice();
        $user2 = TestUser::forceCreate([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
        ]);
        DeviceToken::factory()->create(['user_id' => $user2->id]);

        $message = NotificationMessage::create('Batch Title', 'Batch Body');

        Notify::sendToUsers([$user1->id, $user2->id], $message);

        $this->assertDatabaseCount('notify_logs', 1);

        $log = NotificationLog::first();
        $this->assertEquals('Batch Title', $log->title);
        $this->assertEquals(NotificationLog::STATUS_SENT, $log->status);
    }

    public function test_send_to_topic_creates_log_entry(): void
    {
        $message = NotificationMessage::create('Topic Title', 'Topic Body');

        Notify::sendToTopic('news', $message);

        $this->assertDatabaseCount('notify_logs', 1);

        $log = NotificationLog::first();
        $this->assertEquals('fcm', $log->channel);
        $this->assertEquals('Topic Title', $log->title);
        $this->assertNotNull($log->external_id);
    }

    public function test_broadcast_creates_log_entry(): void
    {
        $message = NotificationMessage::create('Broadcast Title', 'Broadcast Body');

        Notify::broadcast($message);

        $this->assertDatabaseCount('notify_logs', 1);

        $log = NotificationLog::first();
        $this->assertEquals('Broadcast Title', $log->title);
    }

    public function test_failed_send_logs_with_error(): void
    {
        $user = $this->createUserWithDevice();
        $message = NotificationMessage::create('Test', 'Body');

        // Configure fake to fail
        $this->fakeFcm->shouldFailWith('UNREGISTERED');

        Notify::sendToUser($user->id, $message);

        $log = NotificationLog::first();
        $this->assertEquals(NotificationLog::STATUS_FAILED, $log->status);
        $this->assertNotNull($log->error_message);
    }

    public function test_logging_can_be_disabled(): void
    {
        config(['notify.logging.enabled' => false]);

        $user = $this->createUserWithDevice();
        $message = NotificationMessage::create('Test', 'Body');

        Notify::sendToUser($user->id, $message);

        $this->assertDatabaseCount('notify_logs', 0);
    }

    public function test_payload_stored_when_config_enabled(): void
    {
        config(['notify.logging.store_payload' => true]);

        $user = $this->createUserWithDevice();
        $message = NotificationMessage::create('Test', 'Body')
            ->withData(['key' => 'value']);

        Notify::sendToUser($user->id, $message);

        $log = NotificationLog::first();
        $this->assertNotNull($log->payload);
        $this->assertIsArray($log->payload);
    }

    public function test_payload_not_stored_when_config_disabled(): void
    {
        config(['notify.logging.store_payload' => false]);

        $user = $this->createUserWithDevice();
        $message = NotificationMessage::create('Test', 'Body');

        Notify::sendToUser($user->id, $message);

        $log = NotificationLog::first();
        $this->assertNull($log->payload);
    }

    public function test_external_id_captured_from_topic_send(): void
    {
        $message = NotificationMessage::create('Test', 'Body');

        Notify::sendToTopic('news', $message);

        $log = NotificationLog::first();
        // FakeFcmService generates topic message IDs like 'fake-topic-message-...'
        $this->assertNotNull($log->external_id);
        $this->assertStringContainsString('fake-topic-message-', $log->external_id);
    }
}
