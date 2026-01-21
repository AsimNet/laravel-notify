<?php

namespace Asimnet\Notify\Console\Commands;

use Asimnet\Notify\Jobs\SendScheduledNotification;
use Asimnet\Notify\Models\ScheduledNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Process scheduled notifications that are due for sending.
 *
 * This command is designed to run every minute via Laravel scheduler.
 * It queries for due notifications within a tolerance window and
 * dispatches jobs to handle the actual sending asynchronously.
 *
 * معالجة الإشعارات المجدولة المستحقة للإرسال.
 *
 * تم تصميم هذا الأمر ليعمل كل دقيقة عبر مجدول Laravel.
 * يستعلم عن الإشعارات المستحقة ضمن نافذة التسامح
 * ويرسل الوظائف للتعامل مع الإرسال الفعلي بشكل غير متزامن.
 */
class ProcessScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:process-scheduled
                            {--limit=100 : Maximum notifications to process per run / الحد الأقصى للإشعارات لمعالجتها في كل تشغيل}
                            {--tolerance=24 : Hours of tolerance for backlogged notifications / ساعات التسامح للإشعارات المتأخرة}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled notifications that are due for sending / معالجة الإشعارات المجدولة المستحقة للإرسال';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $toleranceHours = (int) $this->option('tolerance');

        // Query for due notifications within tolerance window
        // استعلام عن الإشعارات المستحقة ضمن نافذة التسامح
        $notifications = ScheduledNotification::due()
            ->where('scheduled_at', '>=', now()->subHours($toleranceHours))
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        if ($notifications->isEmpty()) {
            $this->info(__('notify::notify.no_scheduled_notifications'));

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($notifications as $scheduled) {
            // Get tenant_id from the scheduled notification
            $tenantId = $scheduled->tenant_id;

            // Dispatch the job to process this notification
            // إرسال الوظيفة لمعالجة هذا الإشعار
            SendScheduledNotification::dispatch($scheduled, $tenantId);

            $count++;
        }

        $message = "Dispatched {$count} scheduled notifications for processing.";
        $this->info($message);

        Log::info('Notify: Dispatched scheduled notifications', ['count' => $count]);

        return self::SUCCESS;
    }
}
