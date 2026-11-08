<?php
/**
 * OpenFrame — Arabic translations (RTL)
 * All user-facing strings used by the framework layout and helpers.
 */

return [
    /* ---- About Modal ---- */
    'about.title'     => 'عن أوبن فريم',
    'about.button'    => 'حول',

    /* ---- DevTools ---- */
    'devtool.enable'  => 'تمكين أدوات المطور',
    'devtool.disable' => 'تعطيل أدوات المطور',
    'about.app_name'  => 'اسم التطبيق',
    'about.version'   => 'الإصدار',
    'about.framework' => 'الإطار',
    'about.description' => 'إطار عمل PHP خفيف الوزن ومبني على الوحدات، يستخدم PHP عادية وبوتستراب 5. لا يعتمد على مقposer، ولا محرك قوالب، ولا ORM، ولا خطوات بناء.',
    'about.author'    => 'المؤلف',
    'about.github'    => 'مستودع جيت هب',
    'about.license'   => 'الرخصة',

    /* ---- Application ---- */
    'app.name'        => 'أوبن فريم',
    'app.version'     => 'الإصدار',
    'app.powered_by'  => 'مدعوم من أوبن فريم',
    'app.created_by'  => 'أنشئه أوبن فريم',
    'app.github'      => 'جيت هب',
    'app.php'         => 'بي إتش بي',

    /* ---- Common ---- */
    'common.home'             => 'الرئيسية',
    'common.dashboard'        => 'لوحة القيادة',
    'common.settings'         => 'الإعدادات',
    'common.system'           => 'إعدادات النظام',
    'common.full_name'        => 'الاسم الكامل',
    'common.level'            => 'المستوى',
    'common.created'          => 'تاريخ الإنشاء',
    'common.actions'          => 'الإجراءات',
    'common.save'             => 'حفظ',
    'common.cancel'           => 'إلغاء',
    'common.delete'           => 'حذف',
    'common.edit'             => 'تعديل',
    'common.create'           => 'إنشاء',
    'common.update'           => 'تحديث',
    'common.close'            => 'إغلاق',
    'common.actions'          => 'الإجراءات',
    'common.name'             => 'الاسم',
    'common.description'      => 'الوصف',
    'common.version'          => 'الإصدار',
    'common.created'          => 'تاريخ الإنشاء',
    'common.module'           => 'الوحدة',
    'common.modules'          => 'الوحدات',
    'common.no_modules'       => 'لا توجد وحدات مثبتة.',
    'common.profile'          => 'ملفي الشخصي',
    'common.disabled'         => 'معطل',
    'common.enabled'          => 'مفعل',
    'general'                 => 'عام',

    /* ---- Navigation ---- */
    'nav.home'                => 'الرئيسية',
    'nav.modules'             => 'الوحدات',
    'nav.sidemenu'            => 'القائمة',
    'nav.language'            => 'اللغة',
    'nav.english'             => 'الإنجليزية',
    'nav.arabic'              => 'العربية',

    /* ---- Messages / Notifications ---- */
    'msg.saved'               => 'تم الحفظ بنجاح.',
    'msg.deleted'             => 'تم الحذف بنجاح.',
    'msg.created'             => 'تم الإنشاء بنجاح.',
    'msg.updated'             => 'تم التحديث بنجاح.',
    'msg.error'               => 'حدث خطأ.',
    'msg.not_found'           => 'غير موجود.',
    'msg.access_denied'       => 'تم رفض الوصول.',
    'msg.invalid_token'       => 'رمز النموذج غير صالح أو منتهي الصلاحية. ارجع، أعد تحميل الصفحة وحاول مرة أخرى.',
    'msg.wrong_credentials'   => 'اسم المستخدم أو كلمة المرور غير صحيحة.',
    'msg.welcome_back'        => 'أهلاً بك مرة أخرى.',

    /* ---- Errors ---- */
    'err.404_title'           => 'الوحدة غير موجودة',
    'err.404_message'         => 'لا توجد وحدة فعالة تطابق المسار المطلوب.',
    'err.404_view_title'      => 'عرض الوحدة مفقود',
    'err.404_view_message'    => 'المسار لا يحتوي على ملف index.php.',
    'err.db_title'            => 'فشل اتصال قاعدة البيانات',
    'err.db_message'          => 'تعذر الاتصال بقاعدة البيانات. تحقق من بيانات الاعتماد فيincludes/config.php.',

    /* ---- OTP / TOTP ---- */
    'otp.title'               => 'المصادقة الثنائية',
    'otp.subtitle'            => 'أدخل الرمز المكوّن من 6 أرقام من تطبيق المصادقة.',
    'otp.setup_title'         => 'إعداد تطبيق المصادقة',
    'otp.setup_help'          => 'امسح رمز الاستجابة السريعة بتطبيق Google Authenticator أو Microsoft Authenticator أو تطبيق مشابه، ثم أدخل الرمز المكوّن من 6 أرقام.',
    'otp.secret_label'        => 'أو أدخل هذا المفتاح يدوياً',
    'otp.code'                => 'رمز المصادقة',
    'otp.verify'              => 'تحقق',
    'otp.cancel'              => 'العودة لتسجيل الدخول',
    'otp.invalid'             => 'الرمز غير صالح أو منتهي. حاول مرة أخرى.',
    'otp.locked'              => 'محاولات كثيرة جداً. سجّل الدخول مرة أخرى.',
    'otp.reset'               => 'إعادة تعيين المصادقة',
    'otp.reset_ok'            => 'تم إعادة تعيين المصادقة. يجب على المستخدم مسح رمز جديد عند تسجيل الدخول التالي.',

    /* ---- Login module ---- */
    'login.title'             => 'تسجيل الدخول',
    'login.username'          => 'اسم المستخدم',
    'login.password'          => 'كلمة المرور',
    'login.signin'            => 'تسجيل الدخول',
    'login.signout'           => 'تسجيل الخروج',
    'login.welcome'           => 'أهلاً بك مرة أخرى.',
    'login.invalid'           => 'اسم المستخدم أو كلمة المرور غير صحيحة.',
    'login.required'          => 'الرجاء إدخال اسم المستخدم وكلمة المرور.',
    'login.access_denied'     => 'تم رفض الوصول.',

    /* ---- Home module ---- */
    'home.welcome'            => 'مرحباً بك في',
    'home.subtitle'           => 'إطار عمل PHP خفيف الوزن مبني على PHP عادية ובوتستراب.',
    'home.get_started'        => 'البدء',
    'home.browse_modules'     => 'استعرض الوحدات',
    'home.app_info'           => 'معلومات التطبيق',
    'home.app_name'           => 'اسم التطبيق',
    'home.app_path'           => 'مسار التطبيق',
    'home.installed_modules'  => 'الوحدات المثبتة',
    'home.how_routing'        => 'كيف يعمل التوجيه',
    'home.create_module'      => 'إنشاء وحدة',
    'home.steps_1'            => 'يقوم index.php بتشغيل الإعداد (التكوين → الوحدات → المسارات).',
    'home.steps_2'            => 'يقوم routes.php ببناء جدول المسارات من الوحدات المفعلة.',
    'home.steps_3'            => 'current_module() يقرأ ?module= وي التحقق منه.',
    'home.steps_4'            => 'load_module() ينفذ عرض الوحدة بين الرأس والتذييل.',
    'home.file_info_php'      => 'modules/<الوحدة>/info.php',
    'home.file_index_php'     => 'modules/<الوحدة>/index.php',
    'home.appears_in_navbar'  => 'تظهر تلقائياً في شريط التنقل وعند ?module=<الوحدة>.',
];
