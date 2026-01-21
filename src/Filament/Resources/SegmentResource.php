<?php

namespace Asimnet\Notify\Filament\Resources;

use Asimnet\Notify\Filament\NotifyPlugin;
use Asimnet\Notify\Filament\Resources\SegmentResource\Pages;
use Asimnet\Notify\Models\Segment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components as FormInputs;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Resources\Resource;
use Filament\Schemas\Components as Layout;
use Filament\Schemas\Schema;
use Filament\Tables\Columns as TableColumns;
use Filament\Tables\Filters as TableFilters;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Filament resource for managing notification segments.
 *
 * مورد Filament لإدارة شرائح الإشعارات.
 *
 * Provides a visual query builder for creating audience segments
 * with nested AND/OR conditions using AG Grid filter format.
 *
 * يوفر منشئ استعلام مرئي لإنشاء شرائح الجمهور
 * مع شروط AND/OR المتداخلة بتنسيق فلتر AG Grid.
 */
class SegmentResource extends Resource
{
    protected static ?string $model = Segment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return NotifyPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return NotifyPlugin::get()->getNavigationSort() + 2;
    }

    public static function getNavigationLabel(): string
    {
        return __('notify::filament.segments.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('notify::filament.segments.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('notify::filament.segments.plural_model_label');
    }

    /**
     * Get user field options for condition builder.
     *
     * الحصول على خيارات حقول المستخدم لمنشئ الشروط.
     *
     * @return array<string, string>
     */
    protected static function getUserFieldOptions(): array
    {
        return [
            'id' => __('notify::filament.segments.user_fields.id'),
            'full_name' => __('notify::filament.segments.user_fields.full_name'),
            'email' => __('notify::filament.segments.user_fields.email'),
            'phone' => __('notify::filament.segments.user_fields.phone'),
            'gender' => __('notify::filament.segments.user_fields.gender'),
            'city' => __('notify::filament.segments.user_fields.city'),
            'created_at' => __('notify::filament.segments.user_fields.created_at'),
        ];
    }

    /**
     * Get text operators for filter conditions.
     *
     * الحصول على عوامل النص لشروط الفلتر.
     *
     * @return array<string, string>
     */
    protected static function getTextOperators(): array
    {
        return [
            'equals' => __('notify::filament.segments.filter_operators.equals'),
            'notEqual' => __('notify::filament.segments.filter_operators.notEqual'),
            'contains' => __('notify::filament.segments.filter_operators.contains'),
            'notContains' => __('notify::filament.segments.filter_operators.notContains'),
            'startsWith' => __('notify::filament.segments.filter_operators.startsWith'),
            'endsWith' => __('notify::filament.segments.filter_operators.endsWith'),
            'blank' => __('notify::filament.segments.filter_operators.blank'),
            'notBlank' => __('notify::filament.segments.filter_operators.notBlank'),
        ];
    }

    /**
     * Get number operators for filter conditions.
     *
     * الحصول على عوامل الأرقام لشروط الفلتر.
     *
     * @return array<string, string>
     */
    protected static function getNumberOperators(): array
    {
        return [
            'equals' => __('notify::filament.segments.filter_operators.equals'),
            'notEqual' => __('notify::filament.segments.filter_operators.notEqual'),
            'greaterThan' => __('notify::filament.segments.filter_operators.greaterThan'),
            'greaterThanOrEqual' => __('notify::filament.segments.filter_operators.greaterThanOrEqual'),
            'lessThan' => __('notify::filament.segments.filter_operators.lessThan'),
            'lessThanOrEqual' => __('notify::filament.segments.filter_operators.lessThanOrEqual'),
            'inRange' => __('notify::filament.segments.filter_operators.inRange'),
            'blank' => __('notify::filament.segments.filter_operators.blank'),
            'notBlank' => __('notify::filament.segments.filter_operators.notBlank'),
        ];
    }

    /**
     * Get date operators for filter conditions.
     *
     * الحصول على عوامل التاريخ لشروط الفلتر.
     *
     * @return array<string, string>
     */
    protected static function getDateOperators(): array
    {
        return [
            'equals' => __('notify::filament.segments.filter_operators.equals'),
            'notEqual' => __('notify::filament.segments.filter_operators.notEqual'),
            'greaterThan' => __('notify::filament.segments.filter_operators.greaterThan'),
            'lessThan' => __('notify::filament.segments.filter_operators.lessThan'),
            'inRange' => __('notify::filament.segments.filter_operators.inRange'),
            'blank' => __('notify::filament.segments.filter_operators.blank'),
            'notBlank' => __('notify::filament.segments.filter_operators.notBlank'),
        ];
    }

    /**
     * Get operators based on filter type.
     *
     * الحصول على العوامل بناءً على نوع الفلتر.
     *
     * @return array<string, string>
     */
    protected static function getOperatorsForFilterType(?string $filterType): array
    {
        return match ($filterType) {
            'number' => static::getNumberOperators(),
            'date' => static::getDateOperators(),
            'set' => [
                'equals' => __('notify::filament.segments.filter_operators.equals'),
                'notEqual' => __('notify::filament.segments.filter_operators.notEqual'),
            ],
            default => static::getTextOperators(),
        };
    }

    /**
     * Get the condition schema for both Builder block and Repeater.
     *
     * الحصول على مخطط الشرط لكل من كتلة Builder والمكرر.
     *
     * @return array<\Filament\Forms\Components\Component>
     */
    protected static function getConditionSchema(): array
    {
        return [
            Layout\Grid::make(4)->schema([
                FormInputs\Select::make('field')
                    ->label(__('notify::filament.segments.field'))
                    ->options(static::getUserFieldOptions())
                    ->required()
                    ->live(),

                FormInputs\Select::make('filterType')
                    ->label(__('notify::filament.segments.filter_type'))
                    ->options([
                        'text' => __('notify::filament.segments.types.text'),
                        'number' => __('notify::filament.segments.types.number'),
                        'date' => __('notify::filament.segments.types.date'),
                        'set' => __('notify::filament.segments.types.set'),
                    ])
                    ->default('text')
                    ->required()
                    ->live(),

                FormInputs\Select::make('type')
                    ->label(__('notify::filament.segments.operator'))
                    ->options(fn (callable $get) => static::getOperatorsForFilterType($get('filterType')))
                    ->required(),

                FormInputs\TextInput::make('filter')
                    ->label(__('notify::filament.segments.value'))
                    ->visible(fn (callable $get) => ! in_array($get('type'), ['blank', 'notBlank'])),
            ]),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Layout\Section::make(__('notify::filament.segments.sections.basic'))
                ->schema([
                    FormInputs\TextInput::make('name')
                        ->label(__('notify::filament.segments.fields.name'))
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),

                    FormInputs\TextInput::make('slug')
                        ->label(__('notify::filament.segments.fields.slug'))
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    FormInputs\Textarea::make('description')
                        ->label(__('notify::filament.segments.fields.description'))
                        ->rows(2)
                        ->columnSpanFull(),

                    FormInputs\Toggle::make('is_active')
                        ->label(__('notify::filament.segments.fields.is_active'))
                        ->default(true),

                    FormInputs\Placeholder::make('cached_count_display')
                        ->label(__('notify::filament.segments.fields.cached_count'))
                        ->content(fn (?Segment $record) => $record
                            ? ($record->cached_count ?? __('notify::filament.segments.not_calculated'))
                            : '-')
                        ->visibleOn('edit'),
                ])->columns(2),

            Layout\Section::make(__('notify::filament.segments.sections.conditions'))
                ->schema([
                    FormInputs\Select::make('conditions.operator')
                        ->label(__('notify::filament.segments.group_operator'))
                        ->options([
                            'and' => __('notify::filament.segments.operators.and'),
                            'or' => __('notify::filament.segments.operators.or'),
                        ])
                        ->default('and')
                        ->required(),

                    Builder::make('conditions.conditions')
                        ->label(__('notify::filament.segments.conditions'))
                        ->blocks([
                            Block::make('condition')
                                ->label(fn (?array $state): string => isset($state['field'])
                                    ? (static::getUserFieldOptions()[$state['field']] ?? $state['field'])
                                    : __('notify::filament.segments.new_condition'))
                                ->schema(static::getConditionSchema()),

                            Block::make('group')
                                ->label(fn (?array $state): string => ($state['operator'] ?? 'and') === 'and'
                                    ? __('notify::filament.segments.and_group')
                                    : __('notify::filament.segments.or_group'))
                                ->schema([
                                    FormInputs\Select::make('operator')
                                        ->label(__('notify::filament.segments.group_operator'))
                                        ->options([
                                            'and' => __('notify::filament.segments.operators.and'),
                                            'or' => __('notify::filament.segments.operators.or'),
                                        ])
                                        ->default('and')
                                        ->required()
                                        ->live(),

                                    FormInputs\Repeater::make('conditions')
                                        ->label(__('notify::filament.segments.conditions'))
                                        ->schema(static::getConditionSchema())
                                        ->defaultItems(1)
                                        ->addActionLabel(__('notify::filament.segments.add_condition')),
                                ]),
                        ])
                        ->addActionLabel(__('notify::filament.segments.add_condition'))
                        ->blockPickerColumns(2)
                        ->collapsible()
                        ->cloneable()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TableColumns\TextColumn::make('name')
                    ->label(__('notify::filament.segments.fields.name'))
                    ->searchable()
                    ->sortable(),

                TableColumns\TextColumn::make('slug')
                    ->label(__('notify::filament.segments.fields.slug'))
                    ->searchable()
                    ->toggleable(),

                TableColumns\TextColumn::make('description')
                    ->label(__('notify::filament.segments.fields.description'))
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TableColumns\IconColumn::make('is_active')
                    ->label(__('notify::filament.segments.fields.is_active'))
                    ->boolean(),

                TableColumns\TextColumn::make('cached_count')
                    ->label(__('notify::filament.segments.fields.cached_count'))
                    ->numeric()
                    ->sortable()
                    ->placeholder(__('notify::filament.segments.not_calculated')),

                TableColumns\TextColumn::make('cached_at')
                    ->label(__('notify::filament.segments.fields.cached_at'))
                    ->dateTime()
                    ->toggleable(),

                TableColumns\TextColumn::make('created_at')
                    ->label(__('notify::filament.common.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                TableFilters\TernaryFilter::make('is_active')
                    ->label(__('notify::filament.segments.fields.is_active')),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => Pages\ListSegments::route('/'),
            'create' => Pages\CreateSegment::route('/create'),
            'edit' => Pages\EditSegment::route('/{record}/edit'),
        ];
    }
}
