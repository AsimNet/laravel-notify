<?php

namespace Asimnet\Notify\Database\Factories;

use Asimnet\Notify\Models\NotificationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'campaign_id' => null,
            'user_id' => null,
            'device_token_id' => null,
            'channel' => 'fcm',
            'status' => NotificationLog::STATUS_SENT,
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->paragraph(1),
            'payload' => null,
            'external_id' => 'projects/test/messages/'.$this->faker->uuid(),
            'error_message' => null,
            'error_code' => null,
            'sent_at' => now(),
            'delivered_at' => null,
            'opened_at' => null,
            'is_test' => false,
        ];
    }

    /**
     * Set status to pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationLog::STATUS_PENDING,
            'sent_at' => null,
            'external_id' => null,
        ]);
    }

    /**
     * Set status to sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationLog::STATUS_SENT,
            'sent_at' => now(),
            'external_id' => 'projects/test/messages/'.$this->faker->uuid(),
        ]);
    }

    /**
     * Set status to delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationLog::STATUS_DELIVERED,
            'sent_at' => now()->subMinutes(5),
            'delivered_at' => now(),
            'external_id' => 'projects/test/messages/'.$this->faker->uuid(),
        ]);
    }

    /**
     * Set status to opened.
     */
    public function opened(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationLog::STATUS_OPENED,
            'sent_at' => now()->subMinutes(10),
            'delivered_at' => now()->subMinutes(5),
            'opened_at' => now(),
            'external_id' => 'projects/test/messages/'.$this->faker->uuid(),
        ]);
    }

    /**
     * Set status to failed.
     */
    public function failed(?string $message = null, ?string $code = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationLog::STATUS_FAILED,
            'sent_at' => null,
            'external_id' => null,
            'error_message' => $message ?? 'UNREGISTERED',
            'error_code' => $code ?? 'messaging/registration-token-not-registered',
        ]);
    }

    /**
     * Create an old log for pruning tests.
     */
    public function old(int $days = 200): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subDays($days),
            'sent_at' => now()->subDays($days),
        ]);
    }

    /**
     * Mark as test notification.
     */
    public function test(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_test' => true,
        ]);
    }

    /**
     * Set a specific channel.
     */
    public function channel(string $channel): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => $channel,
        ]);
    }

    /**
     * Set user ID.
     */
    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Include full payload.
     */
    public function withPayload(array $payload = []): static
    {
        return $this->state(fn (array $attributes) => [
            'payload' => $payload ?: [
                'title' => $attributes['title'] ?? $this->faker->sentence(4),
                'body' => $attributes['body'] ?? $this->faker->paragraph(1),
                'data' => ['key' => 'value'],
            ],
        ]);
    }
}
