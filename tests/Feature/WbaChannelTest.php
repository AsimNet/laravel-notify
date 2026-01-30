<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Channels\WbaChannel;
use Asimnet\WbaFilament\Services\WbaService;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Mockery;

class WbaChannelTest extends \Asimnet\Notify\Tests\TestCase
{
    public function test_wba_channel_sends_via_wba_service(): void
    {
        if (! class_exists(WbaService::class)) {
            $this->markTestSkipped('wba-filament not installed');
        }

        $wba = Mockery::mock(WbaService::class);
        $wba->shouldReceive('sendByTemplateName')
            ->once()
            ->with('+111222333', 'greeting', 'ar', ['name' => 'Test'], null)
            ->andReturn((object) ['id' => 99]);

        $this->app->instance(WbaService::class, $wba);

        $notifiable = new class
        {
            use Notifiable;

            public string $phone = '+111222333';

            public function routeNotificationForWba(): string
            {
                return $this->phone;
            }
        };

        $notification = new class extends Notification
        {
            public function via($notifiable): array
            {
                return [WbaChannel::class];
            }

            public function toWba($notifiable): array
            {
                return [
                    'template_name' => 'greeting',
                    'language' => 'ar',
                    'parameters' => ['name' => 'Test'],
                ];
            }
        };

        $notifiable->notify($notification);
    }
}
