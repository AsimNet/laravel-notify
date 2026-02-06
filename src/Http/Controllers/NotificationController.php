<?php

namespace Asimnet\Notify\Http\Controllers;

use Asimnet\Notify\Http\Resources\NotificationLogResource;
use Asimnet\Notify\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/**
 * REST controller for user-facing notification history.
 *
 * Provides paginated notification logs and mark-as-read functionality.
 */
class NotificationController extends Controller
{
    /**
     * List notifications for the authenticated user.
     *
     * GET /api/notify/notifications
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = NotificationLog::query()
            ->forUser($request->user()->id)
            ->notTest()
            ->latest('created_at')
            ->paginate($request->integer('per_page', 20));

        return NotificationLogResource::collection($notifications);
    }

    /**
     * Mark a notification as read (set opened_at timestamp).
     *
     * POST /api/notify/notifications/{notification}/read
     */
    public function markAsRead(Request $request, int $notification): JsonResponse
    {
        $log = NotificationLog::query()
            ->forUser($request->user()->id)
            ->findOrFail($notification);

        if (! $log->opened_at) {
            $log->markAsOpened();
        }

        return response()->json([
            'success' => true,
            'message' => __('notify::notify.api.notification_read'),
            'data' => new NotificationLogResource($log->fresh()),
        ]);
    }
}
