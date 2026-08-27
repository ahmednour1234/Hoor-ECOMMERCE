<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Areas
|--------------------------------------------------------------------------
|
| Districts are seeded ONLY for Cairo, Giza and Alexandria, where the names are
| well established and unlikely to be wrong. Every other governorate ships with
| no areas at all and is populated by the admin, which is what the brief asked
| for — inventing district lists for 24 more governorates would put unverified
| data in front of customers.
|
| A governorate with no areas still works: the area field is optional at
| checkout, and the governorate fee applies.
|
| Fees are omitted here, so every area inherits its governorate's fee. The
| admin overrides only the districts that genuinely cost more to reach.
|
*/

return [
    'C' => [
        ['name_en' => 'Nasr City',            'name_ar' => 'مدينة نصر'],
        ['name_en' => 'Heliopolis',           'name_ar' => 'مصر الجديدة'],
        ['name_en' => 'Maadi',                'name_ar' => 'المعادي'],
        ['name_en' => 'New Cairo',            'name_ar' => 'القاهرة الجديدة'],
        ['name_en' => 'Fifth Settlement',     'name_ar' => 'التجمع الخامس'],
        ['name_en' => 'Downtown',             'name_ar' => 'وسط البلد'],
        ['name_en' => 'Zamalek',              'name_ar' => 'الزمالك'],
        ['name_en' => 'Shubra',               'name_ar' => 'شبرا'],
        ['name_en' => 'Ain Shams',            'name_ar' => 'عين شمس'],
        ['name_en' => 'El Marg',              'name_ar' => 'المرج'],
        ['name_en' => 'Helwan',               'name_ar' => 'حلوان'],
        ['name_en' => 'El Salam',             'name_ar' => 'السلام'],
        ['name_en' => 'Al Rehab',             'name_ar' => 'الرحاب'],
        ['name_en' => 'Madinaty',             'name_ar' => 'مدينتي'],
        ['name_en' => 'Obour City',           'name_ar' => 'مدينة العبور'],
        ['name_en' => 'Badr City',            'name_ar' => 'مدينة بدر'],
    ],

    'GZ' => [
        ['name_en' => 'Dokki',                'name_ar' => 'الدقي'],
        ['name_en' => 'Mohandessin',          'name_ar' => 'المهندسين'],
        ['name_en' => 'Agouza',               'name_ar' => 'العجوزة'],
        ['name_en' => 'Haram',                'name_ar' => 'الهرم'],
        ['name_en' => 'Faisal',               'name_ar' => 'فيصل'],
        ['name_en' => '6th of October',       'name_ar' => 'السادس من أكتوبر'],
        ['name_en' => 'Sheikh Zayed',         'name_ar' => 'الشيخ زايد'],
        ['name_en' => 'Imbaba',               'name_ar' => 'إمبابة'],
        ['name_en' => 'Boulaq El Dakrour',    'name_ar' => 'بولاق الدكرور'],
        ['name_en' => 'Kerdasa',              'name_ar' => 'كرداسة'],
        ['name_en' => 'Badrashin',            'name_ar' => 'البدرشين'],
        ['name_en' => 'Saft El Laban',        'name_ar' => 'صفط اللبن'],
    ],

    'ALX' => [
        ['name_en' => 'Montaza',              'name_ar' => 'المنتزه'],
        ['name_en' => 'Sidi Gaber',           'name_ar' => 'سيدي جابر'],
        ['name_en' => 'Smouha',               'name_ar' => 'سموحة'],
        ['name_en' => 'Miami',                'name_ar' => 'ميامي'],
        ['name_en' => 'Sporting',             'name_ar' => 'سبورتنج'],
        ['name_en' => 'Stanley',              'name_ar' => 'ستانلي'],
        ['name_en' => 'Gleem',                'name_ar' => 'جليم'],
        ['name_en' => 'Agami',                'name_ar' => 'العجمي'],
        ['name_en' => 'Borg El Arab',         'name_ar' => 'برج العرب'],
        ['name_en' => 'Amreya',               'name_ar' => 'العامرية'],
        ['name_en' => 'Mandara',              'name_ar' => 'المندرة'],
        ['name_en' => 'Asafra',               'name_ar' => 'العصافرة'],
    ],
];
