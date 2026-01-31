<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notify Package - Filament Arabic Translations
    |--------------------------------------------------------------------------
    |
    | Arabic translations for Filament admin panel resources.
    |
    */

    // Navigation
    'navigation_group' => 'الإشعارات',

    // Topics
    'topics' => [
        'navigation_label' => 'المواضيع',
        'model_label' => 'موضوع',
        'plural_model_label' => 'المواضيع',
        'fields' => [
            'name' => 'اسم الموضوع',
            'slug' => 'المعرف',
            'slug_help' => 'معرف فريد يستخدم في FCM',
            'description' => 'الوصف',
            'is_public' => 'عام',
            'is_public_help' => 'المواضيع العامة يمكن للمستخدمين الاشتراك فيها',
            'is_default' => 'افتراضي',
            'is_default_help' => 'المواضيع الافتراضية يشترك فيها المستخدمون تلقائياً',
            'subscriber_count' => 'عدد المشتركين',
        ],
        'subscribers' => 'المشتركون',
        'subscriber_name' => 'اسم المشترك',
        'synced' => 'متزامن',
        'subscribed_at' => 'تاريخ الاشتراك',
        'no_subscribers' => 'لا يوجد مشتركون',
        'no_subscribers_description' => 'لم يشترك أي مستخدم في هذا الموضوع بعد',
        'unsubscribe_heading' => 'إلغاء الاشتراك',
        'unsubscribe_confirmation' => 'هل أنت متأكد من إلغاء اشتراك هذا المستخدم من الموضوع؟',
    ],

    // Logs
    'logs' => [
        'navigation_label' => 'السجلات',
        'model_label' => 'سجل',
        'plural_model_label' => 'السجلات',
        'sections' => [
            'notification' => 'الإشعار',
            'delivery' => 'التسليم',
            'error' => 'الخطأ',
            'technical' => 'التفاصيل التقنية',
        ],
        'fields' => [
            'title' => 'العنوان',
            'body' => 'المحتوى',
            'channel' => 'القناة',
            'status' => 'الحالة',
            'user' => 'المستخدم',
            'sent_at' => 'تاريخ الإرسال',
            'error_message' => 'رسالة الخطأ',
            'is_test' => 'اختباري',
            'external_id' => 'المعرف الخارجي',
            'error_code' => 'رمز الخطأ',
            'delivered_at' => 'تاريخ التسليم',
            'opened_at' => 'تاريخ الفتح',
            'device_token' => 'رمز الجهاز',
        ],
        'statuses' => [
            'pending' => 'قيد الانتظار',
            'sent' => 'تم الإرسال',
            'delivered' => 'تم التسليم',
            'opened' => 'تم الفتح',
            'failed' => 'فشل',
        ],
        'filters' => [
            'status' => 'الحالة',
            'channel' => 'القناة',
            'from' => 'من تاريخ',
            'until' => 'إلى تاريخ',
            'is_test' => 'اختباري',
        ],
        'channels' => [
            'fcm' => 'Firebase',
            'whatsapp' => 'واتساب',
            'sms' => 'رسائل قصيرة',
            'telegram' => 'تيليجرام',
            'wba' => 'واتساب بزنس',
            'discord' => 'ديسكورد',
        ],
    ],

    // Device Tokens
    'device_tokens' => [
        'navigation_label' => 'الأجهزة',
        'model_label' => 'جهاز',
        'plural_model_label' => 'الأجهزة',
        'fields' => [
            'device_name' => 'اسم الجهاز',
            'platform' => 'المنصة',
            'user' => 'المستخدم',
            'last_active_at' => 'آخر نشاط',
        ],
        'platforms' => [
            'ios' => 'iOS',
            'android' => 'أندرويد',
            'web' => 'ويب',
        ],
    ],

    // Settings
    'settings' => [
        'navigation_label' => 'إعدادات الإشعارات',
        'page_title' => 'إعدادات الإشعارات',
        'actions' => [
            'save' => 'حفظ الإعدادات',
        ],
        'tabs' => [
            'fcm' => 'Firebase',
            'logging' => 'السجلات',
            'queue' => 'قائمة الانتظار',
            'rate_limiting' => 'حدود المعدل',
            'topics' => 'المواضيع',
            'sms' => 'الرسائل القصيرة',
            'wba' => 'واتساب بزنس',
        ],
        'sections' => [
            'fcm' => 'إعدادات Firebase Cloud Messaging',
            'fcm_description' => 'قم بتكوين بيانات اعتماد Firebase لإرسال الإشعارات',
            'logging' => 'إعدادات السجلات',
            'logging_description' => 'تكوين كيفية تسجيل الإشعارات',
            'queue' => 'إعدادات قائمة الانتظار',
            'queue_description' => 'تكوين معالجة قائمة انتظار الإشعارات',
            'rate_limiting' => 'حدود المعدل',
            'rate_limiting_description' => 'تكوين حدود معدل الإشعارات',
            'topics' => 'إعدادات المواضيع',
            'topics_description' => 'تكوين سلوك المواضيع الافتراضي',
            'sms' => 'إعدادات الرسائل القصيرة',
            'sms_description' => 'تفعيل الرسائل القصيرة وتحديد المزود والبيانات',
            'wba' => 'إعدادات واتساب بزنس',
            'wba_description' => 'تفعيل قناة واتساب بزنس. إذا تم إعداد wba-filament مسبقاً فاترك الحقول فارغة وسيتم استخدامها تلقائياً.',
        ],
        'fields' => [
            'fcm_enabled' => 'تفعيل Firebase',
            'fcm_enabled_help' => 'تفعيل أو تعطيل إشعارات Firebase',
            'fcm_credentials_json' => 'بيانات اعتماد JSON',
            'fcm_credentials_json_help' => 'الصق محتويات ملف JSON لحساب الخدمة من Firebase',
            'fcm_credentials_json_placeholder' => '{"type": "service_account", ...}',
            'fcm_status' => 'حالة التكوين',
            'logging_enabled' => 'تفعيل السجلات',
            'logging_enabled_help' => 'تسجيل جميع عمليات إرسال الإشعارات',
            'log_retention_days' => 'مدة الاحتفاظ بالسجلات',
            'log_retention_days_help' => 'عدد الأيام للاحتفاظ بالسجلات قبل الحذف',
            'log_store_payload' => 'تخزين البيانات',
            'log_store_payload_help' => 'تخزين بيانات الإشعار الكاملة في السجلات',
            'days' => 'يوم',
            'queue_connection' => 'اتصال قائمة الانتظار',
            'queue_connection_help' => 'اتصال قائمة الانتظار المستخدم لمعالجة الإشعارات',
            'queue_name' => 'اسم قائمة الانتظار',
            'queue_name_help' => 'اسم قائمة الانتظار لوظائف الإشعارات',
            'rate_limit_per_minute' => 'الحد لكل دقيقة',
            'rate_limit_per_minute_help' => 'الحد الأقصى للإشعارات في الدقيقة',
            'rate_limit_per_user_per_hour' => 'الحد لكل مستخدم في الساعة',
            'rate_limit_per_user_per_hour_help' => 'الحد الأقصى للإشعارات لكل مستخدم في الساعة',
            'per_minute' => 'في الدقيقة',
            'per_hour' => 'في الساعة',
            'auto_subscribe_to_defaults' => 'الاشتراك التلقائي في المواضيع الافتراضية',
            'auto_subscribe_to_defaults_help' => 'اشتراك الأجهزة الجديدة تلقائياً في المواضيع الافتراضية',
            'sms_enabled' => 'تفعيل الرسائل القصيرة',
            'sms_enabled_help' => 'تفعيل إرسال الرسائل القصيرة عبر المزود المحدد',
            'sms_default_driver' => 'مزود الرسائل الافتراضي',
            'sms_default_driver_help' => 'يُستخدم عند عدم تحديد مزود في toSms()',
            'sms_credentials_json' => 'بيانات اعتماد الرسائل (JSON)',
            'sms_credentials_json_help' => 'بيانات اعتماد لكل مزود مثل {"http_generic":{"token":"...","sender":"Notify"}}',
            'sms_credentials_json_placeholder' => '{"http_generic":{"token":"abc","sender":"Notify"}}',
            'wba_enabled' => 'تفعيل واتساب بزنس',
            'wba_enabled_help' => 'تفعيل الإرسال عبر واتساب بزنس (wba-filament)',
            'wba_default_language' => 'اللغة الافتراضية',
            'wba_default_language_help' => 'لغة القوالب عند عدم تحديدها',
            'wba_credentials' => 'بيانات اعتماد واتساب',
            'wba_credentials_json_help' => 'اختياري. إذا كان wba-filament معد مسبقاً فاتركه فارغاً، وإلا أدخل المفاتيح: page_token, phone_number_id, app_secret, verify_token',
        ],
        'fcm_configured' => 'تم تكوين Firebase',
        'fcm_not_configured' => 'لم يتم تكوين Firebase',
        'notifications' => [
            'saved' => 'تم حفظ الإعدادات بنجاح',
        ],
        'errors' => [
            'load_failed' => 'فشل تحميل الإعدادات',
            'save_failed' => 'فشل حفظ الإعدادات',
            'invalid_credentials' => 'بيانات اعتماد JSON غير صالحة',
            'invalid_sms_credentials' => 'بيانات اعتماد الرسائل غير صالحة',
            'invalid_wba_credentials' => 'بيانات اعتماد واتساب غير صالحة',
        ],
    ],

    // Common
    'common' => [
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'yes' => 'نعم',
        'no' => 'لا',
        'all' => 'الكل',
        'none' => 'لا شيء',
        'search' => 'بحث',
        'filter' => 'تصفية',
        'export' => 'تصدير',
        'import' => 'استيراد',
        'refresh' => 'تحديث',
        'save' => 'حفظ',
        'cancel' => 'إلغاء',
        'delete' => 'حذف',
        'edit' => 'تعديل',
        'create' => 'إنشاء',
        'view' => 'عرض',
        'back' => 'رجوع',
    ],
];
