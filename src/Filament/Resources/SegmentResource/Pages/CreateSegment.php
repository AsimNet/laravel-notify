<?php

namespace Asimnet\Notify\Filament\Resources\SegmentResource\Pages;

use Asimnet\Notify\Filament\Resources\SegmentResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page for SegmentResource.
 *
 * صفحة إنشاء شريحة جديدة.
 */
class CreateSegment extends CreateRecord
{
    protected static string $resource = SegmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure conditions has default structure if empty
        if (empty($data['conditions'])) {
            $data['conditions'] = [
                'operator' => 'and',
                'conditions' => [],
            ];
        }

        return $data;
    }
}
