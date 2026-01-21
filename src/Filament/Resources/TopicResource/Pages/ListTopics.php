<?php

namespace Asimnet\Notify\Filament\Resources\TopicResource\Pages;

use Asimnet\Notify\Filament\Resources\TopicResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTopics extends ListRecords
{
    protected static string $resource = TopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
