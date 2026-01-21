<?php

namespace Asimnet\Notify\Jobs;

use Asimnet\Notify\DTOs\NotificationMessage;
use Asimnet\Notify\Models\ScheduledNotification;
use Asimnet\Notify\NotifyManager;
use Asimnet\Notify\Services\NotificationLogger;
use Asimnet\Notify\Services\TemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\Skip;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job for sending scheduled notifications.
 *
 * This job processes individual scheduled notifications with Skip middleware
 * for cancellation support. It handles tenant context restoration, message
 * building (from direct content or templates), and proper error logging.
 *
 * وظيفة لإرسال الإشعارات المجدولة.
 * تعالج هذه الوظيفة الإشعارات المجدولة الفردية مع دعم الإلغاء.
 */
class SendScheduledNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ScheduledNotification $scheduledNotification,
        public ?string $tenantId = null
    ) {
        $this->queue = 'notifications';
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            Skip::when(fn () => $this->shouldSkip()),
        ];
    }

    /**
     * Check if the job should be skipped.
     *
     * Skips if the notification has been cancelled or already sent.
     */
    private function shouldSkip(): bool
    {
        // Refresh from database to get latest state
        $this->scheduledNotification->refresh();

        if ($this->scheduledNotification->cancelled_at !== null) {
            Log::debug('Skipping cancelled scheduled notification', [
                'scheduled_id' => $this->scheduledNotification->id,
            ]);

            return true;
        }

        if ($this->scheduledNotification->sent_at !== null) {
            Log::debug('Skipping already-sent scheduled notification', [
                'scheduled_id' => $this->scheduledNotification->id,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->initializeTenancy();

        $scheduled = $this->scheduledNotification;

        try {
            $message = $this->buildMessage();

            /** @var NotifyManager $notify */
            $notify = app(NotifyManager::class);

            $result = $notify->sendToUser($scheduled->user_id, $message);

            $scheduled->markAsSent();

            // Log the send operation
            /** @var NotificationLogger $logger */
            $logger = app(NotificationLogger::class);
            $logger->logSend(
                message: $message,
                result: $result,
                channel: $scheduled->channel ?? 'fcm',
                userId: $scheduled->user_id,
                deviceTokenId: null,
                campaignId: null,
                isTest: $scheduled->is_test ?? false
            );

            Log::info('Scheduled notification sent successfully', [
                'scheduled_id' => $scheduled->id,
                'user_id' => $scheduled->user_id,
                'success' => $result['success'],
            ]);
        } catch (Throwable $e) {
            $scheduled->markAsFailed($e->getMessage());

            Log::error('Scheduled notification failed', [
                'scheduled_id' => $scheduled->id,
                'error' => $e->getMessage(),
            ]);

            // Re-throw for retry mechanism
            throw $e;
        }
    }

    /**
     * Build the notification message from scheduled notification data.
     *
     * If a template is attached, renders the template. Otherwise, builds
     * a message from direct content fields.
     */
    private function buildMessage(): NotificationMessage
    {
        $scheduled = $this->scheduledNotification;

        // If template is attached, use template rendering
        if ($scheduled->template_id && $scheduled->template) {
            /** @var TemplateRenderer $renderer */
            $renderer = app(TemplateRenderer::class);

            return $renderer->render(
                $scheduled->template,
                $scheduled->template_variables ?? []
            );
        }

        // Build message from direct content
        $message = NotificationMessage::create(
            $scheduled->title,
            $scheduled->body
        );

        if ($scheduled->image_url) {
            $message = $message->withImage($scheduled->image_url);
        }

        if ($scheduled->action_url) {
            $message = $message->withData(['action_url' => $scheduled->action_url]);
        }

        if ($scheduled->payload) {
            $message = $message->withData($scheduled->payload);
        }

        return $message;
    }

    /**
     * Initialize tenant context if multi-tenancy is enabled.
     */
    private function initializeTenancy(): void
    {
        if (! $this->tenantId || ! config('notify.tenancy.enabled', false)) {
            return;
        }

        if (function_exists('tenancy')) {
            tenancy()->initialize($this->tenantId);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        $this->scheduledNotification->markAsFailed($exception->getMessage());

        Log::error('Scheduled notification job failed permanently', [
            'scheduled_id' => $this->scheduledNotification->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
