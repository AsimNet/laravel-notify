<?php

namespace Asimnet\Notify\Filament\Resources\TopicResource\Pages;

use App\Filament\Resources\BulkNotifications\BulkNotificationsResource;
use Asimnet\Notify\Filament\Resources\TopicResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTopic extends EditRecord
{
    protected static string $resource = TopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send_to_subscribers')
                ->label('إرسال للمشتركين')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->url(fn (): string => BulkNotificationsResource::getUrl('create', [
                    'owner_type' => urlencode($this->record::class),
                    'owner_id' => $this->record->getKey(),
                ]))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
