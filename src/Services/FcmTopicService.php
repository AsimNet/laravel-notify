<?php

namespace Asimnet\Notify\Services;

use Asimnet\Notify\Contracts\FcmService;
use Asimnet\Notify\DTOs\NotificationMessage;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\MessagingException;
use RuntimeException;

/**
 * FCM topic subscription service using kreait/firebase-php.
 *
 * Handles subscribing/unsubscribing device tokens to FCM topics
 * with proper error handling and batch processing.
 *
 * Note: This service only implements topic subscription methods.
 * For message sending, use FcmMessageService instead.
 *
 * @deprecated Use FcmMessageService for full FCM functionality including message sending.
 */
class FcmTopicService implements FcmService
{
    /**
     * Maximum tokens per FCM API request.
     */
    private const MAX_TOKENS_PER_REQUEST = 1000;

    public function __construct(
        private readonly Messaging $messaging
    ) {}

    /**
     * {@inheritdoc}
     */
    public function subscribeToTopic(string $topic, array $tokens): array
    {
        if (empty($tokens)) {
            return ['success' => [], 'failures' => []];
        }

        $success = [];
        $failures = [];

        // Process in batches of 1000 (FCM limit)
        $chunks = array_chunk($tokens, self::MAX_TOKENS_PER_REQUEST);

        foreach ($chunks as $chunk) {
            try {
                $result = $this->messaging->subscribeToTopic($topic, $chunk);

                foreach ($result as $token => $status) {
                    if ($status === 'OK' || $status === true) {
                        $success[] = $token;
                    } else {
                        $failures[$token] = is_string($status) ? $status : 'UNKNOWN_ERROR';
                    }
                }
            } catch (MessagingException $e) {
                // If entire batch fails, mark all as failed
                foreach ($chunk as $token) {
                    $failures[$token] = $e->getMessage();
                }
            }
        }

        return ['success' => $success, 'failures' => $failures];
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribeFromTopic(string $topic, array $tokens): array
    {
        if (empty($tokens)) {
            return ['success' => [], 'failures' => []];
        }

        $success = [];
        $failures = [];

        $chunks = array_chunk($tokens, self::MAX_TOKENS_PER_REQUEST);

        foreach ($chunks as $chunk) {
            try {
                $result = $this->messaging->unsubscribeFromTopic($topic, $chunk);

                foreach ($result as $token => $status) {
                    if ($status === 'OK' || $status === true) {
                        $success[] = $token;
                    } else {
                        $failures[$token] = is_string($status) ? $status : 'UNKNOWN_ERROR';
                    }
                }
            } catch (MessagingException $e) {
                foreach ($chunk as $token) {
                    $failures[$token] = $e->getMessage();
                }
            }
        }

        return ['success' => $success, 'failures' => $failures];
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribeFromAllTopics(array $tokens): array
    {
        if (empty($tokens)) {
            return ['success' => [], 'failures' => []];
        }

        try {
            // Note: kreait 7.x uses unsubscribeFromAllTopics
            // kreait 8.x might have different API
            $this->messaging->unsubscribeFromAllTopics($tokens);

            return ['success' => $tokens, 'failures' => []];
        } catch (MessagingException $e) {
            $failures = [];
            foreach ($tokens as $token) {
                $failures[$token] = $e->getMessage();
            }

            return ['success' => [], 'failures' => $failures];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateToken(string $token): bool
    {
        try {
            // Send a dry-run message to validate the token
            $this->messaging->validateRegistrationTokens([$token]);

            return true;
        } catch (MessagingException $e) {
            return false;
        }
    }

    /**
     * Check if an error indicates the token is no longer valid.
     *
     * @param  string  $error  The error message from FCM
     * @return bool True if token should be removed
     */
    public static function isUnregisteredError(string $error): bool
    {
        $unregisteredPatterns = [
            'UNREGISTERED',
            'NOT_FOUND',
            'INVALID_ARGUMENT',
            'InvalidToken',
        ];

        foreach ($unregisteredPatterns as $pattern) {
            if (str_contains(strtoupper($error), strtoupper($pattern))) {
                return true;
            }
        }

        return false;
    }

    // ========================================
    // Message Sending Methods (Not Supported)
    // Use FcmMessageService for message sending
    // ========================================

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException This service does not support message sending
     */
    public function send(string $token, NotificationMessage $message): array
    {
        throw new RuntimeException(
            'FcmTopicService does not support message sending. Use FcmMessageService instead.'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException This service does not support message sending
     */
    public function sendToTopic(string $topic, NotificationMessage $message): array
    {
        throw new RuntimeException(
            'FcmTopicService does not support message sending. Use FcmMessageService instead.'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException This service does not support message sending
     */
    public function sendMulticast(array $tokens, NotificationMessage $message): array
    {
        throw new RuntimeException(
            'FcmTopicService does not support message sending. Use FcmMessageService instead.'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException This service does not support message sending
     */
    public function sendData(string $token, array $data): array
    {
        throw new RuntimeException(
            'FcmTopicService does not support message sending. Use FcmMessageService instead.'
        );
    }
}
