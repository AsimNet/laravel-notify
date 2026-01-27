<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop unused notify tables.
 *
 * حذف جداول الإشعارات غير المستخدمة.
 *
 * These tables were part of the original notify package but the features
 * (templates, campaigns, segments) have been removed.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, drop foreign keys that reference tables we're about to drop
        // أولاً، حذف المفاتيح الأجنبية التي تشير إلى الجداول التي سنحذفها

        // Drop campaign_id FK and column from notify_logs if it exists
        // Use raw SQL with IF EXISTS to avoid PostgreSQL transaction abort
        if (Schema::hasTable('notify_logs') && Schema::hasColumn('notify_logs', 'campaign_id')) {
            DB::statement('ALTER TABLE notify_logs DROP CONSTRAINT IF EXISTS notify_logs_campaign_id_foreign');
            Schema::table('notify_logs', function (Blueprint $table) {
                $table->dropColumn('campaign_id');
            });
        }

        // Drop template_id FK from notify_scheduled_notifications if it exists
        // Use raw SQL with IF EXISTS - won't fail if FK doesn't exist (new tenants have string column)
        if (Schema::hasTable('notify_scheduled_notifications')) {
            DB::statement('ALTER TABLE notify_scheduled_notifications DROP CONSTRAINT IF EXISTS notify_scheduled_notifications_template_id_foreign');
        }

        // Now safe to drop the tables
        // الآن يمكن حذف الجداول بأمان
        Schema::dropIfExists('notify_segments');
        Schema::dropIfExists('notify_campaigns');
        Schema::dropIfExists('notify_templates');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // These tables are intentionally not recreated
        // The features have been permanently removed from the package
    }
};
