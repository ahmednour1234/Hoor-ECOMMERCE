<?php

declare(strict_types=1);

return [

    'title' => 'Site settings',
    'saved' => 'Settings saved.',

    'groups' => [
        'contact'      => 'Contact details',
        'social'       => 'Social links',
        'homepage'     => 'Homepage',
        'about'        => 'About page',
        'contact_page' => 'Contact page',
        'newsletter'   => 'Newsletter',
        'seo'          => 'SEO',
    ],

    'networks' => [
        'instagram' => 'Instagram',
        'facebook'  => 'Facebook',
        'tiktok'    => 'TikTok',
        'whatsapp'  => 'WhatsApp',
    ],

    'fields' => [
        'contact.phone'      => 'Store phone',
        'contact.whatsapp'   => 'WhatsApp number',
        'contact.email'      => 'Email address',
        'contact.address_ar' => 'Address (Arabic)',
        'contact.address_en' => 'Address (English)',
        'contact.hours_ar'   => 'Opening hours (Arabic)',
        'contact.hours_en'   => 'Opening hours (English)',
        'contact.hours_alt_ar' => 'Second hours line (Arabic)',
        'contact.hours_alt_en' => 'Second hours line (English)',
        'contact.map_url'      => 'Map link',
        'contact.response_ar'  => 'Reply time (Arabic)',
        'contact.response_en'  => 'Reply time (English)',

        'social.instagram' => 'Instagram URL',
        'social.facebook'  => 'Facebook URL',
        'social.tiktok'    => 'TikTok URL',

        'homepage.show_hero'         => 'Hero slider',
        'homepage.show_categories'   => 'Category tiles',
        'homepage.show_new_arrivals' => 'New arrivals',
        'homepage.show_promo_banner' => 'Promotional banner',
        'homepage.show_featured'     => 'Featured collection',
        'homepage.show_lookbook'     => 'Lookbook',
        'homepage.show_benefits'     => 'Benefits strip',
        'homepage.show_why_hoor'     => 'Why HOOR',
        'homepage.show_newsletter'   => 'Newsletter signup',

        'homepage.featured_category_id' => 'Featured collection',
        'homepage.featured_title_ar'    => 'Featured heading (Arabic)',
        'homepage.featured_title_en'    => 'Featured heading (English)',

        'about.heading_ar' => 'Heading (Arabic)',
        'about.heading_en' => 'Heading (English)',
        'about.intro_ar'   => 'Introduction (Arabic)',
        'about.intro_en'   => 'Introduction (English)',
        'about.body_ar'    => 'Body (Arabic)',
        'about.body_en'    => 'Body (English)',
        'about.values_ar'  => 'What we stand for (Arabic)',
        'about.values_en'  => 'What we stand for (English)',
        'about.image_path' => 'About image',

        'contact_page.heading_ar' => 'Heading (Arabic)',
        'contact_page.heading_en' => 'Heading (English)',
        'contact_page.intro_ar'   => 'Introduction (Arabic)',
        'contact_page.intro_en'   => 'Introduction (English)',
        'contact_page.show_form'  => 'Show the contact form',

        'newsletter.enabled'    => 'Newsletter signup enabled',
        'newsletter.heading_ar' => 'Heading (Arabic)',
        'newsletter.heading_en' => 'Heading (English)',
        'newsletter.body_ar'    => 'Body (Arabic)',
        'newsletter.body_en'    => 'Body (English)',

        'seo.title_ar'       => 'Default title (Arabic)',
        'seo.title_en'       => 'Default title (English)',
        'seo.description_ar' => 'Default description (Arabic)',
        'seo.description_en' => 'Default description (English)',
        'seo.og_image_path'  => 'Share image',
        'seo.keywords'       => 'Keywords',
        'seo.noindex'        => 'Ask search engines not to index this site',
    ],

    'hints' => [
        'contact.whatsapp' => 'Just the number — the WhatsApp link is built for you.',
        'contact.phone'    => 'Shown in the footer and on the contact page.',
        'contact.map_url'  => 'Paste the Google Maps link for your address. The pin opens it in a new tab.',
        'seo.description_en' => 'Around 155 characters shows in full on Google.',
        'seo.description_ar' => 'Around 155 characters shows in full on Google.',
        'seo.noindex'      => 'Switch on for a staging site. Leave off in production.',
        'homepage.featured_category_id' => 'Leave empty to show whatever is marked featured.',
    ],
];
