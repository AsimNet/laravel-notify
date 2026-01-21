<?php

namespace Asimnet\Notify\Database\Factories;

use Asimnet\Notify\Models\Topic;
use Asimnet\Notify\Models\TopicSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class TopicSubscriptionFactory extends Factory
{
    protected $model = TopicSubscription::class;

    public function definition(): array
    {
        return [
            'topic_id' => Topic::factory(),
            'user_id' => 1, // Override in tests with actual user
            'fcm_synced' => false,
        ];
    }

    /**
     * Mark subscription as synced with FCM.
     */
    public function synced(): static
    {
        return $this->state(fn (array $attributes) => [
            'fcm_synced' => true,
        ]);
    }

    /**
     * Mark subscription as unsynced (pending FCM sync).
     */
    public function unsynced(): static
    {
        return $this->state(fn (array $attributes) => [
            'fcm_synced' => false,
        ]);
    }
}
