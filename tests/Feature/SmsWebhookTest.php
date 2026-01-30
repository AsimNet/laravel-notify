<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Models\NotificationLog;
use Asimnet\Notify\Tests\TestCase;

class SmsWebhookTest extends TestCase
{
    public function test_updates_log_status_to_delivered(): void
    {
        $log = NotificationLog::factory()->create([
            'channel' => 'sms',
            'status' => NotificationLog::STATUS_SENT,
            'external_id' => 'abc-123',
        ]);

        $response = $this->postJson('/api/notify/webhooks/sms', [
            'external_id' => 'abc-123',
            'status' => NotificationLog::STATUS_DELIVERED,
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $log->refresh();
        $this->assertSame(NotificationLog::STATUS_DELIVERED, $log->status);
        $this->assertNotNull($log->delivered_at);
    }
}
