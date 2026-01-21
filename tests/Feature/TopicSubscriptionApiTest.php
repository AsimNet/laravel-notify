<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\Events\TopicSubscribed;
use Asimnet\Notify\Events\TopicUnsubscribed;
use Asimnet\Notify\Models\DeviceToken;
use Asimnet\Notify\Models\Topic;
use Asimnet\Notify\Models\TopicSubscription;
use Asimnet\Notify\Testing\FakeFcmService;
use Asimnet\Notify\Tests\TestCase;
use Illuminate\Support\Facades\Event;

class TopicSubscriptionApiTest extends TestCase
{
    protected FakeFcmService $fakeFcm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFcm = new FakeFcmService;
        $this->app->instance(FcmService::class, $this->fakeFcm);

        Event::fake([
            TopicSubscribed::class,
            TopicUnsubscribed::class,
        ]);
    }

    /** @test */
    public function guest_cannot_access_topic_endpoints(): void
    {
        $response = $this->getJson('/api/notify/topics');

        $response->assertUnauthorized();
    }

    /** @test */
    public function user_can_list_public_topics(): void
    {
        $user = $this->createUserWithDevice();

        Topic::factory()->public()->count(3)->create();
        Topic::factory()->private()->count(2)->create();

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/notify/topics');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'description', 'is_public', 'subscriber_count', 'is_subscribed'],
                ],
            ]);
    }

    /** @test */
    public function topic_list_includes_subscription_status(): void
    {
        $user = $this->createUserWithDevice();

        $subscribedTopic = Topic::factory()->public()->create();
        $unsubscribedTopic = Topic::factory()->public()->create();

        TopicSubscription::create([
            'user_id' => $user->id,
            'topic_id' => $subscribedTopic->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/notify/topics');

        $response->assertOk();

        $data = $response->json('data');
        $subscribedData = collect($data)->firstWhere('id', $subscribedTopic->id);
        $unsubscribedData = collect($data)->firstWhere('id', $unsubscribedTopic->id);

        $this->assertTrue($subscribedData['is_subscribed']);
        $this->assertFalse($unsubscribedData['is_subscribed']);
    }

    /** @test */
    public function user_can_subscribe_to_public_topic(): void
    {
        $user = $this->createUserWithDevice();
        $topic = Topic::factory()->public()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/notify/topics/{$topic->id}/subscribe");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas(config('notify.tables.topic_subscriptions'), [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
        ]);

        Event::assertDispatched(TopicSubscribed::class);
    }

    /** @test */
    public function subscription_increments_subscriber_count(): void
    {
        $user = $this->createUserWithDevice();
        $topic = Topic::factory()->public()->create(['subscriber_count' => 5]);

        $this->actingAs($user, 'api')
            ->postJson("/api/notify/topics/{$topic->id}/subscribe");

        $this->assertEquals(6, $topic->fresh()->subscriber_count);
    }

    /** @test */
    public function user_can_subscribe_by_topic_slug(): void
    {
        $user = $this->createUserWithDevice();
        $topic = Topic::factory()->public()->create(['slug' => 'announcements']);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/notify/topics/announcements/subscribe');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas(config('notify.tables.topic_subscriptions'), [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
        ]);
    }

    /** @test */
    public function user_cannot_subscribe_to_private_topic(): void
    {
        $user = $this->createUserWithDevice();
        $topic = Topic::factory()->private()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/notify/topics/{$topic->id}/subscribe");

        $response->assertForbidden()
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function user_cannot_subscribe_without_device(): void
    {
        $user = $this->createUser();
        $topic = Topic::factory()->public()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/notify/topics/{$topic->id}/subscribe");

        $response->assertUnprocessable()
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function duplicate_subscription_is_idempotent(): void
    {
        $user = $this->createUserWithDevice();
        $topic = Topic::factory()->public()->create(['subscriber_count' => 0]);

        // First subscription
        $this->actingAs($user, 'api')
            ->postJson("/api/notify/topics/{$topic->id}/subscribe");

        // Second subscription (same user, same topic)
        $response = $this->actingAs($user, 'api')
            ->postJson("/api/notify/topics/{$topic->id}/subscribe");

        $response->assertOk();

        // Should only have one subscription record
        $this->assertEquals(1, TopicSubscription::where('user_id', $user->id)->count());

        // Subscriber count should be 1, not 2
        $this->assertEquals(1, $topic->fresh()->subscriber_count);
    }

    /** @test */
    public function user_can_unsubscribe_from_topic(): void
    {
        $user = $this->createUserWithDevice();
        $topic = Topic::factory()->public()->create(['subscriber_count' => 5]);

        TopicSubscription::create([
            'user_id' => $user->id,
            'topic_id' => $topic->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/notify/topics/{$topic->id}/unsubscribe");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing(config('notify.tables.topic_subscriptions'), [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
        ]);

        $this->assertEquals(4, $topic->fresh()->subscriber_count);

        Event::assertDispatched(TopicUnsubscribed::class);
    }

    /** @test */
    public function user_cannot_unsubscribe_if_not_subscribed(): void
    {
        $user = $this->createUserWithDevice();
        $topic = Topic::factory()->public()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/notify/topics/{$topic->id}/unsubscribe");

        $response->assertUnprocessable()
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function subscribing_nonexistent_topic_returns_404(): void
    {
        $user = $this->createUserWithDevice();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/notify/topics/99999/subscribe');

        $response->assertNotFound();
    }

    /**
     * Create a test user with a device token.
     */
    protected function createUserWithDevice()
    {
        $user = $this->createUser();

        DeviceToken::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
