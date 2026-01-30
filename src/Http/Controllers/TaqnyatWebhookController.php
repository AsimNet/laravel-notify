<?php

namespace Asimnet\Notify\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Adapter for Taqnyat SMS webhook payloads into the generic SMS webhook.
 */
class TaqnyatWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $results = $request->post('results', []);

        $success = true;
        $handled = 0;

        foreach ($results as $result) {
            if (! isset($result['msgId'])) {
                continue;
            }

            $payload = [
                'external_id' => $result['msgId'],
                'status' => $this->mapStatus($result['status'] ?? null),
                'error' => $result['desc'] ?? null,
            ];

            $response = app(SmsWebhookController::class)(new Request($payload));

            if ($response->getStatusCode() !== 200) {
                $success = false;
            }

            $handled++;
        }

        if ($handled === 0) {
            return response()->json(['success' => false, 'message' => 'No results processed'], 400);
        }

        return response()->json(['success' => $success]);
    }

    protected function mapStatus(?string $status): ?string
    {
        return match (strtolower((string) $status)) {
            'delivered' => 'delivered',
            'failed' => 'failed',
            default => null,
        };
    }
}
