<?php

namespace Asimnet\Notify\Database\Factories;

use Asimnet\Notify\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for Campaign model.
 *
 * مصنع لنموذج الحملة.
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'name' => $this->faker->words(3, true),
            'type' => Campaign::TYPE_BROADCAST,
            'status' => Campaign::STATUS_DRAFT,
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->paragraph(2),
            'image_url' => null,
            'action_url' => null,
            'payload' => null,
            'template_id' => null,
            'segment_id' => null,
            'channels' => ['fcm'],
            'recipient_query' => null,
            'recipient_count' => 0,
            'sent_count' => 0,
            'delivered_count' => 0,
            'failed_count' => 0,
            'scheduled_at' => null,
            'sent_at' => null,
        ];
    }

    /**
     * Set the campaign as scheduled.
     *
     * تعيين الحملة كمجدولة.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_SCHEDULED,
            'scheduled_at' => now()->addHour(),
        ]);
    }

    /**
     * Set the campaign as sent.
     *
     * تعيين الحملة كمرسلة.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_SENT,
            'sent_at' => now()->subMinutes(30),
            'sent_count' => $this->faker->numberBetween(50, 500),
            'failed_count' => $this->faker->numberBetween(0, 10),
        ]);
    }

    /**
     * Set the campaign as failed.
     *
     * تعيين الحملة كفاشلة.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_FAILED,
            'failed_count' => $this->faker->numberBetween(10, 100),
        ]);
    }

    /**
     * Set the campaign as sending.
     *
     * تعيين الحملة كقيد الإرسال.
     */
    public function sending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_SENDING,
        ]);
    }

    /**
     * Set the campaign as cancelled.
     *
     * تعيين الحملة كملغاة.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_CANCELLED,
        ]);
    }

    /**
     * Set campaign type to direct.
     *
     * تعيين نوع الحملة كمباشر.
     */
    public function direct(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Campaign::TYPE_DIRECT,
        ]);
    }

    /**
     * Set campaign type to topic.
     *
     * تعيين نوع الحملة كموضوع.
     */
    public function topic(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Campaign::TYPE_TOPIC,
        ]);
    }

    /**
     * Set campaign type to segment.
     *
     * تعيين نوع الحملة كشريحة.
     */
    public function segment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Campaign::TYPE_SEGMENT,
        ]);
    }

    /**
     * Set campaign with an image.
     *
     * تعيين حملة مع صورة.
     */
    public function withImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'image_url' => $this->faker->imageUrl(400, 300, 'notification'),
        ]);
    }

    /**
     * Set campaign with action URL.
     *
     * تعيين حملة مع رابط إجراء.
     */
    public function withActionUrl(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_url' => $this->faker->url(),
        ]);
    }

    /**
     * Set campaign with custom payload.
     *
     * تعيين حملة مع حمولة مخصصة.
     *
     * @param  array<string, mixed>  $payload
     */
    public function withPayload(array $payload): static
    {
        return $this->state(fn (array $attributes) => [
            'payload' => $payload,
        ]);
    }

    /**
     * Set campaign ready to send (scheduled in past).
     *
     * تعيين حملة جاهزة للإرسال (مجدولة في الماضي).
     */
    public function readyToSend(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Campaign::STATUS_SCHEDULED,
            'scheduled_at' => now()->subMinutes(5),
        ]);
    }

    /**
     * Set campaign with specific recipient count.
     *
     * تعيين حملة مع عدد مستلمين محدد.
     */
    public function withRecipientCount(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'recipient_count' => $count,
        ]);
    }
}
