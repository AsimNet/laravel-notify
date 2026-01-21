<?php

namespace Asimnet\Notify\Filament\Resources;

use Asimnet\Notify\Filament\NotifyPlugin;
use Asimnet\Notify\Filament\Resources\TemplateResource\Pages;
use Asimnet\Notify\Models\NotificationTemplate;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components as FormInputs;
use Filament\Resources\Resource;
use Filament\Schemas\Components as Layout;
use Filament\Schemas\Schema;
use Filament\Tables\Columns as TableColumns;
use Filament\Tables\Filters as TableFilters;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Filament resource for notification templates.
 *
 * مورد Filament لقوالب الإشعارات.
 */
class TemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationGroup(): ?string
    {
        return NotifyPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return NotifyPlugin::get()->getNavigationSort();
    }

    public static function getNavigationLabel(): string
    {
        return __('notify::filament.templates.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('notify::filament.templates.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('notify::filament.templates.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Layout\Section::make(__('notify::filament.templates.sections.basic'))
                ->schema([
                    FormInputs\TextInput::make('name')
                        ->label(__('notify::filament.templates.fields.name'))
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),

                    FormInputs\TextInput::make('slug')
                        ->label(__('notify::filament.templates.fields.slug'))
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    FormInputs\Toggle::make('is_active')
                        ->label(__('notify::filament.templates.fields.is_active'))
                        ->default(true),
                ])->columns(3),

            Layout\Section::make(__('notify::filament.templates.sections.content'))
                ->schema([
                    FormInputs\TextInput::make('title')
                        ->label(__('notify::filament.templates.fields.title'))
                        ->required()
                        ->maxLength(255)
                        ->helperText(__('notify::filament.templates.fields.title_help', ['example' => '{user.name}'])),

                    FormInputs\Textarea::make('body')
                        ->label(__('notify::filament.templates.fields.body'))
                        ->required()
                        ->rows(4)
                        ->helperText(__('notify::filament.templates.fields.body_help')),

                    FormInputs\TextInput::make('image_url')
                        ->label(__('notify::filament.templates.fields.image_url'))
                        ->url()
                        ->maxLength(500),
                ]),

            Layout\Section::make(__('notify::filament.templates.sections.variables'))
                ->schema([
                    FormInputs\Placeholder::make('variables_help')
                        ->content(__('notify::filament.templates.fields.variables_help'))
                        ->columnSpanFull(),

                    FormInputs\KeyValue::make('variables')
                        ->label(__('notify::filament.templates.fields.variables'))
                        ->keyLabel(__('notify::filament.templates.fields.variable_key'))
                        ->valueLabel(__('notify::filament.templates.fields.variable_default'))
                        ->addActionLabel(__('notify::filament.templates.actions.add_variable')),
                ])->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TableColumns\TextColumn::make('name')
                    ->label(__('notify::filament.templates.fields.name'))
                    ->searchable()
                    ->sortable(),

                TableColumns\TextColumn::make('slug')
                    ->label(__('notify::filament.templates.fields.slug'))
                    ->toggleable(),

                TableColumns\TextColumn::make('title')
                    ->label(__('notify::filament.templates.fields.title'))
                    ->searchable()
                    ->limit(50),

                TableColumns\IconColumn::make('is_active')
                    ->label(__('notify::filament.templates.fields.is_active'))
                    ->boolean(),

                TableColumns\TextColumn::make('created_at')
                    ->label(__('notify::filament.common.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TableFilters\TernaryFilter::make('is_active')
                    ->label(__('notify::filament.templates.fields.is_active')),
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
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTemplates::route('/'),
            'create' => Pages\CreateTemplate::route('/create'),
            'edit' => Pages\EditTemplate::route('/{record}/edit'),
        ];
    }
}
