<?php

namespace Asimnet\Notify\Http\Controllers;

use Asimnet\Notify\Events\TopicSubscribed;
use Asimnet\Notify\Events\TopicUnsubscribed;
use Asimnet\Notify\Http\Resources\TopicResource;
use Asimnet\Notify\Models\DeviceToken;
use Asimnet\Notify\Models\Topic;
use Asimnet\Notify\Models\TopicSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/**
 * REST controller for managing topic subscriptions.
 *
 * Handles listing available topics and subscribing/unsubscribing
 * users with automatic FCM synchronization via events.
 */
class TopicSubscriptionController extends Controller
{
    /**
     * List available topics for subscription.
     *
     * GET /api/notify/topics
     *
     * Returns all public topics with subscription status for authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $topics = Topic::query()
            ->public()
            ->orderBy('name')
            ->get();

        return TopicResource::collection($topics);
    }

    /**
     * Subscribe user to a topic.
     *
     * POST /api/notify/topics/{topic}/subscribe
     */
    public function subscribe(Request $request, Topic $topic): JsonResponse
    {
        $user = $request->user();

        // Only allow subscription to public topics
        if (! $topic->is_public) {
            return response()->json([
                'success' => false,
                'message' => __('notify::notify.validation.topic_not_subscribable'),
            ], 403);
        }

        // Get user's active device tokens
        $deviceTokens = DeviceToken::query()
            ->where('user_id', $user->id)
            ->pluck('token')
            ->toArray();

        if (empty($deviceTokens)) {
            return response()->json([
                'success' => false,
                'message' => __('notify::notify.validation.no_devices_registered'),
            ], 422);
        }

        // Get tenant ID for multi-tenant support
        $tenantId = $this->getTenantId();

        // Create or find subscription (idempotent)
        $subscription = TopicSubscription::firstOrCreate(
            [
                'user_id' => $user->id,
                'topic_id' => $topic->id,
                'tenant_id' => $tenantId,
            ],
            [
                'fcm_synced' => false,
            ]
        );

        // Only increment count and dispatch event if this is a new subscription
        if ($subscription->wasRecentlyCreated) {
            $topic->increment('subscriber_count');

            // Dispatch event for FCM sync
            TopicSubscribed::dispatch($subscription, $deviceTokens);
        } else {
            // Existing subscription - still sync in case devices changed
            $subscription->update(['fcm_synced' => false]);
            TopicSubscribed::dispatch($subscription, $deviceTokens);
        }

        return response()->json([
            'success' => true,
            'message' => __('notify::notify.api.subscribed_to_topic'),
            'data' => new TopicResource($topic->fresh()),
        ]);
    }

    /**
     * Unsubscribe user from a topic.
     *
     * POST /api/notify/topics/{topic}/unsubscribe
     */
    public function unsubscribe(Request $request, Topic $topic): JsonResponse
    {
        $user = $request->user();

        // Find the subscription
        $subscription = TopicSubscription::query()
            ->where('user_id', $user->id)
            ->where('topic_id', $topic->id)
            ->first();

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => __('notify::notify.validation.not_subscribed_to_topic'),
            ], 422);
        }

        // Get user's device tokens for FCM unsubscribe
        $deviceTokens = DeviceToken::query()
            ->where('user_id', $user->id)
            ->pluck('token')
            ->toArray();

        // Delete subscription
        $subscription->delete();

        // Decrement subscriber count
        if ($topic->subscriber_count > 0) {
            $topic->decrement('subscriber_count');
        }

        // Dispatch event for FCM sync
        TopicUnsubscribed::dispatch($topic, $deviceTokens);

        return response()->json([
            'success' => true,
            'message' => __('notify::notify.api.unsubscribed_from_topic'),
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
