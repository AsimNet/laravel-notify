<?php

namespace Asimnet\Notify\Models;

use Asimnet\Notify\Models\Concerns\BelongsToTenantOptionally;
use Asimnet\Notify\Services\SegmentResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Segment model for audience targeting with condition-based filtering.
 *
 * نموذج الشريحة لاستهداف الجمهور مع التصفية المبنية على الشروط.
 *
 * Stores reusable audience segments that can filter users based on
 * nested AND/OR conditions using AG Grid filter format.
 *
 * يخزن شرائح الجمهور القابلة لإعادة الاستخدام والتي يمكنها تصفية
 * المستخدمين بناءً على شروط AND/OR المتداخلة بتنسيق فلتر AG Grid.
 */
class Segment extends Model
{
    use BelongsToTenantOptionally;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'conditions',
        'is_active',
        'cached_count',
        'cached_at',
    ];

    /**
     * Get the casts for the model attributes.
     *
     * الحصول على التحويلات لسمات النموذج.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'is_active' => 'boolean',
            'cached_at' => 'datetime',
        ];
    }

    /**
     * Get the table associated with the model.
     *
     * الحصول على الجدول المرتبط بالنموذج.
     */
    public function getTable(): string
    {
        return config('notify.tables.segments', 'notify_segments');
    }

    /**
     * Boot the model.
     *
     * Auto-generates slug from name if not provided.
     *
     * يولد المعرف تلقائياً من الاسم إذا لم يتم توفيره.
     */
    protected static function booted(): void
    {
        static::creating(function (Segment $segment) {
            if (empty($segment->slug)) {
                $segment->slug = Str::slug($segment->name);
            }
        });
    }

    /**
     * Resolve user IDs matching this segment's conditions.
     *
     * حل معرفات المستخدمين المطابقين لشروط هذه الشريحة.
     *
     * @return array<int>
     */
    public function resolveUserIds(): array
    {
        return (new SegmentResolver($this->conditions ?? []))->getUserIds();
    }

    /**
     * Get the count of users matching this segment's conditions.
     *
     * الحصول على عدد المستخدمين المطابقين لشروط هذه الشريحة.
     */
    public function getUserCount(): int
    {
        return (new SegmentResolver($this->conditions ?? []))->getCount();
    }

    /**
     * Refresh the cached user count for this segment.
     *
     * تحديث عدد المستخدمين المخزن مؤقتاً لهذه الشريحة.
     *
     * Updates cached_count and cached_at, returns the new count.
     * يحدث cached_count و cached_at، ويعيد العدد الجديد.
     */
    public function refreshCachedCount(): int
    {
        $count = $this->getUserCount();

        $this->update([
            'cached_count' => $count,
            'cached_at' => now(),
        ]);

        return $count;
    }

    /**
     * Scope to only active segments.
     *
     * نطاق للشرائح النشطة فقط.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to find by slug.
     *
     * نطاق للبحث بواسطة المعرف.
     */
    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    /**
     * Check if the segment has any conditions defined.
     *
     * التحقق مما إذا كانت الشريحة لديها شروط محددة.
     */
    public function hasConditions(): bool
    {
        if (empty($this->conditions)) {
            return false;
        }

        // Check if conditions array has nested conditions
        return ! empty($this->conditions['conditions'] ?? []);
    }

    /**
     * Get the default empty conditions structure.
     *
     * الحصول على هيكل الشروط الافتراضي الفارغ.
     *
     * @return array{operator: string, conditions: array}
     */
    public static function getEmptyConditions(): array
    {
        return [
            'operator' => 'and',
            'conditions' => [],
        ];
    }

    protected static function newFactory()
    {
        return \Asimnet\Notify\Database\Factories\SegmentFactory::new();
    }
}
