<?php

namespace Asimnet\Notify\Database\Factories;

use Asimnet\Notify\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TopicFactory extends Factory
{
    protected $model = Topic::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'is_public' => true,
            'is_default' => false,
            'subscriber_count' => 0,
        ];
    }

    /**
     * Make topic public (users can subscribe).
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Make topic private (admin-only subscription).
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    /**
     * Make topic a default topic (auto-subscribe new users).
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
            'slug' => 'public',
            'name' => 'Public Announcements',
        ]);
    }

    /**
     * Set subscriber count.
     */
    public function withSubscribers(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'subscriber_count' => $count,
        ]);
    }
}
