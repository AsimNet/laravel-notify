<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('notify.tables.campaigns', 'notify_campaigns'), function (Blueprint $table) {
            $table->id();

            // Nullable tenant_id for optional multi-tenancy
            $table->string('tenant_id')->nullable()->index();

            // Campaign identification
            $table->string('name');

            // Campaign type: 'direct', 'topic', 'broadcast', 'segment'
            $table->string('type', 50);

            // Status: 'draft', 'scheduled', 'sending', 'sent', 'failed', 'cancelled'
            $table->string('status', 50)->default('draft');

            // Notification content
            $table->text('title');
            $table->text('body');
            $table->string('image_url', 500)->nullable();
            $table->string('action_url', 500)->nullable();
            $table->json('payload')->nullable();

            // Template reference
            // مرجع القالب
            $table->foreignId('template_id')
                ->nullable()
                ->constrained(config('notify.tables.templates', 'notify_templates'))
                ->nullOnDelete();

            // Segment reference (no FK constraint since segments table created later)
            // مرجع الشريحة (بدون قيد مفتاح أجنبي لأن جدول الشرائح ينشأ لاحقاً)
            $table->unsignedBigInteger('segment_id')->nullable()->index();

            // Channels to send through: ['fcm', 'sms', 'whatsapp', etc.]
            // القنوات للإرسال من خلالها
            $table->json('channels');

            // Segment targeting query (for segment type campaigns)
            // استعلام استهداف الشريحة (لحملات نوع الشريحة)
            $table->json('recipient_query')->nullable();

            // Statistics
            $table->unsignedBigInteger('recipient_count')->default(0);
            $table->unsignedBigInteger('sent_count')->default(0);
            $table->unsignedBigInteger('delivered_count')->default(0);
            $table->unsignedBigInteger('failed_count')->default(0);

            // Scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['tenant_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('notify.tables.campaigns', 'notify_campaigns'));
    }
};
