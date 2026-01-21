<?php

namespace Asimnet\Notify\Filament\Resources\TopicResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Relation manager for displaying topic subscribers.
 *
 * مدير العلاقات لعرض مشتركي الموضوع.
 */
class SubscribersRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('notify::filament.topics.subscribers');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.full_name')
                    ->label(__('notify::filament.topics.subscriber_name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label(__('notify::filament.logs.fields.user'))
                    ->searchable()
                    ->toggleable(),

                IconColumn::make('fcm_synced')
                    ->label(__('notify::filament.topics.synced'))
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label(__('notify::filament.topics.subscribed_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('fcm_synced')
                    ->label(__('notify::filament.topics.synced')),
            ])
            ->actions([
                DeleteAction::make()
                    ->label(__('notify::notify.unsubscribe'))
                    ->modalHeading(__('notify::filament.topics.unsubscribe_heading'))
                    ->modalDescription(__('notify::filament.topics.unsubscribe_confirmation'))
                    ->modalSubmitActionLabel(__('notify::notify.unsubscribe')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('notify::notify.unsubscribe')),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('notify::filament.topics.no_subscribers'))
            ->emptyStateDescription(__('notify::filament.topics.no_subscribers_description'));
    }
}
