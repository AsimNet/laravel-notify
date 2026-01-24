<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('notify.tables.topics', 'notify_topics'), function (Blueprint $table) {
            $table->id();

            // Nullable tenant_id for optional multi-tenancy
            $table->string('tenant_id')->nullable()->index();

            // Topic identification
            $table->string('name');
            $table->string('slug');

            // Topic description
            $table->text('description')->nullable();

            // Can users self-subscribe to this topic?
            $table->boolean('is_public')->default(false);

            // Should new users be auto-subscribed to this topic?
            $table->boolean('is_default')->default(false);

            // Cached subscriber count for performance
            $table->unsignedBigInteger('subscriber_count')->default(0);

            $table->timestamps();

            // Ensure unique slugs per tenant
            $table->unique(['tenant_id', 'slug']);

            // Index for listing public topics
            $table->index(['tenant_id', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('notify.tables.topics', 'notify_topics'));
    }
};
