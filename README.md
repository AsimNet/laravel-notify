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

### Artisan Commands

Process scheduled notifications:

```bash
php artisan notify:process-scheduled
```

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
