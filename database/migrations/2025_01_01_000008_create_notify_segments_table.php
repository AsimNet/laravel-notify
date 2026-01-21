<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('notify.tables.segments', 'notify_segments'), function (Blueprint $table) {
            $table->id();

            // Nullable tenant_id for optional multi-tenancy
            // معرف المستأجر الاختياري لدعم تعدد المستأجرين
            $table->string('tenant_id')->nullable()->index();

            // Segment identification
            // تعريف الشريحة
            $table->string('name');
            $table->string('slug')->index();

            // Human-readable description for admins
            // وصف للمسؤولين
            $table->text('description')->nullable();

            // Nested AND/OR query conditions (AG Grid filter format)
            // شروط الاستعلام المتداخلة (تنسيق فلتر AG Grid)
            $table->jsonb('conditions');

            // Soft enable/disable
            // تفعيل/تعطيل مرن
            $table->boolean('is_active')->default(true)->index();

            // Cached user count for preview
            // عدد المستخدمين المخزن مؤقتاً للمعاينة
            $table->unsignedInteger('cached_count')->nullable();
            $table->timestamp('cached_at')->nullable();

            $table->timestamps();

            // Segment slugs must be unique per tenant
            // معرفات الشرائح يجب أن تكون فريدة لكل مستأجر
            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('notify.tables.segments', 'notify_segments'));
    }
};
