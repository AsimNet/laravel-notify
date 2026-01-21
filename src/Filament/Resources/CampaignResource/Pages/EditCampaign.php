<?php

namespace Asimnet\Notify\Filament\Resources\CampaignResource\Pages;

use Asimnet\Notify\Filament\Resources\CampaignResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit page for notification campaigns.
 *
 * صفحة تعديل حملات الإشعارات.
 *
 * Includes send action in header for draft/scheduled campaigns.
 *
 * يتضمن إجراء الإرسال في الترويسة للحملات المسودة/المجدولة.
 */
class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label(__('notify::filament.campaigns.actions.send'))
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('notify::filament.campaigns.actions.send_confirm'))
                ->modalDescription(fn (): string => __('notify::filament.campaigns.actions.send_description', [
                    'count' => $this->record->segment?->getUserCount() ?? 0,
                ])
                )
                ->visible(fn (): bool => in_array($this->record->status, ['draft', 'scheduled'])
                )
                ->action(function () {
                    CampaignResource::sendCampaign($this->record);
                    $this->refreshFormData(['status', 'sent_at', 'sent_count', 'failed_count']);
                }),

            DeleteAction::make()
                ->visible(fn (): bool => in_array($this->record->status, ['draft', 'cancelled', 'failed'])
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
