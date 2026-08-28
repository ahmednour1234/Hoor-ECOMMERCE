<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    */

    'brand' => [
        'name_en' => env('HOOR_NAME_EN', 'HOOR'),
        'name_ar' => env('HOOR_NAME_AR', 'حور'),
        'email'   => env('HOOR_EMAIL', 'info@hoor.eg'),
        'phone'   => env('HOOR_PHONE', '+20 100 000 0000'),
        'whatsapp'=> env('HOOR_WHATSAPP', '+20 100 000 0000'),
        'social'  => [
            'instagram' => env('HOOR_INSTAGRAM', 'https://instagram.com/hoor'),
            'facebook'  => env('HOOR_FACEBOOK', 'https://facebook.com/hoor'),
            'tiktok'    => env('HOOR_TIKTOK', 'https://tiktok.com/@hoor'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Commerce
    |--------------------------------------------------------------------------
    |
    | HOOR operates exclusively in Egypt with Cash on Delivery only.
    |
    */

    'currency' => [
        'code'      => 'EGP',
        'symbol_en' => 'EGP',
        'symbol_ar' => 'ج.م',
        'decimals'  => 2,
    ],

    'country' => [
        'code'    => 'EG',
        'name_en' => 'Egypt',
        'name_ar' => 'مصر',
        'dial'    => '+20',
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    |
    | Every supported locale declares its text direction, native label and the
    | HTML `lang` attribute value. The array order drives the language switcher.
    |
    */

    'locales' => [
        'en' => [
            'name'      => 'English',
            'native'    => 'English',
            'direction' => 'ltr',
            'html_lang' => 'en',
            'flag'      => 'EN',
        ],
        'ar' => [
            'name'      => 'Arabic',
            'native'    => 'العربية',
            'direction' => 'rtl',
            'html_lang' => 'ar',
            'flag'      => 'ع',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    */

    'media' => [
        'disk'       => env('HOOR_MEDIA_DISK', 'public'),
        'max_upload' => (int) env('HOOR_MAX_UPLOAD_KB', 4096),
        'paths'      => [
            'products' => 'products',
            'banners'  => 'banners',
            'sliders'  => 'sliders',
            'category' => 'categories',
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Returns and exchanges
    |--------------------------------------------------------------------------
    |
    | How long after delivery a customer may ask to send something back, and
    | how many separate requests one order may carry. Both are business policy
    | rather than code, so they live here.
    |
    */

    'returns' => [
        'window_days'          => (int) env('HOOR_RETURN_WINDOW_DAYS', 14),
        'max_open_per_order'   => (int) env('HOOR_MAX_OPEN_RETURNS', 3),
    ],
    /*
    |--------------------------------------------------------------------------
    | Welcome offer
    |--------------------------------------------------------------------------
    |
    | The discount offered to a guest at checkout for signing in. The banner
    | reads its terms from the coupon itself, so changing the campaign in the
    | admin changes what the banner promises — the two cannot disagree.
    |
    | Blank the code to remove the banner entirely.
    |
    */

    'welcome_offer' => [
        'code' => env('HOOR_WELCOME_CODE', 'WELCOME5'),
    ],
];
