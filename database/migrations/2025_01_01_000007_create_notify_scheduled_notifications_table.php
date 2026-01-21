<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scheduled notifications table for database-driven notification scheduling.
 *
 * جدول الإشعارات المجدولة للجدولة المدفوعة بقاعدة البيانات.
 *
 * Enables future notification scheduling with support for:
 * - Direct content (title/body) or template-based rendering
 * - Cancellation tracking with reason and user
 * - Status determination via timestamp columns
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('notify.tables.scheduled_notifications', 'notify_scheduled_notifications'), function (Blueprint $table) {
            $table->id();

            // Nullable tenant_id for optional multi-tenancy
            $table->string('tenant_id')->nullable()->index();

            // User relationship (recipient)
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Notification channel: 'fcm', 'sms', etc.
            $table->string('channel', 50)->default('fcm');

            // Direct notification content (used when template is not set)
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('action_url', 500)->nullable();
            $table->json('payload')->nullable(); // Custom data payload

            // Template-based content
            $table->foreignId('template_id')
                ->nullable()
                ->constrained(config('notify.tables.templates', 'notify_templates'))
                ->nullOnDelete();
            $table->json('template_variables')->nullable(); // Variables for template rendering

            // Scheduling timestamp
            $table->timestamp('scheduled_at')->index();

            // Status tracking via timestamps (no status column - computed from these)
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('cancellation_reason')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();

            // Flag for test notifications
            $table->boolean('is_test')->default(false);

            $table->timestamps();

            // Composite indexes for efficient queries
            $table->index(['tenant_id', 'scheduled_at']); // Tenant-scoped due queries
            $table->index(['user_id', 'scheduled_at']); // User notification history
            $table->index(['sent_at', 'cancelled_at', 'failed_at']); // Due scope efficiency
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('notify.tables.scheduled_notifications', 'notify_scheduled_notifications'));
    }
};
