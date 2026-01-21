<?php

namespace Asimnet\Notify\Services;

use Asimnet\Notify\Enums\ComparisonOperator;
use Asimnet\Notify\Enums\FieldType;
use DateTime;
use Illuminate\Database\Eloquent\Builder;

/**
 * Resolves segment conditions to Eloquent queries.
 *
 * Converts JSON condition structure to database WHERE clauses.
 *
 * يحول شروط الشريحة إلى استعلامات Eloquent.
 * يحول بنية الشروط JSON إلى عبارات WHERE في قاعدة البيانات.
 */
class SegmentResolver
{
    /**
     * The conditions to resolve.
     *
     * @var array<string, mixed>
     */
    protected array $conditions;

    /**
     * The User model class.
     *
     * @var class-string
     */
    protected string $userModel;

    /**
     * Create a new SegmentResolver instance.
     *
     * @param  array<string, mixed>  $conditions  The JSON conditions array
     */
    public function __construct(array $conditions)
    {
        $this->conditions = $conditions;
        $this->userModel = config('auth.providers.users.model', 'App\\Models\\User');
    }

    /**
     * Get the Eloquent query builder for matching users.
     *
     * الحصول على منشئ استعلام Eloquent للمستخدمين المطابقين.
     */
    public function getQuery(): Builder
    {
        $query = $this->userModel::query();

        if (! empty($this->conditions) && ! empty($this->conditions['conditions'])) {
            $query->where(function (Builder $q) {
                $this->applyConditionGroup($q, $this->conditions);
            });
        }

        return $query;
    }

    /**
     * Get user IDs matching the segment conditions.
     *
     * الحصول على معرفات المستخدمين المطابقين لشروط الشريحة.
     *
     * @return array<int>
     */
    public function getUserIds(): array
    {
        return $this->getQuery()->pluck('id')->toArray();
    }

    /**
     * Get count of users matching the segment conditions.
     *
     * الحصول على عدد المستخدمين المطابقين لشروط الشريحة.
     */
    public function getCount(): int
    {
        return $this->getQuery()->count();
    }

    /**
     * Apply a condition group (AND/OR) to the query.
     *
     * تطبيق مجموعة شروط (AND/OR) على الاستعلام.
     *
     * @param  array<string, mixed>  $group
     */
    protected function applyConditionGroup(Builder $query, array $group): void
    {
        if (empty($group['conditions']) || empty($group['operator'])) {
            return;
        }

        $operator = strtolower($group['operator']); // 'and' or 'or'

        foreach ($group['conditions'] as $index => $condition) {
            // First condition always uses 'and', subsequent use group operator
            $boolean = $index === 0 ? 'and' : $operator;

            if (isset($condition['conditions'])) {
                // Nested group - recurse
                $query->where(function (Builder $q) use ($condition) {
                    $this->applyConditionGroup($q, $condition);
                }, boolean: $boolean);
            } else {
                // Single condition
                $this->applyCondition($query, $condition, $boolean);
            }
        }
    }

    /**
     * Apply a single condition to the query.
     *
     * تطبيق شرط واحد على الاستعلام.
     *
     * @param  array<string, mixed>  $condition
     */
    protected function applyCondition(Builder $query, array $condition, string $boolean): void
    {
        $field = $condition['field'] ?? null;
        $filterType = $condition['filterType'] ?? 'text';
        $type = $condition['type'] ?? 'equals';
        $value = $condition['filter'] ?? $condition['value'] ?? null;

        if (! $field) {
            return;
        }

        $fieldType = FieldType::tryFrom($filterType) ?? FieldType::Text;

        match ($fieldType) {
            FieldType::Text => $this->applyTextCondition($query, $field, $type, $value, $boolean),
            FieldType::Number => $this->applyNumberCondition($query, $field, $type, $value, $condition, $boolean),
            FieldType::Date => $this->applyDateCondition($query, $field, $type, $value, $condition, $boolean),
            FieldType::Set => $this->applySetCondition($query, $field, $condition, $boolean),
        };
    }

    /**
     * Apply a text field condition.
     *
     * تطبيق شرط حقل نصي.
     */
    protected function applyTextCondition(Builder $query, string $field, string $type, mixed $value, string $boolean): void
    {
        $operator = ComparisonOperator::tryFrom($type) ?? ComparisonOperator::Equals;

        match ($operator) {
            ComparisonOperator::Equals => $query->where($field, '=', $value, boolean: $boolean),
            ComparisonOperator::NotEqual => $query->where($field, '!=', $value, boolean: $boolean),
            ComparisonOperator::Contains => $query->where($field, 'ilike', '%'.$value.'%', boolean: $boolean),
            ComparisonOperator::NotContains => $query->where($field, 'not ilike', '%'.$value.'%', boolean: $boolean),
            ComparisonOperator::StartsWith => $query->where($field, 'ilike', $value.'%', boolean: $boolean),
            ComparisonOperator::EndsWith => $query->where($field, 'ilike', '%'.$value, boolean: $boolean),
            ComparisonOperator::Blank => $query->whereNull($field, boolean: $boolean),
            ComparisonOperator::NotBlank => $query->whereNotNull($field, boolean: $boolean),
            default => $query->where($field, '=', $value, boolean: $boolean),
        };
    }

    /**
     * Apply a number field condition.
     *
     * تطبيق شرط حقل رقمي.
     *
     * @param  array<string, mixed>  $condition  Full condition for InRange
     */
    protected function applyNumberCondition(Builder $query, string $field, string $type, mixed $value, array $condition, string $boolean): void
    {
        $operator = ComparisonOperator::tryFrom($type) ?? ComparisonOperator::Equals;

        match ($operator) {
            ComparisonOperator::Equals => $query->where($field, '=', $value, boolean: $boolean),
            ComparisonOperator::NotEqual => $query->where($field, '!=', $value, boolean: $boolean),
            ComparisonOperator::GreaterThan => $query->where($field, '>', $value, boolean: $boolean),
            ComparisonOperator::GreaterThanOrEqual => $query->where($field, '>=', $value, boolean: $boolean),
            ComparisonOperator::LessThan => $query->where($field, '<', $value, boolean: $boolean),
            ComparisonOperator::LessThanOrEqual => $query->where($field, '<=', $value, boolean: $boolean),
            ComparisonOperator::InRange => $query->where(function (Builder $q) use ($field, $value, $condition) {
                $q->where($field, '>=', $value)
                    ->where($field, '<=', $condition['filterTo'] ?? $value);
            }, boolean: $boolean),
            ComparisonOperator::Blank => $query->whereNull($field, boolean: $boolean),
            ComparisonOperator::NotBlank => $query->whereNotNull($field, boolean: $boolean),
            default => $query->where($field, '=', $value, boolean: $boolean),
        };
    }

    /**
     * Apply a date field condition.
     *
     * تطبيق شرط حقل تاريخ.
     *
     * @param  array<string, mixed>  $condition  Full condition for InRange
     */
    protected function applyDateCondition(Builder $query, string $field, string $type, mixed $value, array $condition, string $boolean): void
    {
        $dateFrom = null;
        $dateTo = null;

        if ($value) {
            $dateFrom = $value instanceof DateTime ? $value : new DateTime($value);
        }

        if (isset($condition['filterTo'])) {
            $dateTo = $condition['filterTo'] instanceof DateTime
                ? $condition['filterTo']
                : new DateTime($condition['filterTo']);
        }

        $operator = ComparisonOperator::tryFrom($type) ?? ComparisonOperator::Equals;

        match ($operator) {
            ComparisonOperator::Equals => $query->whereDate($field, '=', $dateFrom, boolean: $boolean),
            ComparisonOperator::NotEqual => $query->whereDate($field, '!=', $dateFrom, boolean: $boolean),
            ComparisonOperator::GreaterThan, ComparisonOperator::GreaterThanOrEqual => $query->whereDate($field, '>=', $dateFrom, boolean: $boolean),
            ComparisonOperator::LessThan, ComparisonOperator::LessThanOrEqual => $query->whereDate($field, '<=', $dateFrom, boolean: $boolean),
            ComparisonOperator::InRange => $query->where(function (Builder $q) use ($field, $dateFrom, $dateTo) {
                $q->whereDate($field, '>=', $dateFrom)
                    ->whereDate($field, '<=', $dateTo ?? $dateFrom);
            }, boolean: $boolean),
            ComparisonOperator::Blank => $query->whereNull($field, boolean: $boolean),
            ComparisonOperator::NotBlank => $query->whereNotNull($field, boolean: $boolean),
            default => $query->whereDate($field, '=', $dateFrom, boolean: $boolean),
        };
    }

    /**
     * Apply a set/select field condition (whereIn).
     *
     * تطبيق شرط حقل المجموعة (whereIn).
     *
     * @param  array<string, mixed>  $condition
     */
    protected function applySetCondition(Builder $query, string $field, array $condition, string $boolean): void
    {
        $values = $condition['values'] ?? [];

        if (empty($values)) {
            return;
        }

        $filteredValues = array_filter($values, fn ($v) => $v !== null && $v !== '');

        if (! empty($filteredValues)) {
            $query->where(function (Builder $q) use ($field, $values, $filteredValues) {
                // If original values contained null, also match NULL
                if (count($filteredValues) !== count($values)) {
                    $q->whereNull($field);
                }

                $q->orWhereIn($field, $filteredValues);
            }, boolean: $boolean);
        }
    }
}
