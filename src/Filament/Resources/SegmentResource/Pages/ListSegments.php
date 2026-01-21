<?php

namespace Asimnet\Notify\Filament\Resources\SegmentResource\Pages;

use Asimnet\Notify\Filament\Resources\SegmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * List page for SegmentResource.
 *
 * صفحة قائمة شرائح الإشعارات.
 */
class ListSegments extends ListRecords
{
    protected static string $resource = SegmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
