<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notify Package - Filament English Translations
    |--------------------------------------------------------------------------
    |
    | English translations for Filament admin panel resources.
    |
    */

    // Navigation
    'navigation_group' => 'Notifications',

    // Topics
    'topics' => [
        'navigation_label' => 'Topics',
        'model_label' => 'Topic',
        'plural_model_label' => 'Topics',
        'fields' => [
            'name' => 'Topic Name',
            'slug' => 'Slug',
            'slug_help' => 'Unique identifier used in FCM',
            'description' => 'Description',
            'is_public' => 'Public',
            'is_public_help' => 'Public topics can be subscribed to by users',
            'is_default' => 'Default',
            'is_default_help' => 'Default topics are automatically subscribed to by users',
            'subscriber_count' => 'Subscriber Count',
        ],
        'subscribers' => 'Subscribers',
        'subscriber_name' => 'Subscriber Name',
        'synced' => 'Synced',
        'subscribed_at' => 'Subscribed At',
        'no_subscribers' => 'No Subscribers',
        'no_subscribers_description' => 'No users have subscribed to this topic yet',
        'unsubscribe_heading' => 'Unsubscribe',
        'unsubscribe_confirmation' => 'Are you sure you want to unsubscribe this user from the topic?',
    ],

    // Logs
    'logs' => [
        'navigation_label' => 'Logs',
        'model_label' => 'Log',
        'plural_model_label' => 'Logs',
        'sections' => [
            'notification' => 'Notification',
            'delivery' => 'Delivery',
            'error' => 'Error',
            'technical' => 'Technical Details',
        ],
        'fields' => [
            'title' => 'Title',
            'body' => 'Body',
            'channel' => 'Channel',
            'status' => 'Status',
            'user' => 'User',
            'sent_at' => 'Sent At',
            'error_message' => 'Error Message',
            'is_test' => 'Test',
            'external_id' => 'External ID',
            'error_code' => 'Error Code',
            'delivered_at' => 'Delivered At',
            'opened_at' => 'Opened At',
            'device_token' => 'Device Token',
        ],
        'statuses' => [
            'pending' => 'Pending',
            'sent' => 'Sent',
            'delivered' => 'Delivered',
            'opened' => 'Opened',
            'failed' => 'Failed',
        ],
        'filters' => [
            'status' => 'Status',
            'channel' => 'Channel',
            'from' => 'From Date',
            'until' => 'Until Date',
            'is_test' => 'Test',
        ],
        'channels' => [
            'fcm' => 'Firebase',
            'whatsapp' => 'WhatsApp',
            'sms' => 'SMS',
            'telegram' => 'Telegram',
        ],
    ],

    // Device Tokens
    'device_tokens' => [
        'navigation_label' => 'Devices',
        'model_label' => 'Device',
        'plural_model_label' => 'Devices',
        'fields' => [
            'device_name' => 'Device Name',
            'platform' => 'Platform',
            'user' => 'User',
            'last_active_at' => 'Last Active',
        ],
        'platforms' => [
            'ios' => 'iOS',
            'android' => 'Android',
            'web' => 'Web',
        ],
    ],

    // Settings
    'settings' => [
        'navigation_label' => 'Notification Settings',
        'page_title' => 'Notification Settings',
        'actions' => [
            'save' => 'Save Settings',
        ],
        'tabs' => [
            'fcm' => 'Firebase',
            'logging' => 'Logging',
            'queue' => 'Queue',
            'rate_limiting' => 'Rate Limiting',
            'topics' => 'Topics',
        ],
        'sections' => [
            'fcm' => 'Firebase Cloud Messaging Settings',
            'fcm_description' => 'Configure Firebase credentials for sending notifications',
            'logging' => 'Logging Settings',
            'logging_description' => 'Configure how notifications are logged',
            'queue' => 'Queue Settings',
            'queue_description' => 'Configure notification queue processing',
            'rate_limiting' => 'Rate Limits',
            'rate_limiting_description' => 'Configure notification rate limits',
            'topics' => 'Topic Settings',
            'topics_description' => 'Configure default topic behavior',
        ],
        'fields' => [
            'fcm_enabled' => 'Enable Firebase',
            'fcm_enabled_help' => 'Enable or disable Firebase notifications',
            'fcm_credentials_json' => 'Credentials JSON',
            'fcm_credentials_json_help' => 'Paste the contents of your Firebase service account JSON file',
            'fcm_credentials_json_placeholder' => '{"type": "service_account", ...}',
            'fcm_status' => 'Configuration Status',
            'logging_enabled' => 'Enable Logging',
            'logging_enabled_help' => 'Log all notification send operations',
            'log_retention_days' => 'Log Retention',
            'log_retention_days_help' => 'Number of days to keep logs before deletion',
            'log_store_payload' => 'Store Payload',
            'log_store_payload_help' => 'Store full notification payload in logs',
            'days' => 'days',
            'queue_connection' => 'Queue Connection',
            'queue_connection_help' => 'Queue connection used for notification processing',
            'queue_name' => 'Queue Name',
            'queue_name_help' => 'Queue name for notification jobs',
            'rate_limit_per_minute' => 'Rate Per Minute',
            'rate_limit_per_minute_help' => 'Maximum notifications per minute',
            'rate_limit_per_user_per_hour' => 'Rate Per User Per Hour',
            'rate_limit_per_user_per_hour_help' => 'Maximum notifications per user per hour',
            'per_minute' => 'per minute',
            'per_hour' => 'per hour',
            'auto_subscribe_to_defaults' => 'Auto-subscribe to Defaults',
            'auto_subscribe_to_defaults_help' => 'Automatically subscribe new devices to default topics',
        ],
        'fcm_configured' => 'Firebase Configured',
        'fcm_not_configured' => 'Firebase Not Configured',
        'notifications' => [
            'saved' => 'Settings saved successfully',
        ],
        'errors' => [
            'load_failed' => 'Failed to load settings',
            'save_failed' => 'Failed to save settings',
            'invalid_credentials' => 'Invalid JSON credentials',
        ],
    ],

    // Common
    'common' => [
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'yes' => 'Yes',
        'no' => 'No',
        'all' => 'All',
        'none' => 'None',
        'search' => 'Search',
        'filter' => 'Filter',
        'export' => 'Export',
        'import' => 'Import',
        'refresh' => 'Refresh',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'create' => 'Create',
        'view' => 'View',
        'back' => 'Back',
    ],
];
