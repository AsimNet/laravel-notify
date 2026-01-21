<?php

namespace Asimnet\Notify\Database\Factories;

use Asimnet\Notify\Models\DeviceToken;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceTokenFactory extends Factory
{
    protected $model = DeviceToken::class;

    public function definition(): array
    {
        return [
            'user_id' => 1, // Override in tests with actual user
            'token' => 'fcm_'.$this->faker->sha256.'_'.$this->faker->sha256,
            'platform' => $this->faker->randomElement(['ios', 'android', 'web']),
            'device_name' => $this->faker->randomElement([
                'iPhone-'.$this->faker->firstName,
                'Samsung-'.$this->faker->firstName,
                'Web-'.$this->faker->domainWord,
            ]),
            'last_active_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Set platform to iOS.
     */
    public function ios(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'ios',
            'device_name' => 'iPhone-'.$this->faker->firstName,
        ]);
    }

    /**
     * Set platform to Android.
     */
    public function android(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'android',
            'device_name' => 'Samsung-'.$this->faker->firstName,
        ]);
    }

    /**
     * Set platform to Web.
     */
    public function web(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'web',
            'device_name' => 'Web-'.$this->faker->domainWord,
        ]);
    }

    /**
     * Mark token as stale (old last_active_at).
     */
    public function stale(int $days = 60): static
    {
        return $this->state(fn (array $attributes) => [
            'last_active_at' => now()->subDays($days),
        ]);
    }
}
