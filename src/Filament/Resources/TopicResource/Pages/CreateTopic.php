<?php

namespace Asimnet\Notify\Filament\Resources\TopicResource\Pages;

use Asimnet\Notify\Filament\Resources\TopicResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTopic extends CreateRecord
{
    protected static string $resource = TopicResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
