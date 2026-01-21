<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\DTOs\NotificationMessage;
use Asimnet\Notify\Facades\Notify;
use Asimnet\Notify\Models\DeviceToken;
use Asimnet\Notify\Models\NotificationTemplate;
use Asimnet\Notify\Models\Segment;
use Asimnet\Notify\Testing\FakeFcmService;
use Asimnet\Notify\Tests\TestCase;
use Asimnet\Notify\Tests\TestUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SegmentNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected FakeFcmService $fcmService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fcmService = new FakeFcmService;
        $this->app->instance(FcmService::class, $this->fcmService);
    }

    public function test_send_to_segment_by_slug(): void
    {
        // Create users
        $user1 = $this->createUserWithAttributes(['gender' => 'male']);
        $user2 = $this->createUserWithAttributes(['gender' => 'male']);
        $user3 = $this->createUserWithAttributes(['gender' => 'female']);

        // Register devices using factory
        DeviceToken::factory()->create(['user_id' => $user1->id, 'token' => 'token1']);
        DeviceToken::factory()->create(['user_id' => $user2->id, 'token' => 'token2']);
        DeviceToken::factory()->create(['user_id' => $user3->id, 'token' => 'token3']);

        // Create segment for males
        $segment = Segment::factory()->genderMale()->slug('male-users')->create();

        $message = NotificationMessage::create('Test Title', 'Test Body');

        $result = Notify::sendToSegment('male-users', $message);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['user_count']);
        $this->assertGreaterThan(0, $result['success_count']);

        // Verify only male users' tokens were sent via multicast
        $sentTokens = $this->getSentTokens();
        $this->assertContains('token1', $sentTokens);
        $this->assertContains('token2', $sentTokens);
        $this->assertNotContains('token3', $sentTokens);
    }

    public function test_send_to_segment_by_id(): void
    {
        $user = $this->createUserWithAttributes(['city' => 'Riyadh']);
        DeviceToken::factory()->create(['user_id' => $user->id, 'token' => 'token-riyadh']);

        $segment = Segment::factory()->cityRiyadh()->create();

        $message = NotificationMessage::create('Test', 'Body');

        $result = Notify::sendToSegment($segment->id, $message);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['user_count']);
    }

    public function test_send_to_segment_by_model(): void
    {
        $user = $this->createUserWithAttributes(['gender' => 'female']);
        DeviceToken::factory()->create(['user_id' => $user->id, 'token' => 'token-female']);

        $segment = Segment::factory()->create([
            'conditions' => [
                'operator' => 'and',
                'conditions' => [
                    ['field' => 'gender', 'filterType' => 'text', 'type' => 'equals', 'filter' => 'female'],
                ],
            ],
        ]);

        $message = NotificationMessage::create('Test', 'Body');

        $result = Notify::sendToSegment($segment, $message);

        $this->assertTrue($result['success']);
    }

    public function test_send_to_segment_returns_error_for_missing_segment(): void
    {
        $message = NotificationMessage::create('Test', 'Body');

        $result = Notify::sendToSegment('non-existent-segment', $message);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['user_count']);
        $this->assertNotNull($result['error']);
    }

    public function test_send_to_segment_returns_error_for_inactive_segment(): void
    {
        $segment = Segment::factory()->inactive()->slug('inactive-seg')->create();

        $message = NotificationMessage::create('Test', 'Body');

        $result = Notify::sendToSegment('inactive-seg', $message);

        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
    }

    public function test_send_to_segment_returns_error_when_no_users_match(): void
    {
        // Create segment that matches no users
        $segment = Segment::factory()->slug('empty-segment')->create([
            'conditions' => [
                'operator' => 'and',
                'conditions' => [
                    ['field' => 'city', 'filterType' => 'text', 'type' => 'equals', 'filter' => 'NonExistentCity'],
                ],
            ],
        ]);

        $message = NotificationMessage::create('Test', 'Body');

        $result = Notify::sendToSegment('empty-segment', $message);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['user_count']);
        $this->assertNotNull($result['error']);
    }

    public function test_send_from_template_to_segment(): void
    {
        $user = $this->createUserWithAttributes(['gender' => 'male']);
        DeviceToken::factory()->create(['user_id' => $user->id, 'token' => 'token-m']);

        NotificationTemplate::factory()->slug('welcome')->create([
            'title' => 'Welcome {greeting}!',
            'body' => 'Hello from the app.',
        ]);

        Segment::factory()->genderMale()->slug('males')->create();

        $result = Notify::sendFromTemplateToSegment('welcome', 'males', ['greeting' => 'Friend']);

        $this->assertTrue($result['success']);

        // Verify message content was rendered
        $lastMessage = $this->fcmService->getLastSentMessage();
        $this->assertEquals('Welcome Friend!', $lastMessage->title);
    }

    public function test_send_to_segment_via_to_method(): void
    {
        $user = $this->createUserWithAttributes(['gender' => 'male']);
        DeviceToken::factory()->create(['user_id' => $user->id, 'token' => 'token-seg']);

        Segment::factory()->genderMale()->slug('segment-test')->create();

        $message = NotificationMessage::create('Test', 'Body');

        $result = Notify::to('segment:segment-test')->send($message);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['user_count']);
    }

    public function test_segment_with_complex_conditions_sends_to_correct_users(): void
    {
        // Create diverse users
        $maleRiyadh = $this->createUserWithAttributes(['gender' => 'male', 'city' => 'Riyadh']);
        $maleJeddah = $this->createUserWithAttributes(['gender' => 'male', 'city' => 'Jeddah']);
        $maleDammam = $this->createUserWithAttributes(['gender' => 'male', 'city' => 'Dammam']);
        $femaleRiyadh = $this->createUserWithAttributes(['gender' => 'female', 'city' => 'Riyadh']);

        DeviceToken::factory()->create(['user_id' => $maleRiyadh->id, 'token' => 't1']);
        DeviceToken::factory()->create(['user_id' => $maleJeddah->id, 'token' => 't2']);
        DeviceToken::factory()->create(['user_id' => $maleDammam->id, 'token' => 't3']);
        DeviceToken::factory()->create(['user_id' => $femaleRiyadh->id, 'token' => 't4']);

        // Male AND (Riyadh OR Jeddah)
        $segment = Segment::factory()->complex()->slug('complex-seg')->create();

        $message = NotificationMessage::create('Test', 'Body');

        $result = Notify::sendToSegment('complex-seg', $message);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['user_count']); // maleRiyadh, maleJeddah

        $sentTokens = $this->getSentTokens();
        $this->assertContains('t1', $sentTokens);
        $this->assertContains('t2', $sentTokens);
        $this->assertNotContains('t3', $sentTokens); // Dammam excluded
        $this->assertNotContains('t4', $sentTokens); // Female excluded
    }

    public function test_send_to_segment_returns_error_when_users_have_no_devices(): void
    {
        // Create user that matches but has no device
        $this->createUserWithAttributes(['gender' => 'male']);

        $segment = Segment::factory()->genderMale()->slug('no-device-seg')->create();

        $message = NotificationMessage::create('Test', 'Body');

        $result = Notify::sendToSegment('no-device-seg', $message);

        $this->assertFalse($result['success']);
        $this->assertEquals(1, $result['user_count']); // User count still reflects matched users
        $this->assertNotNull($result['error']);
    }

    public function test_send_to_segment_by_id_with_inactive_segment(): void
    {
        $segment = Segment::factory()->inactive()->create();

        $message = NotificationMessage::create('Test', 'Body');

        $result = Notify::sendToSegment($segment->id, $message);

        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
    }

    /**
     * Helper to get all tokens sent via multicast.
     *
     * @return array<string>
     */
    protected function getSentTokens(): array
    {
        $tokens = [];
        foreach ($this->fcmService->multicastMessages as $multicast) {
            $tokens = array_merge($tokens, $multicast['tokens']);
        }

        return $tokens;
    }

    /**
     * Helper to create a user with specific attributes.
     */
    protected function createUserWithAttributes(array $attributes): TestUser
    {
        return TestUser::forceCreate(array_merge([
            'name' => 'Test User '.rand(1, 9999),
            'email' => 'test'.rand(1, 99999).'@example.com',
            'password' => bcrypt('password'),
        ], $attributes));
    }
}
