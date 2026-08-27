<?php

declare(strict_types=1);

return [

    'title' => 'إعدادات الموقع',
    'saved' => 'تم حفظ الإعدادات.',

    'groups' => [
        'contact'      => 'بيانات التواصل',
        'social'       => 'روابط التواصل الاجتماعي',
        'homepage'     => 'الصفحة الرئيسية',
        'about'        => 'صفحة من نحن',
        'contact_page' => 'صفحة اتصلي بنا',
        'newsletter'   => 'النشرة البريدية',
        'seo'          => 'تحسين محركات البحث',
    ],

    'networks' => [
        'instagram' => 'إنستجرام',
        'facebook'  => 'فيسبوك',
        'tiktok'    => 'تيك توك',
        'whatsapp'  => 'واتساب',
    ],

    'fields' => [
        'contact.phone'      => 'هاتف المتجر',
        'contact.whatsapp'   => 'رقم واتساب',
        'contact.email'      => 'البريد الإلكتروني',
        'contact.address_ar' => 'العنوان (بالعربية)',
        'contact.address_en' => 'العنوان (بالإنجليزية)',
        'contact.hours_ar'   => 'مواعيد العمل (بالعربية)',
        'contact.hours_en'   => 'مواعيد العمل (بالإنجليزية)',
        'contact.hours_alt_ar' => 'سطر مواعيد ثانٍ (بالعربية)',
        'contact.hours_alt_en' => 'سطر مواعيد ثانٍ (بالإنجليزية)',
        'contact.map_url'      => 'رابط الخريطة',
        'contact.response_ar'  => 'مدة الرد (بالعربية)',
        'contact.response_en'  => 'مدة الرد (بالإنجليزية)',

        'social.instagram' => 'رابط إنستجرام',
        'social.facebook'  => 'رابط فيسبوك',
        'social.tiktok'    => 'رابط تيك توك',

        'homepage.show_hero'         => 'سلايدر الواجهة',
        'homepage.show_categories'   => 'أقسام المنتجات',
        'homepage.show_new_arrivals' => 'وصل حديثًا',
        'homepage.show_promo_banner' => 'بانر إعلاني',
        'homepage.show_featured'     => 'المجموعة المميزة',
        'homepage.show_lookbook'     => 'لوك بوك',
        'homepage.show_benefits'     => 'شريط المميزات',
        'homepage.show_why_hoor'     => 'لماذا حور',
        'homepage.show_newsletter'   => 'الاشتراك في النشرة',

        'homepage.featured_category_id' => 'المجموعة المميزة',
        'homepage.featured_title_ar'    => 'عنوان المجموعة (بالعربية)',
        'homepage.featured_title_en'    => 'عنوان المجموعة (بالإنجليزية)',

        'about.heading_ar' => 'العنوان (بالعربية)',
        'about.heading_en' => 'العنوان (بالإنجليزية)',
        'about.intro_ar'   => 'المقدمة (بالعربية)',
        'about.intro_en'   => 'المقدمة (بالإنجليزية)',
        'about.body_ar'    => 'النص (بالعربية)',
        'about.body_en'    => 'النص (بالإنجليزية)',
        'about.values_ar'  => 'قيمنا (بالعربية)',
        'about.values_en'  => 'قيمنا (بالإنجليزية)',
        'about.image_path' => 'صورة الصفحة',

        'contact_page.heading_ar' => 'العنوان (بالعربية)',
        'contact_page.heading_en' => 'العنوان (بالإنجليزية)',
        'contact_page.intro_ar'   => 'المقدمة (بالعربية)',
        'contact_page.intro_en'   => 'المقدمة (بالإنجليزية)',
        'contact_page.show_form'  => 'إظهار نموذج التواصل',

        'newsletter.enabled'    => 'تفعيل الاشتراك في النشرة',
        'newsletter.heading_ar' => 'العنوان (بالعربية)',
        'newsletter.heading_en' => 'العنوان (بالإنجليزية)',
        'newsletter.body_ar'    => 'النص (بالعربية)',
        'newsletter.body_en'    => 'النص (بالإنجليزية)',

        'seo.title_ar'       => 'العنوان الافتراضي (بالعربية)',
        'seo.title_en'       => 'العنوان الافتراضي (بالإنجليزية)',
        'seo.description_ar' => 'الوصف الافتراضي (بالعربية)',
        'seo.description_en' => 'الوصف الافتراضي (بالإنجليزية)',
        'seo.og_image_path'  => 'صورة المشاركة',
        'seo.keywords'       => 'الكلمات المفتاحية',
        'seo.noindex'        => 'منع محركات البحث من فهرسة الموقع',
    ],

    'hints' => [
        'contact.whatsapp' => 'الرقم فقط — نحن نبني رابط واتساب تلقائيًا.',
        'contact.phone'    => 'يظهر في التذييل وفي صفحة التواصل.',
        'contact.map_url'  => 'الصقي رابط خرائط جوجل لعنوانك. يفتح المؤشر الرابط في تبويب جديد.',
        'seo.description_en' => 'حوالي ١٥٥ حرفًا تظهر كاملة في جوجل.',
        'seo.description_ar' => 'حوالي ١٥٥ حرفًا تظهر كاملة في جوجل.',
        'seo.noindex'      => 'فعّليه لموقع التجربة فقط، واتركيه مغلقًا في الموقع الفعلي.',
        'homepage.featured_category_id' => 'اتركيه فارغًا لعرض المنتجات المميزة.',
    ],
];
