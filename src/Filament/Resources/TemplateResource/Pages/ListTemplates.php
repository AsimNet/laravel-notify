<?php

namespace Asimnet\Notify\Filament\Resources\TemplateResource\Pages;

use Asimnet\Notify\Filament\Resources\TemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * List page for notification templates.
 *
 * صفحة قائمة قوالب الإشعارات.
 */
class ListTemplates extends ListRecords
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
