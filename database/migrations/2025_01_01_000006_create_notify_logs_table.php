<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notification logs table for tracking delivery status.
 *
 * Note: Logs older than config('notify.logging.retention_days') days
 * should be auto-deleted via scheduled cleanup command.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('notify.tables.logs', 'notify_logs'), function (Blueprint $table) {
            $table->id();

            // Nullable tenant_id for optional multi-tenancy
            $table->string('tenant_id')->nullable()->index();

            // Campaign relationship (nullable for direct sends)
            $table->foreignId('campaign_id')
                ->nullable()
                ->constrained(config('notify.tables.campaigns', 'notify_campaigns'))
                ->nullOnDelete();

            // User relationship (nullable if user is deleted)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Store device token ID as string (not FK) in case token is deleted
            $table->string('device_token_id')->nullable()->index();

            // Channel: 'fcm', 'sms', 'telegram', 'whatsapp', 'discord', etc.
            $table->string('channel', 50);

            // Status: 'pending', 'sent', 'delivered', 'opened', 'failed'
            $table->string('status', 50)->default('pending');

            // Notification content snapshot
            $table->text('title')->nullable();
            $table->text('body')->nullable();
            $table->json('payload')->nullable(); // Stored if config('notify.logging.store_payload') is true

            // External reference (FCM message ID, SMS provider ID, etc.)
            $table->string('external_id', 255)->nullable();

            // Error tracking
            $table->text('error_message')->nullable();
            $table->text('error_code')->nullable();

            // Delivery timestamps
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();

            // Flag for test notifications
            $table->boolean('is_test')->default(false);

            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'channel']);
            $table->index('campaign_id');
            $table->index('user_id');
            $table->index('created_at'); // For log retention cleanup
            $table->index('is_test');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('notify.tables.logs', 'notify_logs'));
    }
};
