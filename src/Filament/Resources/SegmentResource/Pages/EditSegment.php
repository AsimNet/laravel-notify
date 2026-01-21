<?php

namespace Asimnet\Notify\Filament\Resources\SegmentResource\Pages;

use Asimnet\Notify\Filament\Resources\SegmentResource;
use Asimnet\Notify\Models\Segment;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit page for SegmentResource with refresh count action.
 *
 * صفحة تعديل الشريحة مع إجراء تحديث العدد.
 */
class EditSegment extends EditRecord
{
    protected static string $resource = SegmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh_count')
                ->label(__('notify::filament.segments.actions.refresh_count'))
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    /** @var Segment $record */
                    $record = $this->record;
                    $count = $record->refreshCachedCount();

                    Notification::make()
                        ->success()
                        ->title(__('notify::filament.segments.notifications.count_refreshed', ['count' => $count]))
                        ->send();

                    $this->refreshFormData(['cached_count', 'cached_at']);
                }),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
