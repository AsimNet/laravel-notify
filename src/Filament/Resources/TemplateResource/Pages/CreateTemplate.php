<?php

namespace Asimnet\Notify\Filament\Resources\TemplateResource\Pages;

use Asimnet\Notify\Filament\Resources\TemplateResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page for notification templates.
 *
 * صفحة إنشاء قالب إشعار جديد.
 */
class CreateTemplate extends CreateRecord
{
    protected static string $resource = TemplateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
