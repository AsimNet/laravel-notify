<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\DTOs\NotificationMessage;
use Asimnet\Notify\Facades\Notify;
use Asimnet\Notify\Models\DeviceToken;
use Asimnet\Notify\Models\NotificationTemplate;
use Asimnet\Notify\Models\ScheduledNotification;
use Asimnet\Notify\Testing\FakeFcmService;
use Asimnet\Notify\Tests\TestCase;
use InvalidArgumentException;

class ScheduledNotificationTest extends TestCase
{
    protected FakeFcmService $fakeFcm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFcm = new FakeFcmService;
        $this->app->instance(FcmService::class, $this->fakeFcm);
    }

    public function test_notification_can_be_scheduled(): void
    {
        $user = $this->createUser();

        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'scheduled_test_token',
        ]);

        $message = NotificationMessage::create('Test Title', 'Test Body');
        $scheduledAt = now()->addHours(2);

        $scheduled = Notify::schedule($user->id, $message, $scheduledAt);

        $this->assertDatabaseHas('notify_scheduled_notifications', [
            'user_id' => $user->id,
            'title' => 'Test Title',
            'body' => 'Test Body',
        ]);

        $this->assertEquals(ScheduledNotification::STATUS_PENDING, $scheduled->status);
        $this->assertTrue($scheduled->canBeCancelled());
        $this->assertNotNull($scheduled->scheduled_at);
    }

    public function test_scheduled_notification_can_be_cancelled(): void
    {
        $user = $this->createUser();

        $scheduled = ScheduledNotification::factory()
            ->forUser($user->id)
            ->scheduledFor(now()->addHours(2))
            ->create();

        $this->assertTrue($scheduled->canBeCancelled());

        $scheduled->cancel($user->id, 'No longer needed');

        $this->assertNotNull($scheduled->fresh()->cancelled_at);
        $this->assertEquals($user->id, $scheduled->fresh()->cancelled_by);
        $this->assertEquals(ScheduledNotification::STATUS_CANCELLED, $scheduled->fresh()->status);
        $this->assertFalse($scheduled->fresh()->canBeCancelled());
    }

    public function test_sent_notification_cannot_be_cancelled(): void
    {
        $user = $this->createUser();

        $scheduled = ScheduledNotification::factory()
            ->forUser($user->id)
            ->sent()
            ->create();

        $this->expectException(InvalidArgumentException::class);

        $scheduled->cancel();
    }

    public function test_already_cancelled_notification_cannot_be_cancelled_again(): void
    {
        $user = $this->createUser();

        $scheduled = ScheduledNotification::factory()
            ->forUser($user->id)
            ->cancelled()
            ->scheduledFor(now()->addHours(2))
            ->create();

        $this->expectException(InvalidArgumentException::class);

        $scheduled->cancel();
    }

    public function test_past_due_notification_cannot_be_cancelled(): void
    {
        $user = $this->createUser();

        // Due notifications have scheduled_at in the past
        $scheduled = ScheduledNotification::factory()
            ->forUser($user->id)
            ->due()
            ->create();

        $this->assertFalse($scheduled->canBeCancelled());
    }

    public function test_due_scope_returns_only_pending_due_notifications(): void
    {
        $user = $this->createUser();

        // Should be included: due (past scheduled time, not sent/cancelled/failed)
        $due = ScheduledNotification::factory()
            ->forUser($user->id)
            ->due()
            ->create();

        // Should be excluded: future scheduled time
        $future = ScheduledNotification::factory()
            ->forUser($user->id)
            ->scheduledFor(now()->addHours(5))
            ->create();

        // Should be excluded: already sent
        $sent = ScheduledNotification::factory()
            ->forUser($user->id)
            ->sent()
            ->create();

        // Should be excluded: cancelled
        $cancelled = ScheduledNotification::factory()
            ->forUser($user->id)
            ->due()
            ->cancelled()
            ->create();

        // Should be excluded: failed
        $failed = ScheduledNotification::factory()
            ->forUser($user->id)
            ->due()
            ->failed()
            ->create();

        $dueNotifications = ScheduledNotification::due()->get();

        $this->assertCount(1, $dueNotifications);
        $this->assertEquals($due->id, $dueNotifications->first()->id);
    }

    public function test_schedule_from_template(): void
    {
        $user = $this->createUser();

        $template = NotificationTemplate::factory()->create([
            'slug' => 'test-template',
            'title' => 'Template Title',
            'body' => 'Template body for {custom}',
        ]);

        $scheduledAt = now()->addHours(1);

        $scheduled = Notify::scheduleFromTemplate(
            'test-template',
            $user->id,
            $scheduledAt,
            ['custom' => 'value']
        );

        $this->assertDatabaseHas('notify_scheduled_notifications', [
            'user_id' => $user->id,
            'template_id' => $template->id,
        ]);

        $this->assertEquals(['custom' => 'value'], $scheduled->template_variables);
    }

    public function test_scheduled_notification_with_image_and_payload(): void
    {
        $user = $this->createUser();

        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'image_payload_token',
        ]);

        $message = NotificationMessage::create('Image Test', 'Body with image')
            ->withImage('https://example.com/image.png')
            ->withData(['action_url' => 'myapp://path', 'extra_key' => 'extra_value']);

        $scheduledAt = now()->addHours(3);

        $scheduled = Notify::schedule($user->id, $message, $scheduledAt);

        $this->assertEquals('https://example.com/image.png', $scheduled->image_url);
        $this->assertEquals('myapp://path', $scheduled->action_url);
        $this->assertIsArray($scheduled->payload);
        $this->assertEquals('extra_value', $scheduled->payload['extra_key']);
    }

    public function test_pending_scope_returns_only_unprocessed_notifications(): void
    {
        $user = $this->createUser();

        // Should be included: pending (no timestamps set)
        $pending = ScheduledNotification::factory()
            ->forUser($user->id)
            ->create();

        // Should be excluded: sent
        $sent = ScheduledNotification::factory()
            ->forUser($user->id)
            ->sent()
            ->create();

        // Should be excluded: cancelled
        $cancelled = ScheduledNotification::factory()
            ->forUser($user->id)
            ->cancelled()
            ->create();

        // Should be excluded: failed
        $failed = ScheduledNotification::factory()
            ->forUser($user->id)
            ->failed()
            ->create();

        $pendingNotifications = ScheduledNotification::pending()->get();

        $this->assertCount(1, $pendingNotifications);
        $this->assertEquals($pending->id, $pendingNotifications->first()->id);
    }

    public function test_for_user_scope_filters_by_user(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        ScheduledNotification::factory()
            ->forUser($user1->id)
            ->count(3)
            ->create();

        ScheduledNotification::factory()
            ->forUser($user2->id)
            ->count(2)
            ->create();

        $user1Notifications = ScheduledNotification::forUser($user1->id)->get();
        $user2Notifications = ScheduledNotification::forUser($user2->id)->get();

        $this->assertCount(3, $user1Notifications);
        $this->assertCount(2, $user2Notifications);
    }

    public function test_status_computed_correctly(): void
    {
        $user = $this->createUser();

        // Pending (default)
        $pending = ScheduledNotification::factory()->forUser($user->id)->create();
        $this->assertEquals(ScheduledNotification::STATUS_PENDING, $pending->status);

        // Sent
        $sent = ScheduledNotification::factory()->forUser($user->id)->sent()->create();
        $this->assertEquals(ScheduledNotification::STATUS_SENT, $sent->status);

        // Cancelled
        $cancelled = ScheduledNotification::factory()->forUser($user->id)->cancelled()->create();
        $this->assertEquals(ScheduledNotification::STATUS_CANCELLED, $cancelled->status);

        // Failed
        $failed = ScheduledNotification::factory()->forUser($user->id)->failed()->create();
        $this->assertEquals(ScheduledNotification::STATUS_FAILED, $failed->status);
    }

    public function test_mark_as_sent_updates_sent_at(): void
    {
        $user = $this->createUser();

        $scheduled = ScheduledNotification::factory()
            ->forUser($user->id)
            ->create();

        $this->assertNull($scheduled->sent_at);

        $scheduled->markAsSent();

        $this->assertNotNull($scheduled->fresh()->sent_at);
        $this->assertEquals(ScheduledNotification::STATUS_SENT, $scheduled->fresh()->status);
    }

    public function test_mark_as_failed_updates_failed_at_and_error(): void
    {
        $user = $this->createUser();

        $scheduled = ScheduledNotification::factory()
            ->forUser($user->id)
            ->create();

        $this->assertNull($scheduled->failed_at);

        $scheduled->markAsFailed('No devices registered');

        $this->assertNotNull($scheduled->fresh()->failed_at);
        $this->assertEquals('No devices registered', $scheduled->fresh()->error_message);
        $this->assertEquals(ScheduledNotification::STATUS_FAILED, $scheduled->fresh()->status);
    }
}
