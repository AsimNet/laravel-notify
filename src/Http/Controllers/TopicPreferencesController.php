<?php

namespace Asimnet\Notify\Http\Controllers;

use Asimnet\Notify\Models\TopicSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TopicPreferencesController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $userId = $request->user()->getKey();

        $prefs = TopicSubscription::query()
            ->where('user_id', $userId)
            ->get(['topic_id', 'fcm_enabled', 'sms_enabled', 'wba_enabled'])
            ->map(fn ($sub) => [
                'topic_id' => $sub->topic_id,
                'fcm_enabled' => (bool) $sub->fcm_enabled,
                'sms_enabled' => (bool) $sub->sms_enabled,
                'wba_enabled' => (bool) $sub->wba_enabled,
            ])
            ->values();

        return response()->json(['data' => $prefs]);
    }

    public function update(Request $request, int $topicId): JsonResponse
    {
        $userId = $request->user()->getKey();

        $data = $request->validate([
            'fcm_enabled' => 'sometimes|boolean',
            'sms_enabled' => 'sometimes|boolean',
            'wba_enabled' => 'sometimes|boolean',
        ]);

        $sub = TopicSubscription::firstOrCreate([
            'topic_id' => $topicId,
            'user_id' => $userId,
        ], [
            'fcm_synced' => false,
        ]);

        $sub->fill($data);
        $sub->save();

        return response()->json([
            'data' => [
                'topic_id' => $sub->topic_id,
                'fcm_enabled' => (bool) $sub->fcm_enabled,
                'sms_enabled' => (bool) $sub->sms_enabled,
                'wba_enabled' => (bool) $sub->wba_enabled,
            ],
        ]);
    }
}
