<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('notify.tables.topic_subscriptions', 'notify_topic_subscriptions'), function (Blueprint $table) {
            $table->id();

            // Nullable tenant_id for optional multi-tenancy
            $table->string('tenant_id')->nullable()->index();

            // Topic relationship
            $table->foreignId('topic_id')
                ->constrained(config('notify.tables.topics', 'notify_topics'))
                ->cascadeOnDelete();

            // User relationship
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Track if subscription is synced to FCM topic
            $table->boolean('fcm_synced')->default(false);

            $table->timestamps();

            // Prevent duplicate subscriptions per tenant
            $table->unique(['tenant_id', 'topic_id', 'user_id']);

            // Index for finding unsynced subscriptions
            $table->index('fcm_synced');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('notify.tables.topic_subscriptions', 'notify_topic_subscriptions'));
    }
};
