<?php

use Asimnet\Notify\Http\Controllers\DeviceTokenController;
use Asimnet\Notify\Http\Controllers\TopicSubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notify Package API Routes
|--------------------------------------------------------------------------
|
| API routes for the Notify notification center package.
| All routes are prefixed with /api/notify and protected by auth:api.
|
*/

Route::middleware(['api', 'auth:api'])->prefix('api/notify')->group(function () {

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

});
