<?php

namespace Asimnet\Notify\Filament\Resources\LogResource\Pages;

use Asimnet\Notify\Filament\Resources\LogResource;
use Filament\Resources\Pages\ListRecords;

/**
 * List page for notification logs.
 *
 * صفحة قائمة سجلات الإشعارات.
 *
 * Read-only list view with no header actions (no create button).
 *
 * عرض قائمة للقراءة فقط بدون إجراءات في الترويسة (بدون زر إنشاء).
 */
class ListLogs extends ListRecords
{
    protected static string $resource = LogResource::class;

    // No header actions (read-only resource)
    // لا توجد إجراءات في الترويسة (مورد للقراءة فقط)
}
