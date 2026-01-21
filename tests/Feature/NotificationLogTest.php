<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Models\NotificationLog;
use Asimnet\Notify\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

class NotificationLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_can_be_created_with_factory(): void
    {
        $log = NotificationLog::factory()->create();

        $this->assertDatabaseHas('notify_logs', [
            'id' => $log->id,
            'channel' => 'fcm',
        ]);
    }

    public function test_log_status_constants_are_defined(): void
    {
        $this->assertEquals('pending', NotificationLog::STATUS_PENDING);
        $this->assertEquals('sent', NotificationLog::STATUS_SENT);
        $this->assertEquals('delivered', NotificationLog::STATUS_DELIVERED);
        $this->assertEquals('opened', NotificationLog::STATUS_OPENED);
        $this->assertEquals('failed', NotificationLog::STATUS_FAILED);
    }

    public function test_mark_as_sent_updates_status(): void
    {
        $log = NotificationLog::factory()->pending()->create();

        $log->markAsSent('message-id-123');

        $this->assertEquals(NotificationLog::STATUS_SENT, $log->fresh()->status);
        $this->assertEquals('message-id-123', $log->fresh()->external_id);
        $this->assertNotNull($log->fresh()->sent_at);
    }

    public function test_mark_as_delivered_updates_status(): void
    {
        $log = NotificationLog::factory()->sent()->create();

        $log->markAsDelivered();

        $this->assertEquals(NotificationLog::STATUS_DELIVERED, $log->fresh()->status);
        $this->assertNotNull($log->fresh()->delivered_at);
    }

    public function test_mark_as_opened_updates_status(): void
    {
        $log = NotificationLog::factory()->delivered()->create();

        $log->markAsOpened();

        $this->assertEquals(NotificationLog::STATUS_OPENED, $log->fresh()->status);
        $this->assertNotNull($log->fresh()->opened_at);
    }

    public function test_mark_as_failed_updates_status_with_error(): void
    {
        $log = NotificationLog::factory()->pending()->create();

        $log->markAsFailed('Token not registered', 'UNREGISTERED');

        $this->assertEquals(NotificationLog::STATUS_FAILED, $log->fresh()->status);
        $this->assertEquals('Token not registered', $log->fresh()->error_message);
        $this->assertEquals('UNREGISTERED', $log->fresh()->error_code);
    }

    public function test_was_successful_returns_true_for_sent_status(): void
    {
        $log = NotificationLog::factory()->sent()->create();

        $this->assertTrue($log->wasSuccessful());
    }

    public function test_was_successful_returns_false_for_failed_status(): void
    {
        $log = NotificationLog::factory()->failed()->create();

        $this->assertFalse($log->wasSuccessful());
    }

    public function test_old_logs_are_pruned(): void
    {
        // Create old logs (beyond retention period)
        NotificationLog::factory()->count(5)->old(200)->create();

        // Create recent logs (within retention period)
        NotificationLog::factory()->count(3)->create();

        $this->assertDatabaseCount('notify_logs', 8);

        // Run prune command
        Artisan::call('model:prune', [
            '--model' => [NotificationLog::class],
        ]);

        // Only recent logs should remain
        $this->assertDatabaseCount('notify_logs', 3);
    }

    public function test_pruning_respects_config_retention_days(): void
    {
        config(['notify.logging.retention_days' => 30]);

        NotificationLog::factory()->old(40)->create();
        NotificationLog::factory()->old(20)->create();

        Artisan::call('model:prune', [
            '--model' => [NotificationLog::class],
        ]);

        // Only the 20-day-old log should remain
        $this->assertDatabaseCount('notify_logs', 1);
    }

    public function test_channel_scope_filters_by_channel(): void
    {
        NotificationLog::factory()->channel('fcm')->create();
        NotificationLog::factory()->channel('sms')->create();

        $fcmLogs = NotificationLog::channel('fcm')->get();

        $this->assertCount(1, $fcmLogs);
        $this->assertEquals('fcm', $fcmLogs->first()->channel);
    }

    public function test_status_scope_filters_by_status(): void
    {
        NotificationLog::factory()->sent()->create();
        NotificationLog::factory()->failed()->create();

        $sentLogs = NotificationLog::status(NotificationLog::STATUS_SENT)->get();

        $this->assertCount(1, $sentLogs);
    }

    public function test_recent_scope_filters_old_logs(): void
    {
        NotificationLog::factory()->old(10)->create();
        NotificationLog::factory()->create(); // Recent

        $recentLogs = NotificationLog::recent(7)->get();

        $this->assertCount(1, $recentLogs);
    }

    public function test_not_test_scope_excludes_test_logs(): void
    {
        NotificationLog::factory()->test()->create();
        NotificationLog::factory()->create();

        $nonTestLogs = NotificationLog::notTest()->get();

        $this->assertCount(1, $nonTestLogs);
        $this->assertFalse($nonTestLogs->first()->is_test);
    }

    public function test_only_test_scope_includes_only_test_logs(): void
    {
        NotificationLog::factory()->test()->create();
        NotificationLog::factory()->create();

        $testLogs = NotificationLog::onlyTest()->get();

        $this->assertCount(1, $testLogs);
        $this->assertTrue($testLogs->first()->is_test);
    }

    public function test_failed_scope_gets_failed_logs(): void
    {
        NotificationLog::factory()->sent()->create();
        NotificationLog::factory()->failed()->create();

        $failedLogs = NotificationLog::failed()->get();

        $this->assertCount(1, $failedLogs);
        $this->assertEquals(NotificationLog::STATUS_FAILED, $failedLogs->first()->status);
    }

    public function test_successful_scope_gets_sent_delivered_opened(): void
    {
        NotificationLog::factory()->sent()->create();
        NotificationLog::factory()->delivered()->create();
        NotificationLog::factory()->opened()->create();
        NotificationLog::factory()->failed()->create();

        $successfulLogs = NotificationLog::successful()->get();

        $this->assertCount(3, $successfulLogs);
    }
}
