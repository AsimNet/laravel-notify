<?php

namespace Asimnet\Notify\Filament;

use Asimnet\Notify\Filament\Resources\LogResource;
use Asimnet\Notify\Filament\Resources\TopicResource;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;

/**
 * Filament plugin for the Notify package.
 *
 * إضافة Filament لحزمة الإشعارات.
 */
class NotifyPlugin implements Plugin
{
    use EvaluatesClosures;

    protected bool $hasTopicResource = true;

    protected bool $hasLogResource = true;

    protected string|Closure|null $navigationGroup = null;

    protected int|Closure $navigationSort = 100;

    /**
     * Create a new plugin instance.
     *
     * إنشاء نسخة جديدة من الإضافة.
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Get the registered plugin instance.
     *
     * الحصول على نسخة الإضافة المسجلة.
     */
    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    /**
     * Get the plugin identifier.
     *
     * الحصول على معرف الإضافة.
     */
    public function getId(): string
    {
        return 'notify';
    }

    /**
     * Register the plugin with the panel.
     *
     * تسجيل الإضافة مع اللوحة.
     */
    public function register(Panel $panel): void
    {
        $resources = [];

        if ($this->hasTopicResource) {
            $resources[] = TopicResource::class;
        }

        if ($this->hasLogResource) {
            $resources[] = LogResource::class;
        }

        $panel->resources($resources);
    }

    /**
     * Boot the plugin after registration.
     *
     * تشغيل الإضافة بعد التسجيل.
     */
    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * Enable or disable the topic resource.
     *
     * تمكين أو تعطيل مورد المواضيع.
     */
    public function topicResource(bool $enabled = true): static
    {
        $this->hasTopicResource = $enabled;

        return $this;
    }

    /**
     * Enable or disable the log resource.
     *
     * تمكين أو تعطيل مورد السجلات.
     */
    public function logResource(bool $enabled = true): static
    {
        $this->hasLogResource = $enabled;

        return $this;
    }

    /**
     * Set the navigation group.
     *
     * تعيين مجموعة التنقل.
     */
    public function navigationGroup(string|Closure|null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    /**
     * Set the navigation sort order.
     *
     * تعيين ترتيب التنقل.
     */
    public function navigationSort(int|Closure $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    /**
     * Get the navigation group.
     *
     * الحصول على مجموعة التنقل.
     */
    public function getNavigationGroup(): ?string
    {
        $group = $this->evaluate($this->navigationGroup);

        return $group ?? __('notify::filament.navigation_group');
    }

    /**
     * Get the navigation sort order.
     *
     * الحصول على ترتيب التنقل.
     */
    public function getNavigationSort(): int
    {
        return $this->evaluate($this->navigationSort);
    }

    /**
     * Check if topic resource is enabled.
     *
     * التحقق من تمكين مورد المواضيع.
     */
    public function hasTopicResource(): bool
    {
        return $this->hasTopicResource;
    }

    /**
     * Check if log resource is enabled.
     *
     * التحقق من تمكين مورد السجلات.
     */
    public function hasLogResource(): bool
    {
        return $this->hasLogResource;
    }
}
