<?php

namespace Asimnet\Notify\Filament\Resources\TemplateResource\Pages;

use Asimnet\Notify\Filament\Resources\TemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit page for notification templates.
 *
 * صفحة تعديل قالب الإشعارات.
 */
class EditTemplate extends EditRecord
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
