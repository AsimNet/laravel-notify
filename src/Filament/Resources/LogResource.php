<?php

namespace Asimnet\Notify\Filament\Resources;

use Asimnet\Notify\Filament\NotifyPlugin;
use Asimnet\Notify\Filament\Resources\LogResource\Pages;
use Asimnet\Notify\Models\NotificationLog;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filament resource for notification logs.
 *
 * مورد Filament لسجلات الإشعارات.
 *
 * Provides read-only access to notification delivery logs
 * with comprehensive filtering and search capabilities.
 *
 * يوفر وصولاً للقراءة فقط لسجلات تسليم الإشعارات
 * مع إمكانيات تصفية وبحث شاملة.
 */
class LogResource extends Resource
{
    protected static ?string $model = NotificationLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $recordTitleAttribute = 'title';

    /**
     * Disable create functionality (read-only resource).
     *
     * تعطيل إنشاء سجلات جديدة (مورد للقراءة فقط).
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationGroup(): ?string
    {
        return NotifyPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return NotifyPlugin::get()->getNavigationSort() + 3;
    }

    public static function getNavigationLabel(): string
    {
        return __('notify::filament.logs.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('notify::filament.logs.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('notify::filament.logs.plural_model_label');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('notify::filament.logs.fields.title'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('channel')
                    ->label(__('notify::filament.logs.fields.channel'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("notify::filament.logs.channels.{$state}")),

                TextColumn::make('status')
                    ->label(__('notify::filament.logs.fields.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent', 'delivered', 'opened' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => __("notify::filament.logs.statuses.{$state}")),

                TextColumn::make('user.full_name')
                    ->label(__('notify::filament.logs.fields.user'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_test')
                    ->label(__('notify::filament.logs.fields.is_test'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('error_message')
                    ->label(__('notify::filament.logs.fields.error_message'))
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sent_at')
                    ->label(__('notify::filament.logs.fields.sent_at'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('notify::filament.common.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('notify::filament.logs.filters.status'))
                    ->options([
                        'pending' => __('notify::filament.logs.statuses.pending'),
                        'sent' => __('notify::filament.logs.statuses.sent'),
                        'delivered' => __('notify::filament.logs.statuses.delivered'),
                        'opened' => __('notify::filament.logs.statuses.opened'),
                        'failed' => __('notify::filament.logs.statuses.failed'),
                    ]),

                SelectFilter::make('channel')
                    ->label(__('notify::filament.logs.filters.channel'))
                    ->options([
                        'fcm' => 'FCM',
                    ]),

                TernaryFilter::make('is_test')
                    ->label(__('notify::filament.logs.filters.is_test')),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')
                            ->label(__('notify::filament.logs.filters.from')),
                        DatePicker::make('until')
                            ->label(__('notify::filament.logs.filters.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = __('notify::filament.logs.filters.from').': '.$data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = __('notify::filament.logs.filters.until').': '.$data['until'];
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('notify::filament.logs.sections.notification'))
                    ->schema([
                        TextEntry::make('title')
                            ->label(__('notify::filament.logs.fields.title')),
                        TextEntry::make('body')
                            ->label(__('notify::filament.logs.fields.body'))
                            ->columnSpanFull(),
                        TextEntry::make('channel')
                            ->label(__('notify::filament.logs.fields.channel'))
                            ->badge(),
                    ])->columns(2),

                Section::make(__('notify::filament.logs.sections.delivery'))
                    ->schema([
                        TextEntry::make('status')
                            ->label(__('notify::filament.logs.fields.status'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'sent', 'delivered', 'opened' => 'success',
                                'pending' => 'warning',
                                'failed' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('user.full_name')
                            ->label(__('notify::filament.logs.fields.user')),
                        TextEntry::make('sent_at')
                            ->label(__('notify::filament.logs.fields.sent_at'))
                            ->dateTime(),
                        TextEntry::make('delivered_at')
                            ->label(__('notify::filament.logs.fields.delivered_at'))
                            ->dateTime(),
                        TextEntry::make('opened_at')
                            ->label(__('notify::filament.logs.fields.opened_at'))
                            ->dateTime(),
                    ])->columns(2),

                Section::make(__('notify::filament.logs.sections.error'))
                    ->schema([
                        TextEntry::make('error_code')
                            ->label(__('notify::filament.logs.fields.error_code')),
                        TextEntry::make('error_message')
                            ->label(__('notify::filament.logs.fields.error_message'))
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (NotificationLog $record): bool => $record->status === 'failed'),

                Section::make(__('notify::filament.logs.sections.technical'))
                    ->schema([
                        TextEntry::make('external_id')
                            ->label(__('notify::filament.logs.fields.external_id')),
                        TextEntry::make('device_token_id')
                            ->label(__('notify::filament.logs.fields.device_token')),
                        IconEntry::make('is_test')
                            ->label(__('notify::filament.logs.fields.is_test'))
                            ->boolean(),
                        TextEntry::make('created_at')
                            ->label(__('notify::filament.common.created_at'))
                            ->dateTime(),
                    ])->columns(2)->collapsed(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLogs::route('/'),
            'view' => Pages\ViewLog::route('/{record}'),
        ];
    }
}
