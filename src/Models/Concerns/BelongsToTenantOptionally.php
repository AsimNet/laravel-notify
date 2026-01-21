<?php

namespace Asimnet\Notify\Models\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait BelongsToTenantOptionally
 *
 * Provides optional tenant scoping for Notify package models.
 * When tenancy is enabled, queries are automatically scoped to the current tenant.
 * When tenancy is disabled, models work without any tenant filtering.
 *
 * @method static Builder withoutTenancy()
 * @method static Builder forTenant(?string $tenantId)
 */
trait BelongsToTenantOptionally
{
    /**
     * Boot the trait.
     *
     * Adds tenant scoping when tenancy is enabled.
     */
    public static function bootBelongsToTenantOptionally(): void
    {
        // Return early if tenancy is not enabled
        if (! config('notify.tenancy.enabled', false)) {
            return;
        }

        // Add global scope to filter by tenant when tenant context exists
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = (new static)->getCurrentTenantId();

            if ($tenantId !== null) {
                $builder->where(
                    $builder->getModel()->getTable().'.'.(new static)->getTenantIdColumn(),
                    $tenantId
                );
            }
        });

        // Auto-set tenant_id when creating records
        static::creating(function ($model) {
            $tenantIdColumn = $model->getTenantIdColumn();

            // Only set if not already set and tenant context exists
            if ($model->{$tenantIdColumn} === null) {
                $tenantId = $model->getCurrentTenantId();

                if ($tenantId !== null) {
                    $model->{$tenantIdColumn} = $tenantId;
                }
            }
        });
    }

    /**
     * Get the tenant ID column name.
     */
    public function getTenantIdColumn(): string
    {
        return 'tenant_id';
    }

    /**
     * Get the current tenant ID if available.
     *
     * Checks if Stancl/Tenancy is installed and a tenant context exists.
     */
    public function getCurrentTenantId(): ?string
    {
        // Check if tenancy is enabled in config
        if (! config('notify.tenancy.enabled', false)) {
            return null;
        }

        try {
            // Check if Stancl/Tenancy's tenant() function exists and returns a tenant
            if (function_exists('tenant') && tenant()) {
                return tenant()->getTenantKey();
            }
        } catch (Exception $e) {
            // Tenant context not available, return null silently
        }

        return null;
    }

    /**
     * Query without tenant scoping.
     *
     * Use this when you need to query across all tenants.
     */
    public static function withoutTenancy(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }

    /**
     * Scope query to a specific tenant.
     *
     * Use this for explicit tenant filtering when needed.
     */
    public function scopeForTenant(Builder $query, ?string $tenantId): Builder
    {
        if ($tenantId === null) {
            return $query;
        }

        return $query->where($this->getTenantIdColumn(), $tenantId);
    }
}
