<?php

namespace Asimnet\Notify\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for device tokens.
 *
 * SECURITY: Never exposes the actual FCM token in responses.
 * Only returns device metadata for user management.
 */
class DeviceTokenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_name' => $this->device_name,
            'platform' => $this->platform,
            'platform_label' => $this->getPlatformLabel(),
            'last_active_at' => $this->last_active_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            // Note: token is intentionally NOT exposed for security
        ];
    }

    /**
     * Get human-readable platform label.
     */
    protected function getPlatformLabel(): string
    {
        return match ($this->platform) {
            'ios' => __('notify::notify.platform_ios'),
            'android' => __('notify::notify.platform_android'),
            'web' => __('notify::notify.platform_web'),
            default => $this->platform,
        };
    }
}
