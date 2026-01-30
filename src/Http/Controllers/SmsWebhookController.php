<?php

namespace Asimnet\Notify\Http\Controllers;

use Asimnet\Notify\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Generic SMS delivery webhook endpoint.
 *
 * Providers can POST:
 * - external_id: provider message id (required)
 * - status: sent|delivered|failed (optional)
 * - error: error message (optional)
 * - delivered_at: ISO datetime (optional)
 */
class SmsWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'external_id' => ['required', 'string'],
            'status' => ['nullable', 'string'],
            'error' => ['nullable', 'string'],
            'delivered_at' => ['nullable', 'date'],
        ]);

        $log = NotificationLog::query()
            ->where('channel', 'sms')
            ->where('external_id', $data['external_id'])
            ->first();

        if (! $log) {
            return response()->json(['success' => false, 'message' => 'Log not found'], 404);
        }

        $status = $data['status'] ?? null;
        $update = [];

        if ($status === NotificationLog::STATUS_DELIVERED) {
            $update['status'] = NotificationLog::STATUS_DELIVERED;
            $update['delivered_at'] = $data['delivered_at'] ?? now();
        } elseif ($status === NotificationLog::STATUS_FAILED) {
            $update['status'] = NotificationLog::STATUS_FAILED;
            $update['error_message'] = $data['error'] ?? null;
        }

        if (! empty($data['error'])) {
            $update['error_message'] = $data['error'];
        }

        if (! empty($data['delivered_at'])) {
            $update['delivered_at'] = $data['delivered_at'];
        }

        if (! empty($update)) {
            $log->update($update);
        }

        return response()->json(['success' => true]);
    }
}
