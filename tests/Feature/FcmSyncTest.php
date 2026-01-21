<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\Events\DeviceTokenDeleted;
use Asimnet\Notify\Events\DeviceTokenRegistered;
use Asimnet\Notify\Events\TopicSubscribed;
use Asimnet\Notify\Events\TopicUnsubscribed;
use Asimnet\Notify\Listeners\CleanupDeletedDeviceFromFcm;
use Asimnet\Notify\Listeners\SyncDeviceToDefaultTopics;
use Asimnet\Notify\Listeners\SyncTopicSubscriptionToFcm;
use Asimnet\Notify\Models\DeviceToken;
use Asimnet\Notify\Models\Topic;
use Asimnet\Notify\Models\TopicSubscription;
use Asimnet\Notify\Testing\FakeFcmService;
use Asimnet\Notify\Tests\TestCase;

class FcmSyncTest extends TestCase
{
    protected FakeFcmService $fakeFcm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFcm = new FakeFcmService;
        $this->app->instance(FcmService::class, $this->fakeFcm);
    }

    /** @test */
    public function device_registration_subscribes_to_default_topics(): void
    {
        $defaultTopic = Topic::factory()->default()->create();
        $regularTopic = Topic::factory()->public()->create();

        $device = DeviceToken::factory()->create(['user_id' => 1]);

        $event = new DeviceTokenRegistered($device, true);
        $listener = new SyncDeviceToDefaultTopics($this->fakeFcm);
        $listener->handle($event);

        // Should subscribe to default topic
        $this->fakeFcm->assertSubscribed($defaultTopic->getFcmTopicName(), $device->token);

        // Should create subscription record
        $this->assertDatabaseHas(config('notify.tables.topic_subscriptions'), [
            'user_id' => $device->user_id,
            'topic_id' => $defaultTopic->id,
        ]);
    }

    /** @test */
    public function device_registration_skips_when_no_default_topics(): void
    {
        Topic::factory()->public()->count(3)->create(); // No default topics

        $device = DeviceToken::factory()->create(['user_id' => 1]);

        $event = new DeviceTokenRegistered($device, true);
        $listener = new SyncDeviceToDefaultTopics($this->fakeFcm);
        $listener->handle($event);

        $this->fakeFcm->assertNothingSubscribed();
    }

    /** @test */
    public function topic_subscription_syncs_with_fcm(): void
    {
        $topic = Topic::factory()->public()->create();
        $subscription = TopicSubscription::factory()->create([
            'user_id' => 1,
            'topic_id' => $topic->id,
            'fcm_synced' => false,
        ]);
        $deviceTokens = ['token1', 'token2'];

        $event = new TopicSubscribed($subscription, $deviceTokens);
        $listener = new SyncTopicSubscriptionToFcm($this->fakeFcm);
        $listener->handleSubscription($event);

        // Both tokens should be subscribed
        $this->fakeFcm->assertSubscribed($topic->getFcmTopicName(), 'token1');
        $this->fakeFcm->assertSubscribed($topic->getFcmTopicName(), 'token2');

        // Subscription should be marked synced
        $this->assertTrue($subscription->fresh()->fcm_synced);
    }

    /** @test */
    public function topic_subscription_marks_synced_on_success(): void
    {
        $topic = Topic::factory()->public()->create();
        $subscription = TopicSubscription::factory()->unsynced()->create([
            'user_id' => 1,
            'topic_id' => $topic->id,
        ]);

        $event = new TopicSubscribed($subscription, ['token1']);
        $listener = new SyncTopicSubscriptionToFcm($this->fakeFcm);
        $listener->handleSubscription($event);

        $this->assertTrue($subscription->fresh()->fcm_synced);
    }

    /** @test */
    public function topic_unsubscription_syncs_with_fcm(): void
    {
        $topic = Topic::factory()->public()->create();
        $deviceTokens = ['token1', 'token2'];

        $event = new TopicUnsubscribed($topic, $deviceTokens);
        $listener = new SyncTopicSubscriptionToFcm($this->fakeFcm);
        $listener->handleUnsubscription($event);

        $this->fakeFcm->assertUnsubscribed($topic->getFcmTopicName(), 'token1');
        $this->fakeFcm->assertUnsubscribed($topic->getFcmTopicName(), 'token2');
    }

    /** @test */
    public function invalid_tokens_are_removed_on_unregistered_error(): void
    {
        $topic = Topic::factory()->public()->create();

        $validDevice = DeviceToken::factory()->create([
            'user_id' => 1,
            'token' => 'valid_token_123',
        ]);
        $invalidDevice = DeviceToken::factory()->create([
            'user_id' => 1,
            'token' => 'invalid_token_456',
        ]);

        // Mark one token as invalid in fake FCM
        $this->fakeFcm->markTokensInvalid(['invalid_token_456']);

        $subscription = TopicSubscription::factory()->create([
            'user_id' => 1,
            'topic_id' => $topic->id,
        ]);

        $event = new TopicSubscribed($subscription, ['valid_token_123', 'invalid_token_456']);
        $listener = new SyncTopicSubscriptionToFcm($this->fakeFcm);
        $listener->handleSubscription($event);

        // Invalid token should be deleted
        $this->assertDatabaseMissing(config('notify.tables.device_tokens'), [
            'token' => 'invalid_token_456',
        ]);

        // Valid token should still exist
        $this->assertDatabaseHas(config('notify.tables.device_tokens'), [
            'token' => 'valid_token_123',
        ]);
    }

    /** @test */
    public function device_deletion_unsubscribes_from_all_topics(): void
    {
        $token = 'deleted_device_token';

        $event = new DeviceTokenDeleted(1, $token, null);
        $listener = new CleanupDeletedDeviceFromFcm($this->fakeFcm);
        $listener->handle($event);

        $this->fakeFcm->assertUnsubscribedFromAll($token);
    }

    /** @test */
    public function fcm_failure_does_not_throw_exception(): void
    {
        $this->fakeFcm->shouldFailWith('QUOTA_EXCEEDED');

        $topic = Topic::factory()->public()->create();
        $subscription = TopicSubscription::factory()->create([
            'user_id' => 1,
            'topic_id' => $topic->id,
        ]);

        $event = new TopicSubscribed($subscription, ['token1']);
        $listener = new SyncTopicSubscriptionToFcm($this->fakeFcm);

        // Should not throw
        $listener->handleSubscription($event);

        // Subscription should NOT be marked as synced
        $this->assertFalse($subscription->fresh()->fcm_synced);
    }

    /** @test */
    public function empty_device_tokens_handled_gracefully(): void
    {
        $topic = Topic::factory()->public()->create();
        $subscription = TopicSubscription::factory()->create([
            'user_id' => 1,
            'topic_id' => $topic->id,
        ]);

        $event = new TopicSubscribed($subscription, []); // Empty tokens
        $listener = new SyncTopicSubscriptionToFcm($this->fakeFcm);

        // Should not throw
        $listener->handleSubscription($event);

        $this->fakeFcm->assertNothingSubscribed();
    }

    /** @test */
    public function tenant_prefix_added_to_fcm_topic_name(): void
    {
        $topic = Topic::factory()->create([
            'slug' => 'announcements',
            'tenant_id' => 'tenant123',
        ]);

        $this->assertEquals('tenant123_announcements', $topic->getFcmTopicName());
    }

    /** @test */
    public function no_tenant_prefix_when_tenant_null(): void
    {
        $topic = Topic::factory()->create([
            'slug' => 'announcements',
            'tenant_id' => null,
        ]);

        $this->assertEquals('announcements', $topic->getFcmTopicName());
    }
}
