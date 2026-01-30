<?php

namespace Asimnet\Notify\Filament\Pages;

use Asimnet\Notify\Filament\NotifyPlugin;
use Asimnet\Notify\Settings\NotifySettings;
use Exception;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

/**
 * Manage Notify Settings Page.
 *
 * صفحة إدارة إعدادات الإشعارات.
 *
 * @property Schema $form
 */
class ManageNotifySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    protected static ?int $navigationSort = 99;

    protected string $view = 'notify::filament.pages.manage-notify-settings';

    public ?array $data = [];

    /**
     * Get the navigation label.
     *
     * الحصول على عنوان التنقل.
     */
    public static function getNavigationLabel(): string
    {
        return __('notify::filament.settings.navigation_label');
    }

    /**
     * Get the page title.
     *
     * الحصول على عنوان الصفحة.
     */
    public function getTitle(): string
    {
        return __('notify::filament.settings.page_title');
    }

    /**
     * Get the navigation group.
     *
     * الحصول على مجموعة التنقل.
     */
    public static function getNavigationGroup(): ?string
    {
        return NotifyPlugin::get()->getNavigationGroup();
    }

    /**
     * Check if the page can be accessed.
     *
     * التحقق من إمكانية الوصول للصفحة.
     */
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    /**
     * Get the form state path.
     *
     * الحصول على مسار حالة النموذج.
     */
    public function getFormStatePath(): ?string
    {
        return 'data';
    }

    /**
     * Mount the page and initialize form data.
     *
     * تحميل الصفحة وتهيئة بيانات النموذج.
     */
    public function mount(): void
    {
        try {
            $settings = app(NotifySettings::class);
            $this->form->fill([
                // FCM Settings (credentials not filled - write-only for security)
                'fcm_enabled' => $settings->fcm_enabled,
                'fcm_credentials_json' => null,

                // SMS Settings
                'sms_enabled' => $settings->sms_enabled,
                'sms_default_driver' => $settings->sms_default_driver,
                'sms_credentials_json' => $settings->sms_credentials,

                // WBA Settings (write-only credentials)
                'wba_enabled' => $settings->wba_enabled,
                'wba_default_language' => $settings->wba_default_language,
                'wba_credentials' => $this->decodeCredentials($settings->wba_credentials),

                // Logging Settings
                'logging_enabled' => $settings->logging_enabled,
                'log_retention_days' => $settings->log_retention_days,
                'log_store_payload' => $settings->log_store_payload,

                // Rate Limiting
                'rate_limit_per_minute' => $settings->rate_limit_per_minute,
                'rate_limit_per_user_per_hour' => $settings->rate_limit_per_user_per_hour,

                // Default Topic Settings
                'auto_subscribe_to_defaults' => $settings->auto_subscribe_to_defaults,

            ]);
        } catch (Exception $e) {
            Log::error('Error loading notify settings: '.$e->getMessage());
            Notification::make()
                ->title(__('notify::filament.settings.errors.load_failed'))
                ->danger()
                ->send();
        }
    }

    /**
     * Define the form schema.
     *
     * تحديد مخطط النموذج.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('settings')
                    ->tabs([
                        // FCM Tab
                        Tab::make('fcm')
                            ->label(__('notify::filament.settings.tabs.fcm'))
                            ->icon(Heroicon::DevicePhoneMobile)
                            ->schema([
                                Section::make(__('notify::filament.settings.sections.fcm'))
                                    ->description(__('notify::filament.settings.sections.fcm_description'))
                                    ->schema([
                                        Toggle::make('fcm_enabled')
                                            ->label(__('notify::filament.settings.fields.fcm_enabled'))
                                            ->helperText(__('notify::filament.settings.fields.fcm_enabled_help'))
                                            ->inline(false),

                                        $this->getFcmCredentialsStatus(),

                                        Textarea::make('fcm_credentials_json')
                                            ->label(__('notify::filament.settings.fields.fcm_credentials_json'))
                                            ->helperText(__('notify::filament.settings.fields.fcm_credentials_json_help'))
                                            ->placeholder(__('notify::filament.settings.fields.fcm_credentials_json_placeholder'))
                                            ->rows(8)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // Logging Tab
                        Tab::make('logging')
                            ->label(__('notify::filament.settings.tabs.logging'))
                            ->icon(Heroicon::ClipboardDocumentList)
                            ->schema([
                                Section::make(__('notify::filament.settings.sections.logging'))
                                    ->description(__('notify::filament.settings.sections.logging_description'))
                                    ->schema([
                                        Toggle::make('logging_enabled')
                                            ->label(__('notify::filament.settings.fields.logging_enabled'))
                                            ->helperText(__('notify::filament.settings.fields.logging_enabled_help'))
                                            ->inline(false),

                                        TextInput::make('log_retention_days')
                                            ->label(__('notify::filament.settings.fields.log_retention_days'))
                                            ->helperText(__('notify::filament.settings.fields.log_retention_days_help'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(365)
                                            ->suffix(__('notify::filament.settings.fields.days')),

                                        Toggle::make('log_store_payload')
                                            ->label(__('notify::filament.settings.fields.log_store_payload'))
                                            ->helperText(__('notify::filament.settings.fields.log_store_payload_help'))
                                            ->inline(false),
                                    ]),
                            ]),

                        // SMS Tab
                        Tab::make('sms')
                            ->label(__('notify::filament.settings.tabs.sms'))
                            ->icon(Heroicon::ChatBubbleBottomCenterText)
                            ->schema([
                                Section::make(__('notify::filament.settings.sections.sms'))
                                    ->description(__('notify::filament.settings.sections.sms_description'))
                                    ->schema([
                                        Toggle::make('sms_enabled')
                                            ->label(__('notify::filament.settings.fields.sms_enabled'))
                                            ->helperText(__('notify::filament.settings.fields.sms_enabled_help'))
                                            ->inline(false),

                                        Select::make('sms_default_driver')
                                            ->label(__('notify::filament.settings.fields.sms_default_driver'))
                                            ->helperText(__('notify::filament.settings.fields.sms_default_driver_help'))
                                            ->options($this->getSmsDriverOptions())
                                            ->searchable()
                                            ->preload(),

                                        Textarea::make('sms_credentials_json')
                                            ->label(__('notify::filament.settings.fields.sms_credentials_json'))
                                            ->helperText(__('notify::filament.settings.fields.sms_credentials_json_help'))
                                            ->placeholder(__('notify::filament.settings.fields.sms_credentials_json_placeholder'))
                                            ->rows(6)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // WBA Tab
                        Tab::make('wba')
                            ->label(__('notify::filament.settings.tabs.wba'))
                            ->icon(Heroicon::ChatBubbleOvalLeftEllipsis)
                            ->schema([
                                Section::make(__('notify::filament.settings.sections.wba'))
                                    ->description(__('notify::filament.settings.sections.wba_description'))
                                    ->schema([
                                        Toggle::make('wba_enabled')
                                            ->label(__('notify::filament.settings.fields.wba_enabled'))
                                            ->helperText(__('notify::filament.settings.fields.wba_enabled_help'))
                                            ->inline(false),

                                        TextInput::make('wba_default_language')
                                            ->label(__('notify::filament.settings.fields.wba_default_language'))
                                            ->helperText(__('notify::filament.settings.fields.wba_default_language_help'))
                                            ->default('ar'),

                                        KeyValue::make('wba_credentials')
                                            ->label(__('notify::filament.settings.fields.wba_credentials'))
                                            ->helperText(__('notify::filament.settings.fields.wba_credentials_json_help'))
                                            ->keyLabel('Key')
                                            ->valueLabel('Value')
                                            ->default([
                                                'page_token' => null,
                                                'phone_number_id' => null,
                                                'app_secret' => null,
                                                'verify_token' => null,
                                            ])
                                            ->reorderable(false)
                                            ->addActionLabel(__('notify::filament.settings.fields.wba_credentials'))
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // Rate Limiting Tab
                        Tab::make('rate_limiting')
                            ->label(__('notify::filament.settings.tabs.rate_limiting'))
                            ->icon(Heroicon::ShieldCheck)
                            ->schema([
                                Section::make(__('notify::filament.settings.sections.rate_limiting'))
                                    ->description(__('notify::filament.settings.sections.rate_limiting_description'))
                                    ->schema([
                                        TextInput::make('rate_limit_per_minute')
                                            ->label(__('notify::filament.settings.fields.rate_limit_per_minute'))
                                            ->helperText(__('notify::filament.settings.fields.rate_limit_per_minute_help'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(1000)
                                            ->suffix(__('notify::filament.settings.fields.per_minute')),

                                        TextInput::make('rate_limit_per_user_per_hour')
                                            ->label(__('notify::filament.settings.fields.rate_limit_per_user_per_hour'))
                                            ->helperText(__('notify::filament.settings.fields.rate_limit_per_user_per_hour_help'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(10)
                                            ->suffix(__('notify::filament.settings.fields.per_hour')),
                                    ]),
                            ]),

                        // Topics Tab
                        Tab::make('topics')
                            ->label(__('notify::filament.settings.tabs.topics'))
                            ->icon(Heroicon::ChatBubbleLeftRight)
                            ->schema([
                                Section::make(__('notify::filament.settings.sections.topics'))
                                    ->description(__('notify::filament.settings.sections.topics_description'))
                                    ->schema([
                                        Toggle::make('auto_subscribe_to_defaults')
                                            ->label(__('notify::filament.settings.fields.auto_subscribe_to_defaults'))
                                            ->helperText(__('notify::filament.settings.fields.auto_subscribe_to_defaults_help'))
                                            ->inline(false),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString(),
            ]);
    }

    /**
     * Get FCM credentials status placeholder.
     *
     * الحصول على حالة بيانات الاعتماد (بدون فك التشفير).
     */
    protected function getFcmCredentialsStatus(): Placeholder
    {
        $settings = app(NotifySettings::class);

        $content = $settings->fcm_has_credentials
            ? '<span class="text-success-600 dark:text-success-400">✓ '.__('notify::filament.settings.fcm_configured').'</span>'
            : '<span class="text-warning-600 dark:text-warning-400">✗ '.__('notify::filament.settings.fcm_not_configured').'</span>';

        return Placeholder::make('fcm_credentials_status')
            ->label(__('notify::filament.settings.fields.fcm_status'))
            ->content(new HtmlString($content));
    }

    /**
     * Decode credentials string to array for KeyValue.
     *
     * @return array<string, string|null>
     */
    protected function decodeCredentials(?string $json): array
    {
        if (! $json) {
            return [
                'page_token' => null,
                'phone_number_id' => null,
                'app_secret' => null,
                'verify_token' => null,
            ];
        }

        $data = json_decode($json, true);

        return is_array($data) ? $data : [
            'page_token' => null,
            'phone_number_id' => null,
            'app_secret' => null,
            'verify_token' => null,
        ];
    }

    /**
     * SMS driver options from config.
     *
     * @return array<string, string>
     */
    protected function getSmsDriverOptions(): array
    {
        $drivers = config('notify.sms.drivers', []);

        $options = [];
        foreach ($drivers as $key => $driver) {
            $options[$key] = $driver['name'] ?? $key;
        }

        return $options;
    }

    /**
     * Save the settings.
     *
     * حفظ الإعدادات.
     */
    public function save(): void
    {
        try {
            $data = $this->form->getState();
            $settings = app(NotifySettings::class);

            // FCM Settings
            $settings->fcm_enabled = $data['fcm_enabled'] ?? true;

            // Only update credentials if new value provided (write-only field)
            if (! empty($data['fcm_credentials_json'])) {
                $json = json_decode($data['fcm_credentials_json'], true);
                if ($json && ! empty($json['project_id']) && ! empty($json['private_key'])) {
                    $settings->fcm_credentials_json = $data['fcm_credentials_json'];
                    $settings->fcm_has_credentials = true;
                } else {
                    throw new Exception(__('notify::filament.settings.errors.invalid_credentials'));
                }
            }

            // Logging Settings
            $settings->logging_enabled = $data['logging_enabled'] ?? true;
            $settings->log_retention_days = (int) ($data['log_retention_days'] ?? 180);
            $settings->log_store_payload = $data['log_store_payload'] ?? false;

            // SMS Settings
            $settings->sms_enabled = (bool) ($data['sms_enabled'] ?? false);
            $settings->sms_default_driver = $data['sms_default_driver'] ?? null;

            if (! empty($data['sms_credentials_json'])) {
                $settings->sms_credentials = $data['sms_credentials_json'];
            }

            // Rate Limiting
            $settings->rate_limit_per_minute = (int) ($data['rate_limit_per_minute'] ?? 1000);
            $settings->rate_limit_per_user_per_hour = (int) ($data['rate_limit_per_user_per_hour'] ?? 10);

            // WBA Settings
            $settings->wba_enabled = (bool) ($data['wba_enabled'] ?? false);
            $settings->wba_default_language = $data['wba_default_language'] ?? 'ar';
            $settings->wba_credentials = ! empty($data['wba_credentials'])
                ? json_encode($data['wba_credentials'])
                : null;

            // Default Topic Settings
            $settings->auto_subscribe_to_defaults = $data['auto_subscribe_to_defaults'] ?? true;

            $settings->save();

            Notification::make()
                ->title(__('notify::filament.settings.notifications.saved'))
                ->success()
                ->send();
        } catch (Exception $e) {
            Log::error('Error saving notify settings: '.$e->getMessage());
            Notification::make()
                ->title(__('notify::filament.settings.errors.save_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
