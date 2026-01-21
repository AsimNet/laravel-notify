<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Jobs\SendScheduledNotification;
use Asimnet\Notify\Models\ScheduledNotification;
use Asimnet\Notify\Tests\TestCase;
use Illuminate\Support\Facades\Queue;

class ProcessScheduledNotificationsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_command_dispatches_jobs_for_due_notifications(): void
    {
        $user = $this->createUser();

        // Create 3 due notifications
        ScheduledNotification::factory()
            ->forUser($user->id)
            ->due()
            ->count(3)
            ->create();

        $this->artisan('notify:process-scheduled');

        Queue::assertPushed(SendScheduledNotification::class, 3);
    }

    public function test_command_does_not_dispatch_jobs_for_future_notifications(): void
    {
        $user = $this->createUser();

        // Create notification scheduled for future
        ScheduledNotification::factory()
            ->forUser($user->id)
            ->scheduledFor(now()->addHours(2))
            ->create();

        $this->artisan('notify:process-scheduled');

        Queue::assertNotPushed(SendScheduledNotification::class);
    }

    public function test_command_does_not_dispatch_jobs_for_sent_notifications(): void
    {
        $user = $this->createUser();

        // Create already sent notification
        ScheduledNotification::factory()
            ->forUser($user->id)
            ->sent()
            ->create();

        $this->artisan('notify:process-scheduled');

        Queue::assertNotPushed(SendScheduledNotification::class);
    }

    public function test_command_does_not_dispatch_jobs_for_cancelled_notifications(): void
    {
        $user = $this->createUser();

        // Create cancelled notification
        ScheduledNotification::factory()
            ->forUser($user->id)
            ->due()
            ->cancelled()
            ->create();

        $this->artisan('notify:process-scheduled');

        Queue::assertNotPushed(SendScheduledNotification::class);
    }

    public function test_command_respects_limit_option(): void
    {
        $user = $this->createUser();

        // Create 10 due notifications
        ScheduledNotification::factory()
            ->forUser($user->id)
            ->due()
            ->count(10)
            ->create();

        $this->artisan('notify:process-scheduled', ['--limit' => 3]);

        Queue::assertPushed(SendScheduledNotification::class, 3);
    }

    public function test_command_respects_tolerance_option(): void
    {
        $user = $this->createUser();

        // Create notification scheduled 48 hours ago (outside 24h default tolerance)
        ScheduledNotification::factory()
            ->forUser($user->id)
            ->scheduledFor(now()->subHours(48))
            ->create();

        // With default 24h tolerance, this should not be dispatched
        $this->artisan('notify:process-scheduled');

        Queue::assertNotPushed(SendScheduledNotification::class);

        // Now create a recent one (12 hours ago)
        ScheduledNotification::factory()
            ->forUser($user->id)
            ->scheduledFor(now()->subHours(12))
            ->create();

        // This one should be dispatched
        $this->artisan('notify:process-scheduled');

        Queue::assertPushed(SendScheduledNotification::class, 1);
    }

    public function test_command_outputs_count_of_dispatched_notifications(): void
    {
        $user = $this->createUser();

        ScheduledNotification::factory()
            ->forUser($user->id)
            ->due()
            ->count(2)
            ->create();

        $this->artisan('notify:process-scheduled')
            ->expectsOutputToContain('Dispatched 2 scheduled notifications')
            ->assertExitCode(0);
    }

    public function test_command_outputs_message_when_no_due_notifications(): void
    {
        // Don't create any notifications

        $this->artisan('notify:process-scheduled')
            ->assertExitCode(0);
    }

    public function test_command_dispatches_jobs_with_tenant_id(): void
    {
        $user = $this->createUser();

        // Create a due notification with tenant_id
        ScheduledNotification::factory()
            ->forUser($user->id)
            ->due()
            ->create(['tenant_id' => 'test-tenant']);

        $this->artisan('notify:process-scheduled');

        Queue::assertPushed(SendScheduledNotification::class, function ($job) {
            return $job->tenantId === 'test-tenant';
        });
    }

    public function test_command_orders_by_scheduled_at(): void
    {
        $user = $this->createUser();

        // Create notifications with different scheduled times
        $newer = ScheduledNotification::factory()
            ->forUser($user->id)
            ->scheduledFor(now()->subMinutes(5))
            ->create();

        $older = ScheduledNotification::factory()
            ->forUser($user->id)
            ->scheduledFor(now()->subHours(2))
            ->create();

        $this->artisan('notify:process-scheduled', ['--limit' => 1]);

        // Only 1 should be dispatched due to limit
        Queue::assertPushed(SendScheduledNotification::class, 1);

        // The dispatched job should be for the older notification
        Queue::assertPushed(SendScheduledNotification::class, function ($job) use ($older) {
            return $job->scheduledNotification->id === $older->id;
        });
    }

    public function test_command_does_not_dispatch_failed_notifications(): void
    {
        $user = $this->createUser();

        // Create a failed notification
        ScheduledNotification::factory()
            ->forUser($user->id)
            ->due()
            ->failed()
            ->create();

        $this->artisan('notify:process-scheduled');

        Queue::assertNotPushed(SendScheduledNotification::class);
    }

    public function test_command_handles_mixed_notification_states(): void
    {
        $user = $this->createUser();

        // Due - should be dispatched
        ScheduledNotification::factory()
            ->forUser($user->id)
            ->due()
            ->count(2)
            ->create();

        // Future - should NOT be dispatched
        ScheduledNotification::factory()
            ->forUser($user->id)
            ->scheduledFor(now()->addHours(1))
            ->create();

        // Sent - should NOT be dispatched
        ScheduledNotification::factory()
            ->forUser($user->id)
            ->sent()
            ->create();

        // Cancelled - should NOT be dispatched
        ScheduledNotification::factory()
            ->forUser($user->id)
            ->due()
            ->cancelled()
            ->create();

        $this->artisan('notify:process-scheduled');

        // Only the 2 due ones should be dispatched
        Queue::assertPushed(SendScheduledNotification::class, 2);
    }
}
