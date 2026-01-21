<?php

namespace Asimnet\Notify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for updating an existing device token.
 */
class UpdateDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by middleware and controller
        return true;
    }

    public function rules(): array
    {
        $deviceId = $this->route('device')?->id ?? $this->route('device');

        return [
            'token' => [
                'sometimes',
                'string',
                'min:100',
                'max:500',
                Rule::unique(config('notify.tables.device_tokens', 'notify_device_tokens'), 'token')
                    ->ignore($deviceId)
                    ->where(function ($query) {
                        $tenantId = $this->getTenantId();
                        if ($tenantId) {
                            $query->where('tenant_id', $tenantId);
                        } else {
                            $query->whereNull('tenant_id');
                        }
                    }),
            ],
            'device_name' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'token.min' => __('notify::notify.validation.token_invalid'),
            'token.max' => __('notify::notify.validation.token_invalid'),
            'token.unique' => __('notify::notify.validation.token_already_registered'),
            'device_name.max' => __('notify::notify.validation.device_name_too_long'),
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => __('notify::notify.device_token'),
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
