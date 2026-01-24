<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('notify.tables.device_tokens', 'notify_device_tokens'), function (Blueprint $table) {
            $table->id();

            // Nullable tenant_id for optional multi-tenancy
            $table->string('tenant_id')->nullable()->index();

            // User relationship
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // FCM token (can be long)
            $table->string('token', 500);

            // Platform: 'ios', 'android', 'web'
            $table->string('platform', 20);

            // Optional device name for user identification (e.g., "iPhone-Mohamed")
            $table->string('device_name')->nullable();

            // Track last activity for token cleanup
            $table->timestamp('last_active_at')->nullable();

            $table->timestamps();

            // Prevent duplicate tokens per tenant
            $table->unique(['tenant_id', 'token']);

            // Common query indexes
            $table->index(['user_id', 'platform']);
            $table->index('last_active_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('notify.tables.device_tokens', 'notify_device_tokens'));
    }
};
