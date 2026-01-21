<?php

namespace Asimnet\Notify\Filament;

use Asimnet\Notify\Filament\Pages\ManageNotifySettings;
use Asimnet\Notify\Filament\Resources\CampaignResource;
use Asimnet\Notify\Filament\Resources\LogResource;
use Asimnet\Notify\Filament\Resources\SegmentResource;
use Asimnet\Notify\Filament\Resources\TemplateResource;
use Asimnet\Notify\Filament\Resources\TopicResource;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;

/**
 * Filament plugin for the Notify package.
 *
 * إضافة Filament لحزمة الإشعارات.
 *
 * Provides configurable resources for managing notifications,
 * campaigns, templates, topics, and logs in Filament admin panel.
 *
 * توفر موارد قابلة للتكوين لإدارة الإشعارات والحملات
 * والقوالب والمواضيع والسجلات في لوحة إدارة Filament.
 */
class NotifyPlugin implements Plugin
{
    use EvaluatesClosures;

    protected bool $hasCampaignResource = true;

    protected bool $hasTemplateResource = true;

    protected bool $hasTopicResource = true;

    protected bool $hasLogResource = true;

    protected bool $hasSegmentResource = true;

    protected bool $hasSettingsPage = true;

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
        $pages = [];
        $widgets = [];

        // Register resources based on configuration
        // تسجيل الموارد بناءً على التكوين

        if ($this->hasCampaignResource) {
            $resources[] = CampaignResource::class;
        }

        if ($this->hasTemplateResource) {
            $resources[] = TemplateResource::class;
        }

        if ($this->hasTopicResource) {
            $resources[] = TopicResource::class;
        }

        if ($this->hasSegmentResource) {
            $resources[] = SegmentResource::class;
        }

        if ($this->hasLogResource) {
            $resources[] = LogResource::class;
        }

        if ($this->hasSettingsPage) {
            $pages[] = ManageNotifySettings::class;
        }

        $panel
            ->resources($resources)
            ->pages($pages)
            ->widgets($widgets);
    }

    /**
     * Boot the plugin after registration.
     *
     * تشغيل الإضافة بعد التسجيل.
     */
    public function boot(Panel $panel): void
    {
        // Additional initialization after registration
        // تهيئة إضافية بعد التسجيل
    }

    /**
     * Enable or disable the campaign resource.
     *
     * تمكين أو تعطيل مورد الحملات.
     */
    public function campaignResource(bool $enabled = true): static
    {
        $this->hasCampaignResource = $enabled;

        return $this;
    }

    /**
     * Enable or disable the template resource.
     *
     * تمكين أو تعطيل مورد القوالب.
     */
    public function templateResource(bool $enabled = true): static
    {
        $this->hasTemplateResource = $enabled;

        return $this;
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
     * Enable or disable the segment resource.
     *
     * تمكين أو تعطيل مورد الشرائح.
     */
    public function segmentResource(bool $enabled = true): static
    {
        $this->hasSegmentResource = $enabled;

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
     * Check if campaign resource is enabled.
     *
     * التحقق من تمكين مورد الحملات.
     */
    public function hasCampaignResource(): bool
    {
        return $this->hasCampaignResource;
    }

    /**
     * Check if template resource is enabled.
     *
     * التحقق من تمكين مورد القوالب.
     */
    public function hasTemplateResource(): bool
    {
        return $this->hasTemplateResource;
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

    /**
     * Check if segment resource is enabled.
     *
     * التحقق من تمكين مورد الشرائح.
     */
    public function hasSegmentResource(): bool
    {
        return $this->hasSegmentResource;
    }

    /**
     * Enable or disable the settings page.
     *
     * تمكين أو تعطيل صفحة الإعدادات.
     */
    public function settingsPage(bool $enabled = true): static
    {
        $this->hasSettingsPage = $enabled;

        return $this;
    }

    /**
     * Check if settings page is enabled.
     *
     * التحقق من تمكين صفحة الإعدادات.
     */
    public function hasSettingsPage(): bool
    {
        return $this->hasSettingsPage;
    }
}
