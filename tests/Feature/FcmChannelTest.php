<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Channels\FcmChannel;
use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\DTOs\NotificationMessage;
use Asimnet\Notify\Models\DeviceToken;
use Asimnet\Notify\Testing\FakeFcmService;
use Asimnet\Notify\Tests\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

/**
 * Test notification class for FcmChannel tests.
 */
class TestFcmNotification extends Notification
{
    public function __construct(
        public string $title = 'Test Title',
        public string $body = 'Test Body',
        public ?string $imageUrl = null,
        public ?string $actionUrl = null,
        public array $data = []
    ) {}

    public function via($notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toFcm($notifiable): NotificationMessage
    {
        $message = NotificationMessage::create($this->title, $this->body);

        if ($this->imageUrl) {
            $message = $message->withImage($this->imageUrl);
        }

        if ($this->actionUrl) {
            $message = $message->withActionUrl($this->actionUrl);
        }

        if (! empty($this->data)) {
            $message = $message->withData($this->data);
        }

        return $message;
    }
}

/**
 * Test notification without toFcm method.
 */
class TestInvalidNotification extends Notification
{
    public function via($notifiable): array
    {
        return [FcmChannel::class];
    }
}

/**
 * Test user with custom routeNotificationForFcm method.
 */
class TestUserWithCustomRouting extends User
{
    protected $table = 'users';

    protected array $customTokens = [];

    public function setCustomTokens(array $tokens): void
    {
        $this->customTokens = $tokens;
    }

    public function routeNotificationForFcm(): array
    {
        return $this->customTokens;
    }
}

class FcmChannelTest extends TestCase
{
    protected FakeFcmService $fakeFcm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFcm = new FakeFcmService;
        $this->app->instance(FcmService::class, $this->fakeFcm);
    }

    /** @test */
    public function it_sends_notification_to_user_devices(): void
    {
        $user = $this->createUser();
        $device = DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'user_device_token_123',
        ]);

        $channel = $this->app->make(FcmChannel::class);
        $channel->send($user, new TestFcmNotification);

        $this->fakeFcm->assertMulticastIncludesToken('user_device_token_123');
    }

    /** @test */
    public function it_sends_to_multiple_devices(): void
    {
        $user = $this->createUser();

        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'token_device_1',
        ]);
        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'token_device_2',
        ]);
        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'token_device_3',
        ]);

        $channel = $this->app->make(FcmChannel::class);
        $channel->send($user, new TestFcmNotification);

        $this->fakeFcm->assertMulticastSent(3);
    }

    /** @test */
    public function it_skips_when_no_devices(): void
    {
        $user = $this->createUser();
        // No devices created for user

        $channel = $this->app->make(FcmChannel::class);
        $channel->send($user, new TestFcmNotification);

        $this->fakeFcm->assertNothingSent();
    }

    /** @test */
    public function it_removes_invalid_tokens_on_unregistered_error(): void
    {
        $user = $this->createUser();

        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'valid_token_abc',
        ]);
        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'invalid_token_xyz',
        ]);

        // Mark one token as invalid
        $this->fakeFcm->markTokensInvalid(['invalid_token_xyz']);

        $channel = $this->app->make(FcmChannel::class);
        $channel->send($user, new TestFcmNotification);

        // Invalid token should be deleted
        $this->assertDatabaseMissing(config('notify.tables.device_tokens'), [
            'token' => 'invalid_token_xyz',
        ]);

        // Valid token should still exist
        $this->assertDatabaseHas(config('notify.tables.device_tokens'), [
            'token' => 'valid_token_abc',
        ]);
    }

    /** @test */
    public function it_throws_exception_for_notification_without_to_fcm(): void
    {
        $user = $this->createUser();
        DeviceToken::factory()->create(['user_id' => $user->id]);

        $channel = $this->app->make(FcmChannel::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('toFcm');

        $channel->send($user, new TestInvalidNotification);
    }

    /** @test */
    public function it_sends_notification_with_image_url(): void
    {
        $user = $this->createUser();
        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'image_test_token',
        ]);

        $notification = new TestFcmNotification(
            title: 'Image Test',
            body: 'Testing image URL',
            imageUrl: 'https://example.com/image.png'
        );

        $channel = $this->app->make(FcmChannel::class);
        $channel->send($user, $notification);

        $lastMessage = $this->fakeFcm->getLastSentMessage();
        $this->assertNotNull($lastMessage);
        $this->assertEquals('https://example.com/image.png', $lastMessage->imageUrl);
    }

    /** @test */
    public function it_sends_notification_with_action_url(): void
    {
        $user = $this->createUser();
        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'action_test_token',
        ]);

        $notification = new TestFcmNotification(
            title: 'Action Test',
            body: 'Testing action URL',
            actionUrl: 'app://open/profile'
        );

        $channel = $this->app->make(FcmChannel::class);
        $channel->send($user, $notification);

        $lastMessage = $this->fakeFcm->getLastSentMessage();
        $this->assertNotNull($lastMessage);
        $this->assertEquals('app://open/profile', $lastMessage->actionUrl);
    }

    /** @test */
    public function it_sends_notification_with_custom_data(): void
    {
        $user = $this->createUser();
        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'data_test_token',
        ]);

        $notification = new TestFcmNotification(
            title: 'Data Test',
            body: 'Testing custom data',
            data: ['custom_key' => 'custom_value', 'order_id' => '12345']
        );

        $channel = $this->app->make(FcmChannel::class);
        $channel->send($user, $notification);

        $lastMessage = $this->fakeFcm->getLastSentMessage();
        $this->assertNotNull($lastMessage);
        $this->assertEquals('custom_value', $lastMessage->data['custom_key']);
        $this->assertEquals('12345', $lastMessage->data['order_id']);
    }

    /** @test */
    public function it_uses_route_notification_for_fcm_if_available(): void
    {
        // Create a user with custom routing
        $user = new TestUserWithCustomRouting;
        $user->forceCreate([
            'name' => 'Custom Routing User',
            'email' => 'custom@example.com',
            'password' => bcrypt('password'),
        ]);

        // Set custom tokens (these don't need to exist in DB)
        $user->setCustomTokens(['custom_routed_token_1', 'custom_routed_token_2']);

        $channel = $this->app->make(FcmChannel::class);
        $channel->send($user, new TestFcmNotification);

        $this->fakeFcm->assertMulticastIncludesToken('custom_routed_token_1');
        $this->fakeFcm->assertMulticastIncludesToken('custom_routed_token_2');
        $this->fakeFcm->assertMulticastSent(2);
    }
}
