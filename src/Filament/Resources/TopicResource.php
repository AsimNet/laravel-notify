<?php

namespace Asimnet\Notify\Filament\Resources;

use Asimnet\Notify\Filament\NotifyPlugin;
use Asimnet\Notify\Filament\Resources\TopicResource\Pages;
use Asimnet\Notify\Filament\Resources\TopicResource\RelationManagers;
use Asimnet\Notify\Models\Topic;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Filament resource for managing notification topics.
 *
 * مورد Filament لإدارة مواضيع الإشعارات.
 */
class TopicResource extends Resource
{
    protected static ?string $model = Topic::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Disable global search to prevent errors when table doesn't exist.
     *
     * تعطيل البحث الشامل لمنع الأخطاء عندما لا يوجد الجدول.
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }

    public static function getNavigationGroup(): ?string
    {
        return NotifyPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return NotifyPlugin::get()->getNavigationSort() + 1;
    }

    public static function getNavigationLabel(): string
    {
        return __('notify::filament.topics.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('notify::filament.topics.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('notify::filament.topics.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Section::make()
                ->schema([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label(__('notify::filament.topics.fields.name'))
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, Set $set) => $set('slug', Str::slug($state))),

                    \Filament\Forms\Components\TextInput::make('slug')
                        ->label(__('notify::filament.topics.fields.slug'))
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText(__('notify::filament.topics.fields.slug_help')),

                    \Filament\Forms\Components\Textarea::make('description')
                        ->label(__('notify::filament.topics.fields.description'))
                        ->rows(3)
                        ->columnSpanFull(),

                    \Filament\Forms\Components\Toggle::make('is_public')
                        ->label(__('notify::filament.topics.fields.is_public'))
                        ->helperText(__('notify::filament.topics.fields.is_public_help'))
                        ->default(true),

                    \Filament\Forms\Components\Toggle::make('is_default')
                        ->label(__('notify::filament.topics.fields.is_default'))
                        ->helperText(__('notify::filament.topics.fields.is_default_help'))
                        ->default(false),

                    \Filament\Forms\Components\Placeholder::make('subscriber_count')
                        ->label(__('notify::filament.topics.fields.subscriber_count'))
                        ->content(fn (?Topic $record) => $record?->subscriber_count ?? 0)
                        ->visibleOn('edit'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('notify::filament.topics.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label(__('notify::filament.topics.fields.slug'))
                    ->searchable(),

                TextColumn::make('description')
                    ->label(__('notify::filament.topics.fields.description'))
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_public')
                    ->label(__('notify::filament.topics.fields.is_public'))
                    ->boolean(),

                IconColumn::make('is_default')
                    ->label(__('notify::filament.topics.fields.is_default'))
                    ->boolean(),

                TextColumn::make('subscriber_count')
                    ->label(__('notify::filament.topics.fields.subscriber_count'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('notify::filament.common.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_public')
                    ->label(__('notify::filament.topics.fields.is_public')),

                TernaryFilter::make('is_default')
                    ->label(__('notify::filament.topics.fields.is_default')),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubscribersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTopics::route('/'),
            'create' => Pages\CreateTopic::route('/create'),
            'edit' => Pages\EditTopic::route('/{record}/edit'),
        ];
    }
}
