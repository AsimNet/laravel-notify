<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('notify.tables.topic_subscriptions', 'notify_topic_subscriptions'), function (Blueprint $table) {
            $table->boolean('fcm_enabled')->default(true)->after('fcm_synced');
            $table->boolean('sms_enabled')->default(false)->after('fcm_enabled');
            $table->boolean('wba_enabled')->default(false)->after('sms_enabled');
        });
    }

    public function down(): void
    {
        Schema::table(config('notify.tables.topic_subscriptions', 'notify_topic_subscriptions'), function (Blueprint $table) {
            $table->dropColumn(['fcm_enabled', 'sms_enabled', 'wba_enabled']);
        });
    }
};
