<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\DTOs\NotificationMessage;
use Asimnet\Notify\Facades\Notify;
use Asimnet\Notify\Models\DeviceToken;
use Asimnet\Notify\Testing\FakeFcmService;
use Asimnet\Notify\Tests\TestCase;
use InvalidArgumentException;

class NotifyManagerTest extends TestCase
{
    protected FakeFcmService $fakeFcm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFcm = new FakeFcmService;
        $this->app->instance(FcmService::class, $this->fakeFcm);
    }

    /** @test */
    public function send_to_user_sends_to_all_user_devices(): void
    {
        $user = $this->createUser();

        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'user_token_1',
        ]);
        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'user_token_2',
        ]);

        $message = NotificationMessage::create('Test Title', 'Test Body');

        $result = Notify::sendToUser($user->id, $message);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['success_count']);
        $this->fakeFcm->assertMulticastSent(2);
    }

    /** @test */
    public function send_to_user_returns_error_when_no_devices(): void
    {
        $user = $this->createUser();
        // No devices created

        $message = NotificationMessage::create('Test Title', 'Test Body');

        $result = Notify::sendToUser($user->id, $message);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['success_count']);
        $this->assertEquals('No devices registered for user', $result['error']);
    }

    /** @test */
    public function send_to_users_sends_to_multiple_users(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $user3 = $this->createUser();

        DeviceToken::factory()->create(['user_id' => $user1->id, 'token' => 'token_u1']);
        DeviceToken::factory()->create(['user_id' => $user2->id, 'token' => 'token_u2']);
        DeviceToken::factory()->create(['user_id' => $user3->id, 'token' => 'token_u3']);

        $message = NotificationMessage::create('Multi-User', 'Message for multiple users');

        $result = Notify::sendToUsers([$user1->id, $user2->id, $user3->id], $message);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['success_count']);
        $this->fakeFcm->assertMulticastSent(3);
    }

    /** @test */
    public function send_to_users_returns_error_for_empty_array(): void
    {
        $message = NotificationMessage::create('Empty', 'No users');

        $result = Notify::sendToUsers([], $message);

        $this->assertFalse($result['success']);
        $this->assertEquals('No user IDs provided', $result['error']);
    }

    /** @test */
    public function send_to_topic_sends_to_fcm_topic(): void
    {
        $message = NotificationMessage::create('News Update', 'Breaking news content');

        $result = Notify::sendToTopic('news', $message);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['message_id']);
        $this->fakeFcm->assertSentToTopic('news');
    }

    /** @test */
    public function broadcast_sends_to_public_topic(): void
    {
        // Set the default topic in config
        config(['notify.default_topics' => ['public']]);

        $message = NotificationMessage::create('Broadcast', 'Message for everyone');

        $result = Notify::broadcast($message);

        $this->assertTrue($result['success']);
        $this->fakeFcm->assertSentToTopic('public');
    }

    /** @test */
    public function fluent_api_to_single_user(): void
    {
        $user = $this->createUser();
        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'fluent_single_token',
        ]);

        $message = NotificationMessage::create('Fluent API', 'Single user test');

        $result = Notify::to($user->id)->send($message);

        $this->assertTrue($result['success']);
        $this->fakeFcm->assertMulticastSent(1);
        $this->fakeFcm->assertMulticastIncludesToken('fluent_single_token');
    }

    /** @test */
    public function fluent_api_to_multiple_users(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        DeviceToken::factory()->create(['user_id' => $user1->id, 'token' => 'fluent_multi_1']);
        DeviceToken::factory()->create(['user_id' => $user2->id, 'token' => 'fluent_multi_2']);

        $message = NotificationMessage::create('Fluent API', 'Multiple users test');

        $result = Notify::to([$user1->id, $user2->id])->send($message);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['success_count']);
        $this->fakeFcm->assertMulticastSent(2);
    }

    /** @test */
    public function fluent_api_to_topic(): void
    {
        $message = NotificationMessage::create('Topic via Fluent', 'Announcement message');

        $result = Notify::to('topic:announcements')->send($message);

        $this->assertTrue($result['success']);
        $this->fakeFcm->assertSentToTopic('announcements');
    }

    /** @test */
    public function fluent_api_without_recipients_throws(): void
    {
        $message = NotificationMessage::create('No Recipients', 'This should fail');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Recipients are required');

        // Send without calling to() first
        Notify::send($message);
    }

    /** @test */
    public function send_data_to_user_sends_data_only_message(): void
    {
        $user = $this->createUser();
        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'data_only_token',
        ]);

        $result = Notify::sendDataToUser($user->id, ['event' => 'sync', 'payload' => 'refresh']);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['success_count']);
        $this->fakeFcm->assertDataSent('data_only_token');
    }

    /** @test */
    public function notification_message_with_all_fields(): void
    {
        $user = $this->createUser();
        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'full_message_token',
        ]);

        $message = NotificationMessage::create('Full Message', 'Complete notification')
            ->withImage('https://example.com/banner.jpg')
            ->withActionUrl('myapp://open/orders/123')
            ->withData(['order_id' => 'ORD123', 'status' => 'shipped'])
            ->withAnalyticsLabel('order_shipped_campaign');

        $result = Notify::sendToUser($user->id, $message);

        $this->assertTrue($result['success']);

        // Verify all fields are preserved
        $lastMessage = $this->fakeFcm->getLastSentMessage();
        $this->assertNotNull($lastMessage);
        $this->assertEquals('Full Message', $lastMessage->title);
        $this->assertEquals('Complete notification', $lastMessage->body);
        $this->assertEquals('https://example.com/banner.jpg', $lastMessage->imageUrl);
        $this->assertEquals('myapp://open/orders/123', $lastMessage->actionUrl);
        $this->assertEquals('ORD123', $lastMessage->data['order_id']);
        $this->assertEquals('shipped', $lastMessage->data['status']);
        $this->assertEquals('order_shipped_campaign', $lastMessage->analyticsLabel);
    }
}
