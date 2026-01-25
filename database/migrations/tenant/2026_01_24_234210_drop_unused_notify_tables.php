<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        // Drop campaign_id FK from notify_logs if it exists
        if (Schema::hasTable('notify_logs') && Schema::hasColumn('notify_logs', 'campaign_id')) {
            Schema::table('notify_logs', function (Blueprint $table) {
                $table->dropForeign(['campaign_id']);
                $table->dropColumn('campaign_id');
            });
        }

        // Drop template_id FK from notify_scheduled_notifications if it exists
        if (Schema::hasTable('notify_scheduled_notifications') && Schema::hasColumn('notify_scheduled_notifications', 'template_id')) {
            // Check if it's a foreign key (bigint) vs string
            try {
                Schema::table('notify_scheduled_notifications', function (Blueprint $table) {
                    $table->dropForeign(['template_id']);
                });
            } catch (\Exception $e) {
                // FK might not exist if column is string type
            }
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
