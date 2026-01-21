<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\Events\DeviceTokenDeleted;
use Asimnet\Notify\Events\DeviceTokenRegistered;
use Asimnet\Notify\Models\DeviceToken;
use Asimnet\Notify\Testing\FakeFcmService;
use Asimnet\Notify\Tests\TestCase;
use Illuminate\Support\Facades\Event;

class DeviceTokenApiTest extends TestCase
{
    protected FakeFcmService $fakeFcm;

    protected function setUp(): void
    {
        parent::setUp();

        // Use fake FCM service
        $this->fakeFcm = new FakeFcmService;
        $this->app->instance(FcmService::class, $this->fakeFcm);

        // Fake events by default
        Event::fake([
            DeviceTokenRegistered::class,
            DeviceTokenDeleted::class,
        ]);
    }

    /** @test */
    public function guest_cannot_access_device_endpoints(): void
    {
        $response = $this->getJson('/api/notify/devices');

        $response->assertUnauthorized();
    }

    /** @test */
    public function user_can_list_their_devices(): void
    {
        $user = $this->createUser();

        DeviceToken::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/notify/devices');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'device_name', 'platform', 'platform_label', 'last_active_at', 'created_at'],
                ],
            ]);

        // Verify token is NOT exposed
        $response->assertJsonMissing(['token']);
    }

    /** @test */
    public function user_only_sees_their_own_devices(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        DeviceToken::factory()->count(2)->create(['user_id' => $user1->id]);
        DeviceToken::factory()->count(3)->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1, 'api')
            ->getJson('/api/notify/devices');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_register_new_device(): void
    {
        $user = $this->createUser();
        $token = 'fcm_'.str_repeat('x', 150);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/notify/devices', [
                'token' => $token,
                'platform' => 'ios',
                'device_name' => 'iPhone-Test',
            ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'platform' => 'ios',
                    'device_name' => 'iPhone-Test',
                ],
            ]);

        $this->assertDatabaseHas(config('notify.tables.device_tokens'), [
            'user_id' => $user->id,
            'platform' => 'ios',
            'device_name' => 'iPhone-Test',
        ]);

        Event::assertDispatched(DeviceTokenRegistered::class);
    }

    /** @test */
    public function first_device_registration_marks_is_first_device(): void
    {
        $user = $this->createUser();
        $token = 'fcm_'.str_repeat('x', 150);

        Event::fake([DeviceTokenRegistered::class]);

        $this->actingAs($user, 'api')
            ->postJson('/api/notify/devices', [
                'token' => $token,
                'platform' => 'ios',
            ]);

        Event::assertDispatched(function (DeviceTokenRegistered $event) {
            return $event->isFirstDevice === true;
        });
    }

    /** @test */
    public function second_device_registration_marks_is_first_device_false(): void
    {
        $user = $this->createUser();

        // First device
        DeviceToken::factory()->create(['user_id' => $user->id]);

        Event::fake([DeviceTokenRegistered::class]);

        // Second device
        $this->actingAs($user, 'api')
            ->postJson('/api/notify/devices', [
                'token' => 'fcm_'.str_repeat('y', 150),
                'platform' => 'android',
            ]);

        Event::assertDispatched(function (DeviceTokenRegistered $event) {
            return $event->isFirstDevice === false;
        });
    }

    /** @test */
    public function registration_validates_token_required(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/notify/devices', [
                'platform' => 'ios',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    }

    /** @test */
    public function registration_validates_token_min_length(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/notify/devices', [
                'token' => 'short',
                'platform' => 'ios',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    }

    /** @test */
    public function registration_validates_platform_required(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/notify/devices', [
                'token' => 'fcm_'.str_repeat('x', 150),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['platform']);
    }

    /** @test */
    public function registration_validates_platform_enum(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/notify/devices', [
                'token' => 'fcm_'.str_repeat('x', 150),
                'platform' => 'invalid',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['platform']);
    }

    /** @test */
    public function registration_prevents_duplicate_tokens(): void
    {
        $user = $this->createUser();
        $token = 'fcm_'.str_repeat('x', 150);

        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => $token,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/notify/devices', [
                'token' => $token,
                'platform' => 'ios',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    }

    /** @test */
    public function user_can_update_their_device(): void
    {
        $user = $this->createUser();
        $device = DeviceToken::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')
            ->putJson("/api/notify/devices/{$device->id}", [
                'device_name' => 'Updated Name',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'device_name' => 'Updated Name',
                ],
            ]);

        $this->assertDatabaseHas(config('notify.tables.device_tokens'), [
            'id' => $device->id,
            'device_name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function user_cannot_update_another_users_device(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $device = DeviceToken::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1, 'api')
            ->putJson("/api/notify/devices/{$device->id}", [
                'device_name' => 'Hacked',
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_delete_their_device(): void
    {
        $user = $this->createUser();
        $device = DeviceToken::factory()->create(['user_id' => $user->id]);
        $deviceId = $device->id;

        $response = $this->actingAs($user, 'api')
            ->deleteJson("/api/notify/devices/{$deviceId}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing(config('notify.tables.device_tokens'), [
            'id' => $deviceId,
        ]);

        Event::assertDispatched(DeviceTokenDeleted::class);
    }

    /** @test */
    public function user_cannot_delete_another_users_device(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $device = DeviceToken::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1, 'api')
            ->deleteJson("/api/notify/devices/{$device->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function deleting_nonexistent_device_returns_404(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'api')
            ->deleteJson('/api/notify/devices/99999');

        $response->assertNotFound();
    }
}
