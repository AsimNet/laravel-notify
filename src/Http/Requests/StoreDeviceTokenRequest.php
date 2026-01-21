<?php

namespace Asimnet\Notify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for registering a new device token.
 */
class StoreDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by middleware
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => [
                'required',
                'string',
                'min:100',  // FCM tokens are typically 150+ characters
                'max:500',
                Rule::unique(config('notify.tables.device_tokens', 'notify_device_tokens'), 'token')
                    ->where(function ($query) {
                        // Scope uniqueness to tenant if multi-tenant
                        $tenantId = $this->getTenantId();
                        if ($tenantId) {
                            $query->where('tenant_id', $tenantId);
                        } else {
                            $query->whereNull('tenant_id');
                        }
                    }),
            ],
            'platform' => [
                'required',
                'string',
                Rule::in(['ios', 'android', 'web']),
            ],
            'device_name' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => __('notify::notify.validation.token_required'),
            'token.min' => __('notify::notify.validation.token_invalid'),
            'token.max' => __('notify::notify.validation.token_invalid'),
            'token.unique' => __('notify::notify.validation.token_already_registered'),
            'platform.required' => __('notify::notify.validation.platform_required'),
            'platform.in' => __('notify::notify.validation.platform_invalid'),
            'device_name.max' => __('notify::notify.validation.device_name_too_long'),
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => __('notify::notify.device_token'),
            'platform' => __('notify::notify.platform'),
            'device_name' => __('notify::notify.device_name'),
        ];
    }

    /**
     * Get the current tenant ID if available.
     */
    protected function getTenantId(): ?string
    {
        if (! config('notify.tenancy.enabled', false)) {
            return null;
        }

        try {
            if (function_exists('tenant') && tenant()) {
                return tenant()->getTenantKey();
            }
        } catch (\Exception $e) {
            // Tenant context not available
        }

        return null;
    }
}
