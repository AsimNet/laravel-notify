<?php

namespace Asimnet\Notify\Filament\Resources\CampaignResource\Pages;

use Asimnet\Notify\Filament\Resources\CampaignResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * View page for notification campaigns.
 *
 * صفحة عرض حملات الإشعارات.
 *
 * Read-only view of campaign details and results.
 *
 * عرض للقراءة فقط لتفاصيل ونتائج الحملة.
 */
class ViewCampaign extends ViewRecord
{
    protected static string $resource = CampaignResource::class;
}
