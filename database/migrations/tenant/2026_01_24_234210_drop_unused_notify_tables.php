<?php

use Illuminate\Database\Migrations\Migration;
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
        // Drop segments (no dependencies)
        Schema::dropIfExists('notify_segments');

        // Drop campaigns
        Schema::dropIfExists('notify_campaigns');

        // Drop templates
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
