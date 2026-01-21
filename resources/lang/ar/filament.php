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

    // Campaigns
    'campaigns' => [
        'navigation_label' => 'الحملات',
        'model_label' => 'حملة',
        'plural_model_label' => 'الحملات',
        'sections' => [
            'basic' => 'المعلومات الأساسية',
            'content' => 'المحتوى',
            'targeting' => 'الاستهداف',
            'schedule' => 'الجدولة',
            'statistics' => 'الإحصائيات',
            'results' => 'النتائج',
        ],
        'fields' => [
            'name' => 'اسم الحملة',
            'template' => 'القالب',
            'segment' => 'الشريحة',
            'title' => 'العنوان',
            'body' => 'المحتوى',
            'image_url' => 'رابط الصورة',
            'action_url' => 'رابط الإجراء',
            'status' => 'الحالة',
            'type' => 'النوع',
            'channels' => 'القنوات',
            'scheduled_at' => 'موعد الإرسال',
            'sent_at' => 'تاريخ الإرسال',
            'success_count' => 'عدد النجاح',
            'failure_count' => 'عدد الفشل',
            'sent_count' => 'عدد المرسل',
            'delivered_count' => 'عدد المُسَلَّم',
            'failed_count' => 'عدد الفشل',
            'recipient_count' => 'عدد المستلمين',
            'send_immediately' => 'إرسال فوري',
            'payload' => 'البيانات الإضافية',
            'template_help' => 'اختر قالباً لتعبئة المحتوى تلقائياً أو أدخل المحتوى يدوياً',
        ],
        'statuses' => [
            'draft' => 'مسودة',
            'scheduled' => 'مجدول',
            'sending' => 'جاري الإرسال',
            'sent' => 'تم الإرسال',
            'failed' => 'فشل',
            'cancelled' => 'ملغى',
        ],
        'types' => [
            'direct' => 'مباشر',
            'topic' => 'موضوع',
            'broadcast' => 'بث عام',
            'segment' => 'شريحة',
        ],
        'actions' => [
            'send' => 'إرسال',
            'send_confirm' => 'تأكيد الإرسال',
            'send_description' => 'سيتم إرسال الإشعار إلى :count مستلم. هل تريد المتابعة؟',
            'schedule' => 'جدولة',
            'cancel' => 'إلغاء',
            'duplicate' => 'تكرار',
        ],
        'notifications' => [
            'sent' => 'تم إرسال الحملة',
            'sent_body' => 'نجح: :success، فشل: :failure',
            'scheduled' => 'تم جدولة الحملة',
            'cancelled' => 'تم إلغاء الحملة',
            'failed' => 'فشل إرسال الحملة',
        ],
    ],

    // Templates
    'templates' => [
        'navigation_label' => 'القوالب',
        'model_label' => 'قالب',
        'plural_model_label' => 'القوالب',
        'sections' => [
            'basic' => 'المعلومات الأساسية',
            'content' => 'المحتوى',
            'variables' => 'المتغيرات',
        ],
        'fields' => [
            'name' => 'اسم القالب',
            'slug' => 'المعرف',
            'title' => 'العنوان',
            'body' => 'المحتوى',
            'image_url' => 'رابط الصورة',
            'variables' => 'المتغيرات',
            'is_active' => 'نشط',
            'title_help' => 'يمكنك استخدام المتغيرات مثل :example',
            'body_help' => 'المتغيرات المتاحة: {user.name}, {user.email}, {app_name}, {date}',
            'variables_help' => 'حدد القيم الافتراضية للمتغيرات المستخدمة في القالب',
            'variable_key' => 'اسم المتغير',
            'variable_default' => 'القيمة الافتراضية',
        ],
        'actions' => [
            'preview' => 'معاينة',
            'test' => 'إرسال تجريبي',
            'add_variable' => 'إضافة متغير',
        ],
    ],

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
        ],
    ],

    // Segments
    'segments' => [
        'navigation_label' => 'الشرائح',
        'model_label' => 'شريحة',
        'plural_model_label' => 'الشرائح',
        'sections' => [
            'basic' => 'المعلومات الأساسية',
            'conditions' => 'شروط الاستهداف',
        ],
        'fields' => [
            'name' => 'اسم الشريحة',
            'slug' => 'المعرف',
            'description' => 'الوصف',
            'is_active' => 'نشط',
            'cached_count' => 'عدد المستخدمين',
            'cached_at' => 'تاريخ آخر تحديث',
        ],
        'conditions' => 'الشروط',
        'condition' => 'شرط',
        'new_condition' => 'شرط جديد',
        'group' => 'مجموعة',
        'and_group' => 'مجموعة (و)',
        'or_group' => 'مجموعة (أو)',
        'field' => 'الحقل',
        'filter_type' => 'نوع الفلتر',
        'operator' => 'العامل',
        'value' => 'القيمة',
        'group_operator' => 'عامل المجموعة',
        'add_condition' => 'إضافة شرط',
        'add_group' => 'إضافة مجموعة',
        'types' => [
            'text' => 'نص',
            'number' => 'رقم',
            'date' => 'تاريخ',
            'set' => 'مجموعة',
        ],
        'operators' => [
            'and' => 'و',
            'or' => 'أو',
        ],
        'filter_operators' => [
            'equals' => 'يساوي',
            'notEqual' => 'لا يساوي',
            'contains' => 'يحتوي',
            'notContains' => 'لا يحتوي',
            'startsWith' => 'يبدأ بـ',
            'endsWith' => 'ينتهي بـ',
            'blank' => 'فارغ',
            'notBlank' => 'غير فارغ',
            'greaterThan' => 'أكبر من',
            'greaterThanOrEqual' => 'أكبر من أو يساوي',
            'lessThan' => 'أصغر من',
            'lessThanOrEqual' => 'أصغر من أو يساوي',
            'inRange' => 'في النطاق',
        ],
        'user_fields' => [
            'id' => 'المعرف',
            'full_name' => 'الاسم الكامل',
            'email' => 'البريد الإلكتروني',
            'phone' => 'الهاتف',
            'gender' => 'الجنس',
            'city' => 'المدينة',
            'created_at' => 'تاريخ التسجيل',
        ],
        'actions' => [
            'refresh_count' => 'تحديث العدد',
            'preview_users' => 'معاينة المستخدمين',
        ],
        'notifications' => [
            'count_refreshed' => 'تم تحديث عدد المستخدمين: :count',
        ],
        'not_calculated' => 'لم يتم الحساب',
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
        'navigation_label' => 'الإعدادات',
        'page_title' => 'إعدادات الإشعارات',

        'tabs' => [
            'fcm' => 'Firebase',
            'logging' => 'السجلات',
            'queue' => 'الطوابير',
            'rate_limiting' => 'تحديد المعدل',
            'campaign' => 'الحملات',
        ],

        'sections' => [
            'fcm' => 'إعدادات Firebase Cloud Messaging',
            'fcm_description' => 'إعدادات الاتصال بخدمة Firebase لإرسال الإشعارات',
            'logging' => 'إعدادات السجلات',
            'logging_description' => 'التحكم في تسجيل الإشعارات وفترة الاحتفاظ',
            'queue' => 'إعدادات الطوابير',
            'queue_description' => 'تكوين اتصال الطوابير لمعالجة الإشعارات',
            'rate_limiting' => 'تحديد المعدل',
            'rate_limiting_description' => 'تحديد عدد الإشعارات المسموح بها',
            'campaign' => 'إعدادات الحملات',
            'campaign_description' => 'إعدادات إرسال الحملات والمواضيع الافتراضية',
        ],

        'fields' => [
            'fcm_enabled' => 'تفعيل Firebase',
            'fcm_enabled_help' => 'تفعيل أو تعطيل إرسال الإشعارات عبر Firebase',
            'fcm_credentials_json' => 'بيانات الاعتماد',
            'fcm_credentials_json_help' => 'الصق محتوى ملف service account JSON من Firebase Console',
            'fcm_credentials_json_placeholder' => 'الصق محتوى ملف JSON هنا...',
            'fcm_status' => 'حالة الإعداد',

            'logging_enabled' => 'تفعيل التسجيل',
            'logging_enabled_help' => 'تسجيل جميع الإشعارات المرسلة في قاعدة البيانات',
            'log_retention_days' => 'فترة الاحتفاظ بالسجلات',
            'log_retention_days_help' => 'عدد الأيام للاحتفاظ بسجلات الإشعارات قبل الحذف التلقائي',
            'log_store_payload' => 'تخزين البيانات الكاملة',
            'log_store_payload_help' => 'تخزين البيانات الكاملة للإشعار (قد يزيد حجم قاعدة البيانات)',
            'days' => 'يوم',

            'queue_connection' => 'اتصال الطابور',
            'queue_connection_help' => 'اسم اتصال الطابور المستخدم (مثل: redis, database, sync)',
            'queue_name' => 'اسم الطابور',
            'queue_name_help' => 'اسم الطابور لمعالجة الإشعارات',

            'rate_limit_per_minute' => 'الحد الأقصى في الدقيقة',
            'rate_limit_per_minute_help' => 'الحد الأقصى للإشعارات المرسلة في الدقيقة (اتركه فارغاً لعدم التحديد)',
            'rate_limit_per_user_per_hour' => 'الحد الأقصى لكل مستخدم في الساعة',
            'rate_limit_per_user_per_hour_help' => 'الحد الأقصى للإشعارات المرسلة لكل مستخدم في الساعة',
            'per_minute' => 'في الدقيقة',
            'per_hour' => 'في الساعة',

            'auto_subscribe_to_defaults' => 'الاشتراك التلقائي في المواضيع الافتراضية',
            'auto_subscribe_to_defaults_help' => 'اشتراك المستخدمين الجدد تلقائياً في المواضيع المحددة كافتراضية',
            'campaign_batch_size' => 'حجم الدفعة',
            'campaign_batch_size_help' => 'عدد الإشعارات المرسلة في كل دفعة عند إرسال الحملات',
            'campaign_retry_attempts' => 'عدد محاولات إعادة الإرسال',
            'campaign_retry_attempts_help' => 'عدد مرات إعادة المحاولة عند فشل إرسال الإشعار',
        ],

        'fcm_configured' => 'Firebase مُعد بشكل صحيح',
        'fcm_not_configured' => 'Firebase غير مُعد - أدخل بيانات الاعتماد أدناه',

        'actions' => [
            'save' => 'حفظ الإعدادات',
        ],

        'notifications' => [
            'saved' => 'تم حفظ الإعدادات بنجاح',
        ],

        'errors' => [
            'load_failed' => 'فشل تحميل الإعدادات',
            'save_failed' => 'فشل حفظ الإعدادات',
            'invalid_credentials' => 'بيانات الاعتماد غير صالحة - تأكد من وجود project_id و private_key',
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
