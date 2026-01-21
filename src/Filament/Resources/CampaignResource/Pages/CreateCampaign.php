<?php

namespace Asimnet\Notify\Filament\Resources\CampaignResource\Pages;

use Asimnet\Notify\Filament\Resources\CampaignResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page for notification campaigns.
 *
 * صفحة إنشاء حملات الإشعارات.
 *
 * Handles campaign creation with optional scheduling support.
 *
 * يتعامل مع إنشاء الحملات مع دعم الجدولة الاختيارية.
 */
class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle scheduling
        // معالجة الجدولة
        if (empty($data['send_immediately']) && ! empty($data['scheduled_at'])) {
            $data['status'] = 'scheduled';
        } else {
            $data['status'] = 'draft';
            $data['scheduled_at'] = null;
        }

        // Remove virtual field
        // إزالة الحقل الافتراضي
        unset($data['send_immediately']);

        return $data;
    }
}
