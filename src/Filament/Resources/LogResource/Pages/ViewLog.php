<?php

namespace Asimnet\Notify\Filament\Resources\LogResource\Pages;

use Asimnet\Notify\Filament\Resources\LogResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * View page for notification log details.
 *
 * صفحة عرض تفاصيل سجل الإشعار.
 *
 * Displays comprehensive log information using the infolist schema.
 *
 * تعرض معلومات السجل الشاملة باستخدام مخطط قائمة المعلومات.
 */
class ViewLog extends ViewRecord
{
    protected static string $resource = LogResource::class;
}
