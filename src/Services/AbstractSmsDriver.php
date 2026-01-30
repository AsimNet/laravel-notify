<?php

namespace Asimnet\Notify\Services;

use Asimnet\Notify\Contracts\SmsDriver;
use Asimnet\Notify\DTOs\SmsSendResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Base SMS driver with helper methods for HTTP providers.
 *
 * Providers can extend and implement mapPayload()/handleResponse().
 */
abstract class AbstractSmsDriver implements SmsDriver
{
    protected PendingRequest $http;

    public function __construct(?PendingRequest $http = null)
    {
        $this->http = $http ?? Http::withoutVerifying();
    }

    /**
     * Entry point: send message using mapped payload + HTTP client.
     */
    public function send(string $to, string $message, array $options = []): SmsSendResult
    {
        try {
            $payload = $this->mapPayload($to, $message, $options);
            $response = $this->performRequest($payload, $options);

            return $this->handleResponse($response, $payload, $options);
        } catch (Throwable $e) {
            return SmsSendResult::failure($e->getMessage(), null, $this->name());
        }
    }

    /**
     * Map request payload/body/headers.
     *
     * @param  array<string, mixed>  $options
     * @return array{url:string,method:string,body:mixed,headers?:array<string,string>,query?:array<string,mixed>}
     */
    abstract protected function mapPayload(string $to, string $message, array $options): array;

    /**
     * Parse provider response into SmsSendResult.
     */
    abstract protected function handleResponse(Response $response, array $payload, array $options): SmsSendResult;

    /**
     * Perform HTTP request using mapped payload.
     */
    protected function performRequest(array $payload, array $options): Response
    {
        $method = strtolower($payload['method'] ?? 'post');

        return match ($method) {
            'get' => $this->http->withHeaders($payload['headers'] ?? [])->get(
                $payload['url'],
                $payload['query'] ?? $payload['body'] ?? []
            ),
            default => $this->http->withHeaders($payload['headers'] ?? [])->{$method}(
                $payload['url'],
                $payload['body'] ?? []
            ),
        };
    }

    /**
     * Helper to generate a pseudo reference when provider doesn't return one.
     */
    protected function fallbackMessageId(): string
    {
        return Str::uuid()->toString();
    }
}
