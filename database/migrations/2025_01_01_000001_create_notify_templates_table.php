<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('notify.tables.templates', 'notify_templates'), function (Blueprint $table) {
            $table->id();

            // Nullable tenant_id for optional multi-tenancy
            $table->string('tenant_id')->nullable()->index();

            // Template identification
            $table->string('name');
            $table->string('slug');

            // Template content with variable placeholders like {user.name}
            $table->text('title');
            $table->text('body');
            $table->string('image_url', 500)->nullable();

            // Variable definitions for this template
            $table->json('variables')->nullable();

            // Template status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes for common queries
            $table->index(['tenant_id', 'is_active']);
            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('notify.tables.templates', 'notify_templates'));
    }
};
