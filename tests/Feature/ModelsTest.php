<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Models\DeviceToken;
use Asimnet\Notify\Models\Topic;
use Asimnet\Notify\Models\TopicSubscription;
use Asimnet\Notify\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_token_can_be_created(): void
    {
        $token = DeviceToken::create([
            'user_id' => 1,
            'token' => 'test_fcm_token_'.str_repeat('x', 100),
            'platform' => 'ios',
            'device_name' => 'iPhone-Test',
            'last_active_at' => now(),
        ]);

        $this->assertDatabaseHas(config('notify.tables.device_tokens'), [
            'id' => $token->id,
            'platform' => 'ios',
        ]);
    }

    public function test_device_token_factory_works(): void
    {
        $token = DeviceToken::factory()->ios()->create(['user_id' => 1]);

        $this->assertEquals('ios', $token->platform);
        $this->assertStringStartsWith('iPhone-', $token->device_name);
    }

    public function test_device_token_stale_scope(): void
    {
        DeviceToken::factory()->create([
            'user_id' => 1,
            'last_active_at' => now()->subDays(60),
        ]);
        DeviceToken::factory()->create([
            'user_id' => 1,
            'last_active_at' => now(),
        ]);

        $staleTokens = DeviceToken::stale(30)->get();

        $this->assertCount(1, $staleTokens);
    }

    public function test_topic_can_be_created(): void
    {
        $topic = Topic::create([
            'name' => 'Test Topic',
            'description' => 'A test topic',
            'is_public' => true,
        ]);

        $this->assertDatabaseHas(config('notify.tables.topics'), [
            'id' => $topic->id,
            'slug' => 'test-topic',
        ]);
    }

    public function test_topic_auto_generates_slug(): void
    {
        $topic = Topic::create([
            'name' => 'My Test Topic',
            'is_public' => true,
        ]);

        $this->assertEquals('my-test-topic', $topic->slug);
    }

    public function test_topic_factory_works(): void
    {
        $topic = Topic::factory()->public()->create();

        $this->assertTrue($topic->is_public);
    }

    public function test_topic_factory_default_state(): void
    {
        $topic = Topic::factory()->default()->create();

        $this->assertTrue($topic->is_default);
        $this->assertEquals('public', $topic->slug);
    }

    public function test_topic_subscription_can_be_created(): void
    {
        $topic = Topic::factory()->create();

        $subscription = TopicSubscription::create([
            'topic_id' => $topic->id,
            'user_id' => 1,
            'fcm_synced' => false,
        ]);

        $this->assertDatabaseHas(config('notify.tables.topic_subscriptions'), [
            'id' => $subscription->id,
            'topic_id' => $topic->id,
        ]);
    }

    public function test_topic_subscription_factory_works(): void
    {
        $subscription = TopicSubscription::factory()->synced()->create(['user_id' => 1]);

        $this->assertTrue($subscription->fcm_synced);
    }

    public function test_topic_is_subscribed_by_user(): void
    {
        $topic = Topic::factory()->create();
        TopicSubscription::create([
            'topic_id' => $topic->id,
            'user_id' => 1,
            'fcm_synced' => true,
        ]);

        $this->assertTrue($topic->isSubscribedByUser(1));
        $this->assertFalse($topic->isSubscribedByUser(999));
    }

    public function test_topic_subscription_mark_synced(): void
    {
        $subscription = TopicSubscription::factory()->unsynced()->create(['user_id' => 1]);

        $this->assertFalse($subscription->fcm_synced);

        $subscription->markSynced();

        $this->assertTrue($subscription->fresh()->fcm_synced);
    }

    public function test_topic_fcm_topic_name_without_tenant(): void
    {
        $topic = Topic::factory()->create(['slug' => 'announcements']);

        $this->assertEquals('announcements', $topic->getFcmTopicName());
    }

    public function test_topic_fcm_topic_name_with_tenant(): void
    {
        $topic = Topic::factory()->create([
            'slug' => 'announcements',
            'tenant_id' => 'tenant123',
        ]);

        $this->assertEquals('tenant123_announcements', $topic->getFcmTopicName());
    }
}
