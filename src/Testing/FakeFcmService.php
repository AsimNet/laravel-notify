<?php

namespace Asimnet\Notify\Testing;

use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\DTOs\NotificationMessage;
use PHPUnit\Framework\Assert;

/**
 * Fake FCM service for testing.
 *
 * Records all operations and provides assertion methods
 * to verify FCM interactions in tests.
 */
class FakeFcmService implements FcmService
{
    /**
     * Recorded topic subscriptions.
     *
     * @var array<string, array<string>>
     */
    public array $subscriptions = [];

    /**
     * Recorded topic unsubscriptions.
     *
     * @var array<string, array<string>>
     */
    public array $unsubscriptions = [];

    /**
     * Recorded unsubscribe from all topics calls.
     *
     * @var array<string>
     */
    public array $unsubscribedFromAll = [];

    /**
     * Recorded token validations.
     *
     * @var array<string>
     */
    public array $validatedTokens = [];

    /**
     * Whether operations should fail.
     */
    public bool $shouldFail = false;

    /**
     * Error message to return on failure.
     */
    public string $failureError = 'UNREGISTERED';

    /**
     * Tokens that should be treated as invalid.
     *
     * @var array<string>
     */
    public array $invalidTokens = [];

    /**
     * Recorded single token messages.
     *
     * @var array<array{token: string, message: NotificationMessage}>
     */
    public array $sentMessages = [];

    /**
     * Recorded topic messages.
     *
     * @var array<array{topic: string, message: NotificationMessage}>
     */
    public array $topicMessages = [];

    /**
     * Recorded multicast messages.
     *
     * @var array<array{tokens: array<string>, message: NotificationMessage}>
     */
    public array $multicastMessages = [];

    /**
     * Recorded data-only messages.
     *
     * @var array<array{token: string, data: array<string, string>}>
     */
    public array $dataMessages = [];

    /**
     * {@inheritdoc}
     */
    public function subscribeToTopic(string $topic, array $tokens): array
    {
        if (empty($tokens)) {
            return ['success' => [], 'failures' => []];
        }

        if ($this->shouldFail) {
            return [
                'success' => [],
                'failures' => array_fill_keys($tokens, $this->failureError),
            ];
        }

        // Check for individually invalid tokens
        $success = [];
        $failures = [];

        foreach ($tokens as $token) {
            if (in_array($token, $this->invalidTokens, true)) {
                $failures[$token] = 'UNREGISTERED';
            } else {
                $success[] = $token;
            }
        }

        // Record successful subscriptions
        if (! isset($this->subscriptions[$topic])) {
            $this->subscriptions[$topic] = [];
        }
        $this->subscriptions[$topic] = array_merge($this->subscriptions[$topic], $success);

        return ['success' => $success, 'failures' => $failures];
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribeFromTopic(string $topic, array $tokens): array
    {
        if (empty($tokens)) {
            return ['success' => [], 'failures' => []];
        }

        if ($this->shouldFail) {
            return [
                'success' => [],
                'failures' => array_fill_keys($tokens, $this->failureError),
            ];
        }

        // Record unsubscriptions
        if (! isset($this->unsubscriptions[$topic])) {
            $this->unsubscriptions[$topic] = [];
        }
        $this->unsubscriptions[$topic] = array_merge($this->unsubscriptions[$topic], $tokens);

        return ['success' => $tokens, 'failures' => []];
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribeFromAllTopics(array $tokens): array
    {
        if (empty($tokens)) {
            return ['success' => [], 'failures' => []];
        }

        if ($this->shouldFail) {
            return [
                'success' => [],
                'failures' => array_fill_keys($tokens, $this->failureError),
            ];
        }

        $this->unsubscribedFromAll = array_merge($this->unsubscribedFromAll, $tokens);

        return ['success' => $tokens, 'failures' => []];
    }

    /**
     * {@inheritdoc}
     */
    public function validateToken(string $token): bool
    {
        $this->validatedTokens[] = $token;

        if ($this->shouldFail) {
            return false;
        }

        return ! in_array($token, $this->invalidTokens, true);
    }

    // ========================================
    // Message Sending Methods
    // ========================================

    /**
     * {@inheritdoc}
     */
    public function send(string $token, NotificationMessage $message): array
    {
        $this->sentMessages[] = ['token' => $token, 'message' => $message];

        if ($this->shouldFail || in_array($token, $this->invalidTokens, true)) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => $this->failureError,
            ];
        }

        return [
            'success' => true,
            'message_id' => 'fake-message-'.uniqid(),
            'error' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function sendToTopic(string $topic, NotificationMessage $message): array
    {
        $this->topicMessages[] = ['topic' => $topic, 'message' => $message];

        if ($this->shouldFail) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => $this->failureError,
            ];
        }

        return [
            'success' => true,
            'message_id' => 'fake-topic-message-'.uniqid(),
            'error' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function sendMulticast(array $tokens, NotificationMessage $message): array
    {
        if (empty($tokens)) {
            return ['success_count' => 0, 'failure_count' => 0, 'results' => []];
        }

        $this->multicastMessages[] = ['tokens' => $tokens, 'message' => $message];

        $successCount = 0;
        $failureCount = 0;
        $results = [];

        foreach ($tokens as $token) {
            if ($this->shouldFail || in_array($token, $this->invalidTokens, true)) {
                $failureCount++;
                $results[$token] = [
                    'success' => false,
                    'message_id' => null,
                    'error' => $this->failureError,
                ];
            } else {
                $successCount++;
                $results[$token] = [
                    'success' => true,
                    'message_id' => 'fake-multicast-'.uniqid(),
                    'error' => null,
                ];
            }
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function sendData(string $token, array $data): array
    {
        $this->dataMessages[] = ['token' => $token, 'data' => $data];

        if ($this->shouldFail || in_array($token, $this->invalidTokens, true)) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => $this->failureError,
            ];
        }

        return [
            'success' => true,
            'message_id' => 'fake-data-message-'.uniqid(),
            'error' => null,
        ];
    }

    // =====================
    // Test Helper Methods
    // =====================

    /**
     * Reset all recorded operations.
     */
    public function reset(): void
    {
        $this->subscriptions = [];
        $this->unsubscriptions = [];
        $this->unsubscribedFromAll = [];
        $this->validatedTokens = [];
        $this->shouldFail = false;
        $this->failureError = 'UNREGISTERED';
        $this->invalidTokens = [];
        $this->sentMessages = [];
        $this->topicMessages = [];
        $this->multicastMessages = [];
        $this->dataMessages = [];
    }

    /**
     * Mark specific tokens as invalid.
     */
    public function markTokensInvalid(array $tokens): self
    {
        $this->invalidTokens = array_merge($this->invalidTokens, $tokens);

        return $this;
    }

    /**
     * Make all operations fail.
     */
    public function shouldFailWith(string $error = 'UNREGISTERED'): self
    {
        $this->shouldFail = true;
        $this->failureError = $error;

        return $this;
    }

    // =====================
    // Assertion Methods
    // =====================

    /**
     * Assert a token was subscribed to a topic.
     */
    public function assertSubscribed(string $topic, string $token): void
    {
        Assert::assertTrue(
            in_array($token, $this->subscriptions[$topic] ?? [], true),
            "Token was not subscribed to topic: {$topic}"
        );
    }

    /**
     * Assert a token was unsubscribed from a topic.
     */
    public function assertUnsubscribed(string $topic, string $token): void
    {
        Assert::assertTrue(
            in_array($token, $this->unsubscriptions[$topic] ?? [], true),
            "Token was not unsubscribed from topic: {$topic}"
        );
    }

    /**
     * Assert a token was unsubscribed from all topics.
     */
    public function assertUnsubscribedFromAll(string $token): void
    {
        Assert::assertTrue(
            in_array($token, $this->unsubscribedFromAll, true),
            'Token was not unsubscribed from all topics'
        );
    }

    /**
     * Assert no subscriptions were made.
     */
    public function assertNothingSubscribed(): void
    {
        Assert::assertEmpty(
            $this->subscriptions,
            'Expected no subscriptions, but found: '.json_encode($this->subscriptions)
        );
    }

    /**
     * Assert subscription count for a topic.
     */
    public function assertSubscriptionCount(string $topic, int $count): void
    {
        $actual = count($this->subscriptions[$topic] ?? []);
        Assert::assertEquals(
            $count,
            $actual,
            "Expected {$count} subscriptions to topic {$topic}, got {$actual}"
        );
    }

    // ========================================
    // Message Sending Assertion Methods
    // ========================================

    /**
     * Assert a message was sent to a specific token.
     */
    public function assertSent(string $token): void
    {
        $found = collect($this->sentMessages)->contains(
            fn ($m) => $m['token'] === $token
        );
        Assert::assertTrue($found, "No message sent to token: {$token}");
    }

    /**
     * Assert a message was sent to a topic.
     */
    public function assertSentToTopic(string $topic): void
    {
        $found = collect($this->topicMessages)->contains(
            fn ($m) => $m['topic'] === $topic
        );
        Assert::assertTrue($found, "No message sent to topic: {$topic}");
    }

    /**
     * Assert multicast was sent to expected number of tokens.
     */
    public function assertMulticastSent(int $expectedCount): void
    {
        $totalTokens = collect($this->multicastMessages)
            ->sum(fn ($m) => count($m['tokens']));
        Assert::assertEquals(
            $expectedCount,
            $totalTokens,
            "Expected {$expectedCount} multicast recipients, got {$totalTokens}"
        );
    }

    /**
     * Assert multicast included a specific token.
     */
    public function assertMulticastIncludesToken(string $token): void
    {
        $found = collect($this->multicastMessages)->contains(
            fn ($m) => in_array($token, $m['tokens'], true)
        );
        Assert::assertTrue($found, "Token not found in multicast: {$token}");
    }

    /**
     * Assert data message was sent to a token.
     */
    public function assertDataSent(string $token): void
    {
        $found = collect($this->dataMessages)->contains(
            fn ($m) => $m['token'] === $token
        );
        Assert::assertTrue($found, "No data message sent to token: {$token}");
    }

    /**
     * Assert no messages were sent at all.
     */
    public function assertNothingSent(): void
    {
        Assert::assertEmpty($this->sentMessages, 'Expected no messages sent');
        Assert::assertEmpty($this->topicMessages, 'Expected no topic messages sent');
        Assert::assertEmpty($this->multicastMessages, 'Expected no multicast messages sent');
        Assert::assertEmpty($this->dataMessages, 'Expected no data messages sent');
    }

    /**
     * Assert message content matches expectations.
     *
     * @param  callable(NotificationMessage): bool  $callback
     */
    public function assertSentWithContent(callable $callback): void
    {
        $allMessages = array_merge(
            array_column($this->sentMessages, 'message'),
            array_column($this->topicMessages, 'message'),
            array_column($this->multicastMessages, 'message')
        );

        $found = collect($allMessages)->contains($callback);
        Assert::assertTrue($found, 'No message matching content callback found');
    }

    /**
     * Get the last sent message for inspection.
     */
    public function getLastSentMessage(): ?NotificationMessage
    {
        if (! empty($this->sentMessages)) {
            return end($this->sentMessages)['message'];
        }
        if (! empty($this->multicastMessages)) {
            return end($this->multicastMessages)['message'];
        }
        if (! empty($this->topicMessages)) {
            return end($this->topicMessages)['message'];
        }

        return null;
    }
}
