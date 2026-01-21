<?php

namespace Asimnet\Notify\Filament\Resources\CampaignResource\Pages;

use Asimnet\Notify\Filament\Resources\CampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * List page for notification campaigns.
 *
 * صفحة قائمة حملات الإشعارات.
 */
class ListCampaigns extends ListRecords
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
