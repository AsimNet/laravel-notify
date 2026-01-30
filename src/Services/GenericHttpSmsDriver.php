<?php

namespace Asimnet\Notify\Services;

use Asimnet\Notify\DTOs\SmsSendResult;
use Illuminate\Http\Client\Response;

/**
 * Config-driven HTTP SMS driver (POST/GET, json/form, auth headers).
 *
 * Good for simple providers; complex providers can extend AbstractSmsDriver directly.
 */
class GenericHttpSmsDriver extends AbstractSmsDriver
{
    public function __construct(
        protected array $config
    ) {
        parent::__construct();
    }

    public function name(): string
    {
        return $this->config['name'] ?? 'http-generic';
    }

    protected function mapPayload(string $to, string $message, array $options): array
    {
        $method = $this->config['method'] ?? 'post';
        $url = $this->config['url'] ?? '';
        $bodyType = $this->config['body_type'] ?? 'json'; // json|form|query
        $auth = $this->config['auth'] ?? [];
        $sender = $options['sender'] ?? $this->config['sender'] ?? null;

        $payload = [
            'url' => $url,
            'method' => $method,
            'headers' => $auth['headers'] ?? [],
            'body' => null,
            'query' => null,
        ];

        $fields = [
            $this->config['fields']['to'] ?? 'to' => $to,
            $this->config['fields']['message'] ?? 'message' => $message,
        ];

        if ($sender) {
            $fields[$this->config['fields']['sender'] ?? 'sender'] = $sender;
        }

        if ($bodyType === 'query') {
            $payload['query'] = $fields;
        } elseif ($bodyType === 'form') {
            $payload['body'] = $fields;
            $payload['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
        } else {
            $payload['body'] = $fields;
            $payload['headers']['Content-Type'] = 'application/json';
        }

        // Simple token / basic auth helpers
        if (($auth['type'] ?? null) === 'bearer' && ! empty($auth['token'])) {
            $payload['headers']['Authorization'] = 'Bearer '.$auth['token'];
        } elseif (($auth['type'] ?? null) === 'basic' && isset($auth['username'], $auth['password'])) {
            $payload['headers']['Authorization'] = 'Basic '.base64_encode("{$auth['username']}:{$auth['password']}");
        }

        return $payload;
    }

    protected function handleResponse(Response $response, array $payload, array $options): SmsSendResult
    {
        if (! $response->successful()) {
            return SmsSendResult::failure($response->body(), $response->json(), $this->name());
        }

        $json = $response->json();
        $messageId = $json[$this->config['response_keys']['id'] ?? 'messageId'] ?? null;

        return SmsSendResult::success($messageId ?? $this->fallbackMessageId(), $json, $this->name());
    }
}
