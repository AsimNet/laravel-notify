<?php

namespace Asimnet\Notify\Filament\Resources;

use Asimnet\Notify\DTOs\NotificationMessage;
use Asimnet\Notify\Filament\NotifyPlugin;
use Asimnet\Notify\Filament\Resources\CampaignResource\Pages;
use Asimnet\Notify\Models\Campaign;
use Asimnet\Notify\Models\NotificationTemplate;
use Asimnet\Notify\Models\Segment;
use Asimnet\Notify\NotifyManager;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components as FormInputs;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components as Layout;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Filament resource for notification campaigns.
 *
 * مورد Filament لحملات الإشعارات.
 *
 * Provides full CRUD for creating, editing, sending, and scheduling
 * notification campaigns with template and segment targeting.
 *
 * يوفر CRUD كامل لإنشاء وتعديل وإرسال وجدولة
 * حملات الإشعارات مع استهداف القوالب والشرائح.
 */
class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return NotifyPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return NotifyPlugin::get()->getNavigationSort() - 1;
    }

    public static function getNavigationLabel(): string
    {
        return __('notify::filament.campaigns.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('notify::filament.campaigns.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('notify::filament.campaigns.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Layout\Section::make(__('notify::filament.campaigns.sections.basic'))
                ->schema([
                    FormInputs\TextInput::make('name')
                        ->label(__('notify::filament.campaigns.fields.name'))
                        ->required()
                        ->maxLength(255),

                    FormInputs\Select::make('status')
                        ->label(__('notify::filament.campaigns.fields.status'))
                        ->options([
                            'draft' => __('notify::filament.campaigns.statuses.draft'),
                            'scheduled' => __('notify::filament.campaigns.statuses.scheduled'),
                            'sending' => __('notify::filament.campaigns.statuses.sending'),
                            'sent' => __('notify::filament.campaigns.statuses.sent'),
                            'failed' => __('notify::filament.campaigns.statuses.failed'),
                            'cancelled' => __('notify::filament.campaigns.statuses.cancelled'),
                        ])
                        ->default('draft')
                        ->disabled()
                        ->dehydrated()
                        ->visibleOn('edit'),
                ])->columns(2),

            Layout\Section::make(__('notify::filament.campaigns.sections.content'))
                ->schema([
                    FormInputs\Select::make('template_id')
                        ->label(__('notify::filament.campaigns.fields.template'))
                        ->relationship('template', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state) {
                                $template = NotificationTemplate::find($state);
                                if ($template) {
                                    $set('title', $template->title);
                                    $set('body', $template->body);
                                    $set('image_url', $template->image_url);
                                }
                            }
                        })
                        ->helperText(__('notify::filament.campaigns.fields.template_help')),

                    FormInputs\TextInput::make('title')
                        ->label(__('notify::filament.campaigns.fields.title'))
                        ->required()
                        ->maxLength(255),

                    FormInputs\Textarea::make('body')
                        ->label(__('notify::filament.campaigns.fields.body'))
                        ->required()
                        ->rows(4),

                    FormInputs\TextInput::make('image_url')
                        ->label(__('notify::filament.campaigns.fields.image_url'))
                        ->url()
                        ->maxLength(500),
                ]),

            Layout\Section::make(__('notify::filament.campaigns.sections.targeting'))
                ->schema([
                    FormInputs\Select::make('segment_id')
                        ->label(__('notify::filament.campaigns.fields.segment'))
                        ->relationship('segment', 'name', fn ($query) => $query->active())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state) {
                                $segment = Segment::find($state);
                                $set('_recipient_count', $segment?->getUserCount() ?? 0);
                            } else {
                                $set('_recipient_count', 0);
                            }
                        })
                        ->required(),

                    FormInputs\Placeholder::make('recipient_count')
                        ->label(__('notify::filament.campaigns.fields.recipient_count'))
                        ->content(function (callable $get, ?Campaign $record) {
                            $segmentId = $get('segment_id');
                            if ($segmentId) {
                                $segment = Segment::find($segmentId);

                                return $segment?->getUserCount() ?? 0;
                            }

                            return $record?->segment?->getUserCount() ?? '-';
                        }),
                ])->columns(2),

            Layout\Section::make(__('notify::filament.campaigns.sections.schedule'))
                ->schema([
                    FormInputs\Toggle::make('send_immediately')
                        ->label(__('notify::filament.campaigns.fields.send_immediately'))
                        ->default(true)
                        ->live()
                        ->dehydrated(false),

                    FormInputs\DateTimePicker::make('scheduled_at')
                        ->label(__('notify::filament.campaigns.fields.scheduled_at'))
                        ->visible(fn (callable $get) => ! $get('send_immediately'))
                        ->required(fn (callable $get) => ! $get('send_immediately'))
                        ->minDate(now()->addMinutes(5)),
                ])->visibleOn('create'),

            Layout\Section::make(__('notify::filament.campaigns.sections.results'))
                ->schema([
                    FormInputs\Placeholder::make('success_count_display')
                        ->label(__('notify::filament.campaigns.fields.success_count'))
                        ->content(fn (?Campaign $record) => $record?->sent_count ?? 0),

                    FormInputs\Placeholder::make('failure_count_display')
                        ->label(__('notify::filament.campaigns.fields.failure_count'))
                        ->content(fn (?Campaign $record) => $record?->failed_count ?? 0),

                    FormInputs\Placeholder::make('sent_at_display')
                        ->label(__('notify::filament.campaigns.fields.sent_at'))
                        ->content(fn (?Campaign $record) => $record?->sent_at?->format('Y-m-d H:i') ?? '-'),
                ])->columns(3)->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('notify::filament.campaigns.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('template.name')
                    ->label(__('notify::filament.campaigns.fields.template'))
                    ->toggleable(),

                TextColumn::make('segment.name')
                    ->label(__('notify::filament.campaigns.fields.segment'))
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(__('notify::filament.campaigns.fields.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'scheduled' => 'info',
                        'sending' => 'warning',
                        'sent' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => __("notify::filament.campaigns.statuses.{$state}")),

                TextColumn::make('sent_count')
                    ->label(__('notify::filament.campaigns.fields.success_count'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('failed_count')
                    ->label(__('notify::filament.campaigns.fields.failure_count'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('scheduled_at')
                    ->label(__('notify::filament.campaigns.fields.scheduled_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('sent_at')
                    ->label(__('notify::filament.campaigns.fields.sent_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('notify::filament.common.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('notify::filament.campaigns.fields.status'))
                    ->options([
                        'draft' => __('notify::filament.campaigns.statuses.draft'),
                        'scheduled' => __('notify::filament.campaigns.statuses.scheduled'),
                        'sent' => __('notify::filament.campaigns.statuses.sent'),
                        'failed' => __('notify::filament.campaigns.statuses.failed'),
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn (Campaign $record): bool => $record->status === 'draft'),

                    Action::make('send')
                        ->label(__('notify::filament.campaigns.actions.send'))
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('notify::filament.campaigns.actions.send_confirm'))
                        ->modalDescription(fn (Campaign $record): string => __('notify::filament.campaigns.actions.send_description', [
                            'count' => $record->segment?->getUserCount() ?? 0,
                        ])
                        )
                        ->visible(fn (Campaign $record): bool => in_array($record->status, ['draft', 'scheduled'])
                        )
                        ->action(function (Campaign $record) {
                            static::sendCampaign($record);
                        }),

                    Action::make('cancel')
                        ->label(__('notify::filament.campaigns.actions.cancel'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Campaign $record): bool => $record->status === 'scheduled')
                        ->action(function (Campaign $record) {
                            $record->update(['status' => 'cancelled']);

                            Notification::make()
                                ->success()
                                ->title(__('notify::filament.campaigns.notifications.cancelled'))
                                ->send();
                        }),

                    DeleteAction::make()
                        ->visible(fn (Campaign $record): bool => in_array($record->status, ['draft', 'cancelled', 'failed'])
                        ),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Send the campaign to all segment users.
     *
     * إرسال الحملة إلى جميع مستخدمي الشريحة.
     */
    public static function sendCampaign(Campaign $record): void
    {
        if (! $record->segment) {
            Notification::make()
                ->danger()
                ->title(__('notify::filament.campaigns.notifications.failed'))
                ->body(__('notify::notify.error_no_recipients'))
                ->send();

            return;
        }

        $record->update(['status' => 'sending']);

        try {
            $message = NotificationMessage::create(
                $record->title,
                $record->body
            );

            if ($record->image_url) {
                $message = $message->withImage($record->image_url);
            }

            $result = app(NotifyManager::class)
                ->sendToSegment($record->segment, $message);

            $record->update([
                'status' => $result['success'] ? 'sent' : 'failed',
                'sent_at' => now(),
                'sent_count' => $result['success_count'],
                'failed_count' => $result['failure_count'],
            ]);

            if ($result['success']) {
                Notification::make()
                    ->success()
                    ->title(__('notify::filament.campaigns.notifications.sent'))
                    ->body(__('notify::filament.campaigns.notifications.sent_body', [
                        'success' => $result['success_count'],
                        'failure' => $result['failure_count'],
                    ]))
                    ->send();
            } else {
                Notification::make()
                    ->danger()
                    ->title(__('notify::filament.campaigns.notifications.failed'))
                    ->body($result['error'] ?? __('notify::notify.error_sending'))
                    ->send();
            }
        } catch (\Exception $e) {
            $record->update(['status' => 'failed']);

            Notification::make()
                ->danger()
                ->title(__('notify::filament.campaigns.notifications.failed'))
                ->body($e->getMessage())
                ->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'view' => Pages\ViewCampaign::route('/{record}'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
