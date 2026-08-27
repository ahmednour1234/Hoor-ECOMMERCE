<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Egyptian governorates
|--------------------------------------------------------------------------
|
| All 27 governorates, with their official Arabic names and the usual English
| transliterations. Codes follow ISO 3166-2:EG.
|
| `fee` is in whole EGP and is a STARTING POINT ONLY — the admin owns these
| numbers and is expected to set them against real courier pricing. They are
| banded by delivery difficulty rather than invented per governorate:
|
|   Greater Cairo   — same-day courier reach
|   Delta / Canal   — next-day trunk routes
|   Upper Egypt     — longer trunk routes
|   Frontier        — sparse coverage, longest lead times
|
| Areas are deliberately NOT listed here. Only the three governorates whose
| district names are well established ship with areas (see EgyptAreaSeeder);
| everywhere else the admin populates them, which is what the brief asked for.
|
*/

return [
    // ------------------------------------------------------------ Greater Cairo
    ['code' => 'C',  'name_en' => 'Cairo',           'name_ar' => 'القاهرة',        'fee' => 45, 'days' => [1, 3]],
    ['code' => 'GZ', 'name_en' => 'Giza',            'name_ar' => 'الجيزة',         'fee' => 45, 'days' => [1, 3]],
    ['code' => 'KB', 'name_en' => 'Qalyubia',        'name_ar' => 'القليوبية',      'fee' => 50, 'days' => [2, 4]],

    // ------------------------------------------------------- Alexandria & coast
    ['code' => 'ALX', 'name_en' => 'Alexandria',     'name_ar' => 'الإسكندرية',     'fee' => 50, 'days' => [2, 4]],
    ['code' => 'MT',  'name_en' => 'Matrouh',        'name_ar' => 'مطروح',          'fee' => 90, 'days' => [4, 7]],
    ['code' => 'BH',  'name_en' => 'Beheira',        'name_ar' => 'البحيرة',        'fee' => 60, 'days' => [2, 5]],

    // ------------------------------------------------------------------- Delta
    ['code' => 'DK', 'name_en' => 'Dakahlia',        'name_ar' => 'الدقهلية',       'fee' => 55, 'days' => [2, 5]],
    ['code' => 'SHR','name_en' => 'Sharqia',         'name_ar' => 'الشرقية',        'fee' => 55, 'days' => [2, 5]],
    ['code' => 'GH', 'name_en' => 'Gharbia',         'name_ar' => 'الغربية',        'fee' => 55, 'days' => [2, 5]],
    ['code' => 'MNF','name_en' => 'Monufia',         'name_ar' => 'المنوفية',       'fee' => 55, 'days' => [2, 5]],
    ['code' => 'KFS','name_en' => 'Kafr El Sheikh',  'name_ar' => 'كفر الشيخ',      'fee' => 60, 'days' => [3, 5]],
    ['code' => 'DT', 'name_en' => 'Damietta',        'name_ar' => 'دمياط',          'fee' => 60, 'days' => [3, 5]],

    // ------------------------------------------------------------- Canal & Sinai
    ['code' => 'PTS','name_en' => 'Port Said',       'name_ar' => 'بورسعيد',        'fee' => 60, 'days' => [3, 5]],
    ['code' => 'IS', 'name_en' => 'Ismailia',        'name_ar' => 'الإسماعيلية',    'fee' => 60, 'days' => [3, 5]],
    ['code' => 'SUZ','name_en' => 'Suez',            'name_ar' => 'السويس',         'fee' => 60, 'days' => [3, 5]],
    ['code' => 'SIN','name_en' => 'North Sinai',     'name_ar' => 'شمال سيناء',     'fee' => 95, 'days' => [5, 8]],
    ['code' => 'JS', 'name_en' => 'South Sinai',     'name_ar' => 'جنوب سيناء',     'fee' => 95, 'days' => [5, 8]],

    // ------------------------------------------------------------- Upper Egypt
    ['code' => 'BNS','name_en' => 'Beni Suef',       'name_ar' => 'بني سويف',       'fee' => 65, 'days' => [3, 6]],
    ['code' => 'FYM','name_en' => 'Faiyum',          'name_ar' => 'الفيوم',         'fee' => 65, 'days' => [3, 6]],
    ['code' => 'MN', 'name_en' => 'Minya',           'name_ar' => 'المنيا',         'fee' => 70, 'days' => [3, 6]],
    ['code' => 'AST','name_en' => 'Asyut',           'name_ar' => 'أسيوط',          'fee' => 70, 'days' => [4, 6]],
    ['code' => 'SHG','name_en' => 'Sohag',           'name_ar' => 'سوهاج',          'fee' => 75, 'days' => [4, 7]],
    ['code' => 'KN', 'name_en' => 'Qena',            'name_ar' => 'قنا',            'fee' => 75, 'days' => [4, 7]],
    ['code' => 'LX', 'name_en' => 'Luxor',           'name_ar' => 'الأقصر',         'fee' => 80, 'days' => [4, 7]],
    ['code' => 'ASN','name_en' => 'Aswan',           'name_ar' => 'أسوان',          'fee' => 85, 'days' => [5, 8]],

    // ---------------------------------------------------------------- Frontier
    ['code' => 'BA', 'name_en' => 'Red Sea',         'name_ar' => 'البحر الأحمر',   'fee' => 90, 'days' => [4, 8]],
    ['code' => 'WAD','name_en' => 'New Valley',      'name_ar' => 'الوادي الجديد',  'fee' => 95, 'days' => [5, 9]],
];
