<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notify Package - English Translations
    |--------------------------------------------------------------------------
    |
    | English translations for the Notify notification center package.
    | Note: This application is Arabic-first. English translations are minimal.
    |
    */

    // General
    'package_name' => 'Notification Center',
    'notifications' => 'Notifications',
    'notification' => 'Notification',

    // Campaigns
    'campaigns' => 'Campaigns',
    'campaign' => 'Campaign',
    'campaign_name' => 'Campaign Name',
    'campaign_type' => 'Campaign Type',
    'campaign_status' => 'Campaign Status',
    'create_campaign' => 'Create Campaign',
    'edit_campaign' => 'Edit Campaign',
    'delete_campaign' => 'Delete Campaign',
    'send_campaign' => 'Send Campaign',
    'schedule_campaign' => 'Schedule Campaign',
    'cancel_campaign' => 'Cancel Campaign',

    // Campaign Types
    'type_direct' => 'Direct',
    'type_topic' => 'Topic',
    'type_broadcast' => 'Broadcast',
    'type_segment' => 'Segment',

    // Campaign Statuses
    'status_draft' => 'Draft',
    'status_scheduled' => 'Scheduled',
    'status_sending' => 'Sending',
    'status_sent' => 'Sent',
    'status_failed' => 'Failed',
    'status_cancelled' => 'Cancelled',

    // Templates
    'templates' => 'Templates',
    'template' => 'Template',
    'template_name' => 'Template Name',
    'create_template' => 'Create Template',
    'edit_template' => 'Edit Template',
    'delete_template' => 'Delete Template',
    'use_template' => 'Use Template',

    // Content
    'title' => 'Title',
    'body' => 'Body',
    'image' => 'Image',
    'image_url' => 'Image URL',
    'action_url' => 'Action URL',
    'payload' => 'Custom Payload',
    'variables' => 'Variables',

    // Devices
    'devices' => 'Devices',
    'device' => 'Device',
    'device_name' => 'Device Name',
    'device_token' => 'Device Token',
    'platform' => 'Platform',
    'platform_ios' => 'iOS',
    'platform_android' => 'Android',
    'platform_web' => 'Web',
    'last_active' => 'Last Active',
    'register_device' => 'Register Device',
    'remove_device' => 'Remove Device',

    // Topics
    'topics' => 'Topics',
    'topic' => 'Topic',
    'topic_name' => 'Topic Name',
    'topic_slug' => 'Topic Slug',
    'topic_description' => 'Topic Description',
    'create_topic' => 'Create Topic',
    'edit_topic' => 'Edit Topic',
    'delete_topic' => 'Delete Topic',
    'subscribers' => 'Subscribers',
    'subscribers_count' => 'Subscribers Count',
    'subscribe' => 'Subscribe',
    'unsubscribe' => 'Unsubscribe',
    'public_topic' => 'Public Topic',

    // Segments
    'segments' => 'Segments',
    'segment' => 'Segment',
    'segment_name' => 'Segment Name',
    'create_segment' => 'Create Segment',
    'edit_segment' => 'Edit Segment',
    'delete_segment' => 'Delete Segment',
    'conditions' => 'Conditions',
    'add_condition' => 'Add Condition',
    'remove_condition' => 'Remove Condition',
    'matching_users' => 'Matching Users',

    // Logs
    'logs' => 'Logs',
    'log' => 'Log',
    'delivery_status' => 'Delivery Status',
    'sent_at' => 'Sent At',
    'delivered_at' => 'Delivered At',
    'opened_at' => 'Opened At',
    'failed_at' => 'Failed At',
    'error_message' => 'Error Message',
    'recipient' => 'Recipient',
    'recipients' => 'Recipients',
    'recipients_count' => 'Recipients Count',

    // Delivery Statuses
    'delivery_pending' => 'Pending',
    'delivery_sent' => 'Sent',
    'delivery_delivered' => 'Delivered',
    'delivery_opened' => 'Opened',
    'delivery_failed' => 'Failed',

    // Channels
    'channels' => 'Channels',
    'channel' => 'Channel',
    'channel_fcm' => 'Firebase Cloud Messaging',
    'channel_whatsapp' => 'WhatsApp',
    'channel_telegram' => 'Telegram',
    'channel_discord' => 'Discord',
    'channel_sms' => 'SMS',

    // Scheduling
    'scheduled_at' => 'Scheduled At',
    'send_now' => 'Send Now',
    'schedule_for_later' => 'Schedule for Later',

    // Testing
    'test_notification' => 'Test Notification',
    'send_test' => 'Send Test',
    'test_sent' => 'Test notification sent',

    // Analytics
    'analytics' => 'Analytics',
    'total_sent' => 'Total Sent',
    'total_delivered' => 'Total Delivered',
    'total_opened' => 'Total Opened',
    'delivery_rate' => 'Delivery Rate',
    'open_rate' => 'Open Rate',

    // Actions
    'save' => 'Save',
    'cancel' => 'Cancel',
    'delete' => 'Delete',
    'edit' => 'Edit',
    'create' => 'Create',
    'send' => 'Send',
    'preview' => 'Preview',
    'duplicate' => 'Duplicate',
    'refresh' => 'Refresh',

    // Messages
    'campaign_created' => 'Campaign created successfully',
    'campaign_updated' => 'Campaign updated successfully',
    'campaign_deleted' => 'Campaign deleted successfully',
    'campaign_sent' => 'Campaign sent successfully',
    'campaign_scheduled' => 'Campaign scheduled successfully',
    'campaign_cancelled' => 'Campaign cancelled successfully',
    'template_created' => 'Template created successfully',
    'template_updated' => 'Template updated successfully',
    'template_deleted' => 'Template deleted successfully',
    'device_registered' => 'Device registered successfully',
    'device_removed' => 'Device removed successfully',
    'topic_created' => 'Topic created successfully',
    'topic_updated' => 'Topic updated successfully',
    'topic_deleted' => 'Topic deleted successfully',
    'subscribed' => 'Subscribed successfully',
    'unsubscribed' => 'Unsubscribed successfully',

    // Errors
    'error_sending' => 'Error sending notification',
    'error_invalid_token' => 'Invalid device token',
    'error_no_recipients' => 'No recipients found',
    'error_template_not_found' => 'Template not found',
    'error_topic_not_found' => 'Topic not found',
    'error_segment_empty' => 'Segment has no matching users',
    'error_unauthorized' => 'You are not authorized for this action',
    'error_token_invalid' => 'Invalid device token',
    'error_token_unregistered' => 'Device is unregistered',
    'error_quota_exceeded' => 'Send quota exceeded',

    // Confirmations
    'confirm_delete' => 'Are you sure you want to delete?',
    'confirm_send' => 'Are you sure you want to send?',
    'confirm_cancel' => 'Are you sure you want to cancel?',

    // Validation (legacy)
    'required' => 'This field is required',
    'invalid_format' => 'Invalid format',
    'max_length' => 'Maximum length exceeded',

    // Validation messages for form requests
    'validation' => [
        'token_required' => 'Device token is required',
        'token_invalid' => 'Device token is invalid',
        'token_already_registered' => 'This device is already registered',
        'platform_required' => 'Platform is required',
        'platform_invalid' => 'Platform is invalid. Valid options: ios, android, web',
        'device_name_too_long' => 'Device name is too long',
        'no_devices_registered' => 'No devices registered',
        'topic_not_subscribable' => 'Cannot subscribe to this topic',
        'not_subscribed_to_topic' => 'You are not subscribed to this topic',
    ],

    // API response messages
    'api' => [
        'device_registered' => 'Device registered successfully',
        'device_updated' => 'Device updated successfully',
        'device_deleted' => 'Device deleted successfully',
        'subscribed_to_topic' => 'Subscribed to topic successfully',
        'unsubscribed_from_topic' => 'Unsubscribed from topic successfully',
        'notification_read' => 'Notification marked as read',
    ],

    // Notification sending messages
    'notification_sent' => 'Notification sent successfully',
    'notification_failed' => 'Failed to send notification',
    'no_devices' => 'No devices registered for user',
    'no_recipients' => 'No recipients specified',
    'invalid_message' => 'Invalid notification message',

    // Send results
    'send_success' => ':count notifications sent successfully',
    'send_partial' => ':success sent, :failed failed',
    'send_all_failed' => 'All notifications failed to send',

    // Broadcast
    'broadcast_sent' => 'Broadcast sent to all users',
    'topic_sent' => 'Notification sent to topic :topic',

    // Template messages
    'template_not_found' => 'Template not found',
    'template_inactive' => 'Template is inactive',
    'template_variable_missing' => 'Variable :variable is missing',

    // Logging messages
    'log_created' => 'Notification log created',
    'log_status_pending' => 'Pending',
    'log_status_sent' => 'Sent',
    'log_status_delivered' => 'Delivered',
    'log_status_opened' => 'Opened',
    'log_status_failed' => 'Failed',
    'log_pruned' => ':count old logs pruned',
    'log_retention_days' => 'Logs older than :days days will be automatically deleted',

    // Scheduled notifications
    'no_scheduled_notifications' => 'No scheduled notifications due.',
    'scheduled_notifications_dispatched' => 'Dispatched :count scheduled notifications for processing.',
    'cannot_cancel_notification' => 'Cannot cancel notification that has already been sent or cancelled',
    'scheduled_notification_sent' => 'Scheduled notification sent successfully',
    'scheduled_notification_failed' => 'Scheduled notification failed to send',
    'scheduled_notification_cancelled' => 'Scheduled notification was cancelled',
    'scheduled_notification_skipped' => 'Scheduled notification skipped',

    // Test notifications
    'test_requires_auth' => 'Test notification requires authentication',

    // Segment messages
    'segment_not_found' => 'Segment not found',
    'segment_inactive' => 'Segment is inactive',
    'no_users_in_segment' => 'No users match the segment conditions',
    'segment_created' => 'Segment created successfully',
    'segment_updated' => 'Segment updated successfully',
    'segment_deleted' => 'Segment deleted successfully',
    'segment_count_refreshed' => 'User count refreshed',
];
