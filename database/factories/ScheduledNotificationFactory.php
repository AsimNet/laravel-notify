<?php

namespace Asimnet\Notify\Database\Factories;

use Asimnet\Notify\Models\ScheduledNotification;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledNotificationFactory extends Factory
{
    protected $model = ScheduledNotification::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'user_id' => null, // Must be set in tests
            'channel' => 'fcm',
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->paragraph(1),
            'image_url' => null,
            'action_url' => null,
            'payload' => null,
            'template_id' => null,
            'template_variables' => null,
            'scheduled_at' => now()->addHours(1), // Future by default
            'sent_at' => null,
            'cancelled_at' => null,
            'cancelled_by' => null,
            'cancellation_reason' => null,
            'failed_at' => null,
            'error_message' => null,
            'is_test' => false,
        ];
    }

    /**
     * Create a notification that is due (past scheduled time, ready to send).
     *
     * إنشاء إشعار مستحق (وقت مجدول في الماضي، جاهز للإرسال).
     */
    public function due(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => now()->subMinutes(5),
        ]);
    }

    /**
     * Create a notification that has been sent.
     *
     * إنشاء إشعار تم إرساله.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => now()->subHour(),
            'sent_at' => now(),
        ]);
    }

    /**
     * Create a notification that has been cancelled.
     *
     * إنشاء إشعار تم إلغاؤه.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'cancelled_at' => now(),
            'cancellation_reason' => __('notify::messages.cancelled_by_admin'),
        ]);
    }

    /**
     * Create a notification that has failed.
     *
     * إنشاء إشعار فشل.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => now()->subHour(),
            'failed_at' => now(),
            'error_message' => __('notify::messages.no_device_tokens'),
        ]);
    }

    /**
     * Mark as test notification.
     *
     * تحديد كإشعار اختبار.
     */
    public function test(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_test' => true,
        ]);
    }

    /**
     * Schedule for a specific date/time.
     *
     * جدولة لتاريخ/وقت محدد.
     */
    public function scheduledFor(DateTimeInterface $dateTime): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => $dateTime,
        ]);
    }

    /**
     * Set user ID.
     *
     * تعيين معرف المستخدم.
     */
    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Set template with variables.
     *
     * تعيين القالب مع المتغيرات.
     */
    public function withTemplate(int $templateId, array $variables = []): static
    {
        return $this->state(fn (array $attributes) => [
            'template_id' => $templateId,
            'template_variables' => $variables,
            'title' => null,
            'body' => null,
        ]);
    }

    /**
     * Set custom payload data.
     *
     * تعيين بيانات الحمولة المخصصة.
     */
    public function withPayload(array $payload): static
    {
        return $this->state(fn (array $attributes) => [
            'payload' => $payload,
        ]);
    }

    /**
     * Set a specific channel.
     *
     * تعيين قناة محددة.
     */
    public function channel(string $channel): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => $channel,
        ]);
    }
}
