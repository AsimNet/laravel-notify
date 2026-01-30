<?php

use Asimnet\Notify\Http\Controllers\DeviceTokenController;
use Asimnet\Notify\Http\Controllers\SmsWebhookController;
use Asimnet\Notify\Http\Controllers\TaqnyatWebhookController;
use Asimnet\Notify\Http\Controllers\TopicPreferencesController;
use Asimnet\Notify\Http\Controllers\TopicSubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notify Package API Routes
|--------------------------------------------------------------------------
|
| API routes for the Notify notification center package.
| All routes are prefixed with /api/notify and protected by auth:api.
| Middleware stack is configurable via notify.route_middleware.
|
*/

$middleware = config('notify.route_middleware', ['api', 'auth:api']);

Route::middleware($middleware)->prefix('api/notify')->group(function () {

    // Device Token Management
    // GET    /api/notify/devices           - List user's devices
    // POST   /api/notify/devices           - Register new device
    // PUT    /api/notify/devices/{device}  - Update device
    // DELETE /api/notify/devices/{device}  - Delete device
    Route::apiResource('devices', DeviceTokenController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['devices' => 'device']);

    // Topic Subscription Management
    // GET  /api/notify/topics                     - List available topics
    // POST /api/notify/topics/{topic}/subscribe   - Subscribe to topic
    // POST /api/notify/topics/{topic}/unsubscribe - Unsubscribe from topic
    Route::get('topics', [TopicSubscriptionController::class, 'index'])
        ->name('notify.topics.index');

    Route::post('topics/{topic}/subscribe', [TopicSubscriptionController::class, 'subscribe'])
        ->name('notify.topics.subscribe');

    Route::post('topics/{topic}/unsubscribe', [TopicSubscriptionController::class, 'unsubscribe'])
        ->name('notify.topics.unsubscribe');

    // Topic channel preferences (for mobile apps)
    Route::get('topics/preferences', [TopicPreferencesController::class, 'show'])
        ->name('notify.topics.preferences.show');

    Route::patch('topics/{topic}/preferences', [TopicPreferencesController::class, 'update'])
        ->name('notify.topics.preferences.update');

});

// Public SMS delivery webhook (provider callbacks)
$webhookMiddleware = config('notify.sms.webhook_middleware', ['api']);
Route::middleware($webhookMiddleware)
    ->prefix('api/notify')
    ->post('webhooks/sms', SmsWebhookController::class)
    ->name('notify.webhooks.sms');
Route::middleware($webhookMiddleware)
    ->prefix('api/notify')
    ->post('webhooks/taqnyat', TaqnyatWebhookController::class)
    ->name('notify.webhooks.sms.taqnyat');
