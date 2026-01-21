<?php

namespace Asimnet\Notify\Contracts;

use Asimnet\Notify\DTOs\NotificationMessage;

/**
 * Contract for FCM operations including topic subscriptions and message sending.
 *
 * عقد لعمليات FCM بما في ذلك الاشتراكات في المواضيع وإرسال الرسائل.
 *
 * Implementations must handle:
 * - FCM's 1000-token-per-request limit for subscriptions
 * - FCM's 500-token-per-request limit for multicast messages
 * - Error handling for UNREGISTERED/INVALID_ARGUMENT tokens
 * - Return structured results with success/failure information
 */
interface FcmService
{
    // ========================================
    // Topic Subscription Methods
    // طرق الاشتراك في المواضيع
    // ========================================

    /**
     * Subscribe device tokens to a topic.
     *
     * اشتراك رموز الأجهزة في موضوع.
     *
     * @param  string  $topic  Topic name (without /topics/ prefix)
     * @param  array<string>  $tokens  FCM registration tokens
     * @return array{success: array<string>, failures: array<string, string>}
     */
    public function subscribeToTopic(string $topic, array $tokens): array;

    /**
     * Unsubscribe device tokens from a topic.
     *
     * إلغاء اشتراك رموز الأجهزة من موضوع.
     *
     * @param  string  $topic  Topic name (without /topics/ prefix)
     * @param  array<string>  $tokens  FCM registration tokens
     * @return array{success: array<string>, failures: array<string, string>}
     */
    public function unsubscribeFromTopic(string $topic, array $tokens): array;

    /**
     * Unsubscribe tokens from all topics.
     *
     * Used when a device is deleted to clean up all topic subscriptions.
     *
     * إلغاء اشتراك الرموز من جميع المواضيع.
     * يستخدم عند حذف جهاز لتنظيف جميع اشتراكات المواضيع.
     *
     * @param  array<string>  $tokens  FCM registration tokens
     * @return array{success: array<string>, failures: array<string, string>}
     */
    public function unsubscribeFromAllTopics(array $tokens): array;

    /**
     * Validate that a token is still valid with FCM.
     *
     * التحقق من أن الرمز لا يزال صالحاً مع FCM.
     *
     * @param  string  $token  FCM registration token
     * @return bool True if token is valid
     */
    public function validateToken(string $token): bool;

    // ========================================
    // Message Sending Methods
    // طرق إرسال الرسائل
    // ========================================

    /**
     * Send a notification to a single device token.
     *
     * إرسال إشعار إلى رمز جهاز واحد.
     *
     * @param  string  $token  FCM registration token
     * @param  NotificationMessage  $message  Notification message content
     * @return array{success: bool, message_id: ?string, error: ?string}
     *                                                                   - success: Whether the message was sent successfully
     *                                                                   - message_id: FCM message ID if successful
     *                                                                   - error: Error message if failed
     */
    public function send(string $token, NotificationMessage $message): array;

    /**
     * Send a notification to a topic.
     *
     * All devices subscribed to the topic will receive the notification.
     *
     * إرسال إشعار إلى موضوع.
     * ستتلقى جميع الأجهزة المشتركة في الموضوع الإشعار.
     *
     * @param  string  $topic  Topic name (without /topics/ prefix)
     * @param  NotificationMessage  $message  Notification message content
     * @return array{success: bool, message_id: ?string, error: ?string}
     *                                                                   - success: Whether the message was sent successfully
     *                                                                   - message_id: FCM message ID if successful
     *                                                                   - error: Error message if failed
     */
    public function sendToTopic(string $topic, NotificationMessage $message): array;

    /**
     * Send a notification to multiple device tokens (multicast).
     *
     * FCM allows maximum 500 tokens per multicast request.
     * Implementation handles batching for larger token sets.
     *
     * إرسال إشعار إلى عدة رموز أجهزة (بث متعدد).
     * يسمح FCM بحد أقصى 500 رمز لكل طلب بث متعدد.
     * يتعامل التطبيق مع التجميع لمجموعات الرموز الأكبر.
     *
     * @param  array<string>  $tokens  FCM registration tokens (max 500 per call, implementation batches larger sets)
     * @param  NotificationMessage  $message  Notification message content
     * @return array{success_count: int, failure_count: int, results: array<string, array{success: bool, message_id: ?string, error: ?string}>}
     *                                                                                                                                          - success_count: Number of successful deliveries
     *                                                                                                                                          - failure_count: Number of failed deliveries
     *                                                                                                                                          - results: Per-token results keyed by token
     */
    public function sendMulticast(array $tokens, NotificationMessage $message): array;

    /**
     * Send a data-only message (no visible notification).
     *
     * Use this for silent background updates where no user notification
     * should be displayed. The app handles the data in the background.
     *
     * إرسال رسالة بيانات فقط (بدون إشعار مرئي).
     * استخدم هذا للتحديثات الصامتة في الخلفية حيث لا ينبغي عرض
     * إشعار للمستخدم. يتعامل التطبيق مع البيانات في الخلفية.
     *
     * @param  string  $token  FCM registration token
     * @param  array<string, string>  $data  Key-value pairs (values must be strings)
     * @return array{success: bool, message_id: ?string, error: ?string}
     *                                                                   - success: Whether the message was sent successfully
     *                                                                   - message_id: FCM message ID if successful
     *                                                                   - error: Error message if failed
     */
    public function sendData(string $token, array $data): array;
}
