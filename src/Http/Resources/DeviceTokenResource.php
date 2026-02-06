<?php

namespace Asimnet\Notify\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for device tokens.
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
            'token' => $this->token,
            'device_name' => $this->device_name,
            'platform' => $this->platform,
            'platform_label' => $this->getPlatformLabel(),
            'last_active_at' => $this->last_active_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
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
