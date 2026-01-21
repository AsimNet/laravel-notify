<?php

namespace Asimnet\Notify\Database\Factories;

use Asimnet\Notify\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationTemplateFactory extends Factory
{
    protected $model = NotificationTemplate::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->unique()->slug(2),
            'title' => 'Hello {user.name}!',
            'body' => 'This is a notification for {user.email} from {tenant_name}.',
            'image_url' => null,
            'variables' => [
                'user.name' => 'Recipient name',
                'user.email' => 'Recipient email',
                'tenant_name' => 'Organization name',
            ],
            'is_active' => true,
        ];
    }

    /**
     * Set template as inactive.
     *
     * تعيين القالب كغير نشط.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific slug.
     *
     * تعيين معرف محدد.
     */
    public function slug(string $slug): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => $slug,
        ]);
    }

    /**
     * Add an image URL.
     *
     * إضافة رابط صورة.
     */
    public function withImage(?string $url = null): static
    {
        return $this->state(fn (array $attributes) => [
            'image_url' => $url ?? $this->faker->imageUrl(400, 300),
        ]);
    }

    /**
     * Create a simple template without variables.
     *
     * إنشاء قالب بسيط بدون متغيرات.
     */
    public function simple(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->paragraph(1),
            'variables' => [],
        ]);
    }

    /**
     * Create a welcome template.
     *
     * إنشاء قالب ترحيب.
     */
    public function welcome(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Welcome Message',
            'slug' => 'welcome',
            'title' => 'Welcome {user.name}!',
            'body' => 'Thank you for joining {tenant_name}. We are excited to have you!',
            'variables' => [
                'user.name' => 'New member name',
                'tenant_name' => 'Organization name',
            ],
        ]);
    }
}
