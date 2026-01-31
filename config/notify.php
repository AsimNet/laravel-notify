<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Configuration / إعدادات تعدد المستأجرين
    |--------------------------------------------------------------------------
    |
    | Configure multi-tenancy support for the Notify package.
    | When enabled, all models will be scoped to the current tenant.
    |
    | تكوين دعم تعدد المستأجرين لحزمة الإشعارات.
    | عند التفعيل، سيتم تحديد نطاق جميع النماذج للمستأجر الحالي.
    |
    */
    'tenancy' => [
        'enabled' => env('NOTIFY_TENANCY_ENABLED', false),
        'tenant_model' => env('NOTIFY_TENANT_MODEL', 'App\\Models\\Tenant'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration / إعدادات الطابور
    |--------------------------------------------------------------------------
    |
    | Configure the queue connection and queue name for notification jobs.
    |
    | تكوين اتصال الطابور واسم الطابور لوظائف الإشعارات.
    |
    */
    'queue' => [
        'connection' => env('NOTIFY_QUEUE_CONNECTION', 'redis'),
        'queue' => env('NOTIFY_QUEUE_NAME', 'notifications'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Horizon Integration / تكامل Horizon
    |--------------------------------------------------------------------------
    |
    | Configure Laravel Horizon integration for notification queue management.
    | The supervisor settings define worker processes for notification queues.
    |
    | تكوين تكامل Laravel Horizon لإدارة طابور الإشعارات.
    | تحدد إعدادات المشرف عمليات العمال لطوابير الإشعارات.
    |
    */
    'horizon' => [
        'enabled' => env('NOTIFY_HORIZON_ENABLED', true),
        'supervisor' => [
            'connection' => 'redis',
            'queue' => ['notifications', 'notifications-high', 'notifications-low'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses' => 1,
            'maxProcesses' => 5,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 120,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration / إعدادات التسجيل
    |--------------------------------------------------------------------------
    |
    | Configure notification logging and retention settings.
    | Logs older than retention_days will be automatically deleted.
    |
    | تكوين تسجيل الإشعارات وإعدادات الاحتفاظ.
    | سيتم حذف السجلات الأقدم من أيام الاحتفاظ تلقائياً.
    |
    */
    'logging' => [
        'enabled' => env('NOTIFY_LOGGING_ENABLED', true),
        'retention_days' => env('NOTIFY_LOG_RETENTION_DAYS', 180),
        'store_payload' => env('NOTIFY_LOG_STORE_PAYLOAD', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Table Names / أسماء جداول قاعدة البيانات
    |--------------------------------------------------------------------------
    |
    | Customize table names used by the Notify package.
    | All tables are prefixed with 'notify_' by default to avoid conflicts.
    |
    | تخصيص أسماء الجداول المستخدمة من قبل حزمة الإشعارات.
    | جميع الجداول تبدأ بـ 'notify_' افتراضياً لتجنب التعارضات.
    |
    */
    'tables' => [
        'device_tokens' => 'notify_device_tokens',
        'topics' => 'notify_topics',
        'topic_subscriptions' => 'notify_topic_subscriptions',
        'logs' => 'notify_logs',
        'scheduled_notifications' => 'notify_scheduled_notifications',
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Configuration / إعدادات الرسائل القصيرة
    |--------------------------------------------------------------------------
    |
    | Define SMS drivers and defaults. Drivers can be extended at runtime
    | via SmsManager::extend() from the host application.
    |
    */
    'sms' => [
        'enabled' => env('NOTIFY_SMS_ENABLED', false),
        'default_driver' => env('NOTIFY_SMS_DRIVER', 'http_generic'),
        // Optional callable to supply per-tenant or dynamic credentials:
        // fn (): array => ['driver_key' => ['auth' => ['token' => '...']]]
        'credentials_resolver' => null,
        'drivers' => [
            // Example config-driven HTTP driver
            'http_generic' => [
                'type' => 'http',
                'name' => 'http-generic',
                'method' => 'post',
                'url' => env('NOTIFY_SMS_HTTP_URL', ''),
                'body_type' => 'json', // json|form|query
                'fields' => [
                    'to' => 'to',
                    'message' => 'message',
                    'sender' => 'sender',
                ],
                'auth' => [
                    'type' => 'bearer', // bearer|basic|none
                    'token' => env('NOTIFY_SMS_HTTP_TOKEN'),
                    // for basic: username/password
                ],
                'response_keys' => [
                    'id' => 'messageId',
                ],
            ],

            // Taqnyat SMS driver
            'taqnyat' => [
                'type' => 'http',
                'name' => 'taqnyat',
                'method' => 'post',
                'url' => env('TAQNYAT_SMS_URL', 'https://api.taqnyat.sa/v1/messages'),
                'body_type' => 'json',
                'fields' => [
                    'to' => 'recipients',
                    'message' => 'body',
                    'sender' => 'sender',
                ],
                'auth' => [
                    'type' => 'bearer',
                    'token' => env('TAQNYAT_SMS_TOKEN'),
                ],
                'defaults' => [
                    'sender' => env('TAQNYAT_SMS_SENDER'),
                ],
                'response_keys' => [
                    'id' => 'messageId',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business (WBA) Configuration
    |--------------------------------------------------------------------------
    */
    'wba' => [
        'enabled' => env('NOTIFY_WBA_ENABLED', false),
        'default_language' => env('NOTIFY_WBA_LANGUAGE', 'ar'),
        'webhook_middleware' => ['api'],
    ],
];
