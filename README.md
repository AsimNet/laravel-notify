# Laravel Notify

A Laravel package for push notifications with Firebase Cloud Messaging (FCM), featuring a Filament v4 admin panel.

## Features

- **Firebase Cloud Messaging (FCM)** - Send push notifications to iOS, Android, and Web
- **Multi-tenant Support** - Full Stancl/Tenancy integration
- **Filament v4 Admin Panel** - Complete management interface
- **Device Token Management** - Register and manage device tokens
- **Topic Subscriptions** - Subscribe users to topics for broadcast notifications
- **Scheduled Notifications** - Queue notifications for future delivery
- **Comprehensive Logging** - Track all sent notifications with detailed logs
- **Rate Limiting** - Protect against notification spam (configurable per minute and per user)
- **Queue Support** - Process notifications in the background
- **Encrypted Credentials** - Secure storage for FCM service account
- **SMS Ready** - Pluggable SMS drivers with a generic HTTP driver and custom driver extensions

## Requirements

- PHP 8.2+
- Laravel 11+
- Filament 4+
- Spatie Laravel Settings

## Installation

```bash
composer require asimnet/laravel-notify
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=notify-config
```

### SMS Quick Start

1. Enable SMS in `config/notify.php` (`sms.enabled => true`) and set `default_driver`.
2. For simple providers, configure the bundled `http_generic` driver (URL, auth token, field names).
3. To send via Laravel Notifications, add `SmsChannel::class` or the channel name `'sms'` in `via()` and implement `toSms()`:

```php
use Asimnet\Notify\Channels\SmsChannel;

public function via($notifiable): array
{
    return [SmsChannel::class]; // or simply 'sms'
}

public function toSms($notifiable): array
{
    return [
        'message' => 'Your code is 1234',
        // optional: 'to' => '+15551234567', 'driver' => 'http_generic', 'options' => [...]
    ];
}
```

### WhatsApp Business (WBA) Quick Start

- Requires the sibling package `asimnet/wba-filament` (already present in `packages/wba-filament`).
- Enable WBA in `config/notify.php` (`wba.enabled => true`). If `wba-filament` is already configured, you do **not** need to re-enter credentials in Notify; it reuses the same configuration.
- Use the notification channel:

```php
use Asimnet\Notify\Channels\WbaChannel;

public function via($notifiable): array
{
    return [WbaChannel::class]; // or 'wba'
}

public function toWba($notifiable): array
{
    return [
        'template_name' => 'greeting',
        'language' => 'ar',
        'parameters' => ['name' => $notifiable->name],
        // optional: 'phone' => '+1555...', 'metadata' => [...]
    ];
}
```

- Taqnyat webhook adapter: POST provider callbacks to `/api/notify/webhooks/taqnyat`; generic SMS webhook is `/api/notify/webhooks/sms`.

### Multi-channel sending (FCM / SMS / WBA)

Use the fluent `Notify` facade; select the channel with `via()` or the dedicated channel classes:

```php
use Asimnet\Notify\Facades\Notify;
use Asimnet\Notify\Channels\SmsChannel;
use Asimnet\Notify\Channels\WbaChannel;

$message = Notify::message('Hello', 'Body here');

// FCM (default)
Notify::to($userIds)->send($message);

// SMS
Notify::to($userIds)->via(SmsChannel::class)->send($message);

// WBA
Notify::to($userIds)->via(WbaChannel::class)->send($message);

// Schedule (currently FCM only)
Notify::schedule($userId, $message, now()->addHour());
```

Scheduling is currently supported for FCM only; SMS/WBA always send immediately.

### Routing phone numbers (important)

Notify does **not** guess phone fields. You must supply a destination:

- Implement `routeNotificationForSms($notification)` on your notifiable (e.g., User) to return an E.164 number for SMS.
- Implement `routeNotificationForWba()` to return the raw phone for WhatsApp (e.g., `9665xxxxxxx`).
- Or return `to` inside `toSms()` / `toWba()` on the notification itself.

If neither is provided, the send is skipped with an exception.

### Topic channel preferences (per user)

Each topic subscription stores per-channel flags:

| Column       | Default | Meaning                                     |
|--------------|---------|---------------------------------------------|
| fcm_enabled  | true    | Push via FCM is allowed for this topic      |
| sms_enabled  | false   | SMS is allowed for this topic               |
| wba_enabled  | false   | WhatsApp Business is allowed for this topic |

Update a user’s preferences in your app:

```php
use Asimnet\Notify\Models\TopicSubscription;

$sub = TopicSubscription::firstWhere([
    'topic_id' => $topicId,
    'user_id' => $userId,
]);

$sub->update([
    'fcm_enabled' => true,
    'sms_enabled' => false,
    'wba_enabled' => true,
]);
```

FCM syncing respects `fcm_enabled`; for SMS/WBA check these flags before sending to topic subscribers.

API endpoints for mobile apps:
- `GET /api/notify/topics/preferences` — list current user’s topics with `fcm_enabled`, `sms_enabled`, `wba_enabled`.
- `PATCH /api/notify/topics/{topic}/preferences` — update booleans for a topic (creates subscription if missing).

You can extend drivers at runtime:

```php
app(\Asimnet\Notify\SmsManager::class)->extend('my-provider', function ($app) {
    return new MyCustomSmsDriver(config('notify.sms.drivers.my-provider'));
});
```

### Multi-tenant Setup (Stancl/Tenancy)

Add the package migration path to your `config/tenancy.php`:

```php
'migration_parameters' => [
    '--force' => true,
    '--path' => [
        database_path('migrations/tenant'),
        base_path('packages/notify/database/migrations/tenant'),
    ],
    '--realpath' => true,
],
```

Then run tenant migrations:

```bash
php artisan tenants:migrate
```

### Single-tenant Setup

Run the migrations directly:

```bash
php artisan migrate
```

## Configuration

### Firebase Setup

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Create a new project or select existing one
3. Go to Project Settings > Service Accounts
4. Generate a new private key (JSON file)
5. In Filament admin, go to Notifications > Settings
6. Paste the JSON content in the credentials field

### Filament Integration

Register the plugin in your Filament panel:

```php
use Asimnet\Notify\Filament\NotifyPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            NotifyPlugin::make()
                ->navigationGroup('Notifications')
                ->topicResource(true)  // Enable/disable topic management
                ->logResource(true),   // Enable/disable log viewer
        ]);
}
```

### User Model

Add the `HasDeviceTokens` trait to your User model:

```php
use Asimnet\Notify\Models\Concerns\HasDeviceTokens;

class User extends Authenticatable
{
    use HasDeviceTokens;
}
```

## Usage

### Sending Notifications

```php
use Asimnet\Notify\Facades\Notify;

// Send to a user
Notify::to($user)
    ->title('Hello!')
    ->body('This is a test notification')
    ->send();

// Send to multiple users
Notify::to($users)
    ->title('Announcement')
    ->body('Important update for everyone')
    ->send();

// Send to a topic
Notify::toTopic('news')
    ->title('Breaking News')
    ->body('Something important happened')
    ->send();

// With custom data payload
Notify::to($user)
    ->title('New Message')
    ->body('You have a new message')
    ->data(['screen' => 'messages', 'id' => 123])
    ->send();
```

### API Endpoints

Register device tokens:

```
POST /api/notify/devices
{
    "token": "fcm-device-token",
    "platform": "ios|android|web",
    "device_name": "iPhone 15"
}
```

Remove device token:

```
DELETE /api/notify/devices/{device}
```

Subscribe to topics:

```
POST /api/notify/topics/{topic}/subscribe
POST /api/notify/topics/{topic}/unsubscribe
```

Get user's subscribed topics:

```
GET /api/notify/topics/subscribed
```

Topic preferences (per channel):

```
GET /api/notify/topics/preferences
PATCH /api/notify/topics/{topic}/preferences
```

### Webhooks

- Generic SMS delivery callbacks: `POST /api/notify/webhooks/sms`
- Taqnyat SMS adapter: `POST /api/notify/webhooks/taqnyat`
- WBA: delivery receipts depend on your WBA provider; wire their callback URL to the WBA service (see `wba-filament` docs).
- FCM: Google does not post delivery receipts back; rely on FCM response status and Notify logs.

Handle provider payloads in your webhook controller/driver to update delivery status.

### Topic preference schemas (for frontend/QA)

Request schema (PATCH `/api/notify/topics/{topic}/preferences`):

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "properties": {
    "fcm_enabled": { "type": "boolean" },
    "sms_enabled": { "type": "boolean" },
    "wba_enabled": { "type": "boolean" }
  },
  "additionalProperties": false
}
```

Response schema (GET or PATCH):

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "properties": {
    "data": {
      "type": ["array", "object"],
      "items": {
        "type": "object",
        "properties": {
          "topic_id": { "type": "integer" },
          "fcm_enabled": { "type": "boolean" },
          "sms_enabled": { "type": "boolean" },
          "wba_enabled": { "type": "boolean" }
        },
        "required": ["topic_id", "fcm_enabled", "sms_enabled", "wba_enabled"],
        "additionalProperties": false
      }
    }
  },
  "required": ["data"],
  "additionalProperties": false
}
```

Mock payloads for QA live at `packages/notify/docs/mocks/topic-preferences.json`.

### Artisan Commands

Process scheduled notifications:

```bash
php artisan notify:process-scheduled
```

## Filament bulk action example (host app)

Example bulk action on a Filament Users table that mirrors the host app implementation:

```php
BulkAction::make('notify_multi_channel')
    ->label('إرسال إشعار / رسالة')
    ->form([
        Select::make('channel')->options(['fcm' => 'FCM', 'sms' => 'SMS', 'wba' => 'WBA'])->required(),
        Select::make('when')->options(['now' => 'الآن', 'schedule' => 'مجدول'])->default('now'),
        DateTimePicker::make('scheduled_at')->hidden(fn ($get) => $get('when') !== 'schedule'),
        TextInput::make('title')->required(),
        Textarea::make('body')->required(),
    ])
    ->action(function (array $data, $records) {
        $message = NotificationMessage::create($data['title'], $data['body']);

        if (($data['when'] ?? 'now') === 'schedule' && $data['channel'] !== 'fcm') {
            Notification::make()->warning()->title('Scheduling is FCM-only')->send();
            return;
        }

        $ids = collect($records)->pluck('id');

        if (($data['when'] ?? 'now') === 'schedule') {
            $at = $data['scheduled_at'] ?? now()->addMinutes(10);
            $ids->each(fn ($id) => Notify::schedule($id, $message, $at));
            return;
        }

        Notify::to($ids)->via($data['channel'])->send($message);
    });
```

Scheduling is currently supported for FCM; SMS/WBA send immediately.

## Settings

The following settings can be configured via the Filament admin panel:

| Setting | Description | Default |
|---------|-------------|---------|
| FCM Enabled | Enable/disable FCM sending | true |
| Logging Enabled | Enable notification logging | true |
| Log Retention Days | Days to keep logs | 180 |
| Store Payload | Store notification payloads in logs | false |
| Rate Limit Per Minute | Max notifications per minute | 1000 |
| Rate Limit Per User/Hour | Max notifications per user per hour | 10 |
| Auto Subscribe to Defaults | Auto-subscribe new devices to default topics | true |

## Testing

```bash
composer test
```

Use the fake FCM service in tests:

```php
use Asimnet\Notify\Testing\FakeFcmService;

public function test_notification_is_sent()
{
    $fake = FakeFcmService::fake();

    Notify::to($user)->title('Test')->send();

    $fake->assertSentTo($user);
}
```

## License

MIT License. See [LICENSE](LICENSE) for details.
