<?php

namespace Asimnet\Notify\Http\Controllers;

use Asimnet\Notify\Events\DeviceTokenDeleted;
use Asimnet\Notify\Events\DeviceTokenRegistered;
use Asimnet\Notify\Http\Requests\StoreDeviceTokenRequest;
use Asimnet\Notify\Http\Requests\UpdateDeviceTokenRequest;
use Asimnet\Notify\Http\Resources\DeviceTokenResource;
use Asimnet\Notify\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/**
 * REST controller for managing FCM device tokens.
 *
 * Handles device registration, listing, updating, and deletion
 * with proper authorization and event dispatching for FCM sync.
 */
class DeviceTokenController extends Controller
{
    /**
     * List user's registered devices.
     *
     * GET /api/notify/devices
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $devices = DeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return DeviceTokenResource::collection($devices);
    }

    /**
     * Register a new device token.
     *
     * POST /api/notify/devices
     */
    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        $user = $request->user();

        // Check if this is user's first device
        $isFirstDevice = ! DeviceToken::query()
            ->where('user_id', $user->id)
            ->exists();

        // Get tenant ID if multi-tenant
        $tenantId = $this->getTenantId();

        $device = DeviceToken::create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'token' => $request->validated('token'),
            'platform' => $request->validated('platform'),
            'device_name' => $request->validated('device_name'),
            'last_active_at' => now(),
        ]);

        // Dispatch event for default topic subscription
        DeviceTokenRegistered::dispatch($device, $isFirstDevice);

        return response()->json([
            'success' => true,
            'message' => __('notify::notify.api.device_registered'),
            'data' => new DeviceTokenResource($device),
        ], 201);
    }

    /**
     * Update device token (e.g., rename device or refresh token).
     *
     * PUT /api/notify/devices/{device}
     */
    public function update(UpdateDeviceTokenRequest $request, DeviceToken $device): JsonResponse
    {
        // Ensure user owns this device
        if ($device->user_id !== $request->user()->id) {
            abort(403, __('notify::notify.error_unauthorized'));
        }

        $device->update($request->validated());

        // Touch last_active_at
        $device->touchLastActive();

        return response()->json([
            'success' => true,
            'message' => __('notify::notify.api.device_updated'),
            'data' => new DeviceTokenResource($device),
        ]);
    }

    /**
     * Delete device token.
     *
     * DELETE /api/notify/devices/{device}
     */
    public function destroy(Request $request, DeviceToken $device): JsonResponse
    {
        // Ensure user owns this device
        if ($device->user_id !== $request->user()->id) {
            abort(403, __('notify::notify.error_unauthorized'));
        }

        // Store token and tenant info before deletion for FCM cleanup
        $tokenValue = $device->token;
        $tenantId = $device->tenant_id;
        $userId = $device->user_id;

        $device->delete();

        // Dispatch event for FCM cleanup
        DeviceTokenDeleted::dispatch($userId, $tokenValue, $tenantId);

        return response()->json([
            'success' => true,
            'message' => __('notify::notify.api.device_deleted'),
        ]);
    }

    /**
     * Get current tenant ID if multi-tenancy is enabled.
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
