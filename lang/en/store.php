<?php

declare(strict_types=1);

return [
    'meta' => [
        'home_title'       => 'HOOR — Modest Denim for Women in Egypt',
        'home_description' => 'Premium modest denim and jeans for women, delivered across Egypt with cash on delivery.',
    ],

    'announcement' => [
        'free_shipping' => 'Free shipping on orders over 500 EGP',
    ],

    'hero' => [
        'label'    => 'Featured collections',
        // Live text over the photograph rather than baked into the image, so it
        // translates, is indexable, and can be edited without a new render.
        'headline'    => 'Modest Style .. Timeless Comfort',
        'headline_ar' => 'أناقة محتشمة .. راحة تدوم',
        'tagline'     => 'Premium Denim Wear for Modern Women',
        'previous' => 'Previous slide',
        'next'     => 'Next slide',
        'go_to'    => 'Go to slide :number',

        'slides' => [
            [
                'eyebrow' => 'New season',
                'title'   => 'Denim, reimagined for modest style',
                'body'    => 'Thoughtfully cut jeans and denim pieces that move with you — designed and delivered across Egypt.',
            ],
            [
                'eyebrow' => 'The skirt edit',
                'title'   => 'Floor-length denim, made to move',
                'body'    => 'Full-length skirts with a concealed walking split, cut from denim that holds its shape.',
            ],
            [
                'eyebrow' => 'Layer up',
                'title'   => 'The jacket that finishes everything',
                'body'    => 'Oversized, softly washed and built to sit over every piece in the range.',
            ],
        ],

        // Retained for the plain hero used before the slider was introduced.
        'eyebrow'  => 'New season',
        'title'    => 'Denim, reimagined for modest style',
        'subtitle' => 'Thoughtfully cut jeans and denim pieces that move with you — designed and delivered across Egypt.',
    ],

    'promise' => [
        'cod'      => ['title' => 'Cash on delivery', 'body' => 'Pay only when your order arrives at your door.'],
        'shipping' => ['title' => 'Nationwide delivery', 'body' => 'We ship to every governorate in Egypt.'],
        'quality'  => ['title' => 'Premium denim',    'body' => 'Durable fabric, considered fit, made to last.'],
        'exchange' => ['title' => 'Easy exchange',    'body' => 'Wrong size? Exchange it without the hassle.'],
    ],

    'categories' => [
        'eyebrow' => 'Shop by category',
        'title'   => 'Find your fit',
        'lead'    => 'From wide leg to floor-length, every piece is cut with modest wear in mind.',
        'count'   => '{1} :count piece|[2,*] :count pieces',
    ],

    'new_arrivals' => [
        'eyebrow' => 'Just landed',
        'title'   => 'New in',
        'lead'    => 'The latest additions to the HOOR range.',
        'view_all'=> 'View all new in',
    ],

    'featured' => [
        'eyebrow' => 'Handpicked',
        'title'   => 'Featured denim',
        'lead'    => 'The pieces our customers keep coming back to.',
        'view_all'=> 'Shop the collection',
    ],

    'collection' => [
        'eyebrow'  => 'The denim edit',
        'title'    => 'Built to be worn every day',
        'body'     => 'Rigid indigo, softened washes and considered cuts — our core denim collection is designed for real Egyptian weather and real everyday wear.',
        'discount' => 'Up to :percent% off selected pieces',
        'cta'      => 'Shop the edit',
    ],

    'why' => [
        'eyebrow' => 'Why HOOR',
        'title'   => 'Denim that respects how you dress',
        'lead'    => 'We started HOOR because modest denim usually means compromising on fit, fabric or price. It should not.',

        'reasons' => [
            'modesty' => [
                'title' => 'Designed modest, not adjusted',
                'body'  => 'Every cut starts from full coverage rather than a standard block altered afterwards.',
            ],
            'fit' => [
                'title' => 'Fit tested on real bodies',
                'body'  => 'Each style is fitted across our full size run, so XS and XXL are both intentional.',
            ],
            'fabric' => [
                'title' => 'Fabric chosen for the heat',
                'body'  => 'Breathable weights and colour-locked washes that survive Egyptian summers and repeated laundering.',
            ],
            'egypt' => [
                'title' => 'Made for Egypt',
                'body'  => 'Priced in EGP, delivered to every governorate, paid for at your door.',
            ],
        ],
    ],

    'quality' => [
        'title' => 'Timeless Denim. Effortless You.',
        'lead'  => 'Crafted for comfort. Designed for every moment.',
        'cta'   => 'Explore the collection',

        'items' => [
            'fabric' => ['title' => 'Premium Fabrics',   'body' => 'Made to last'],
            'soft'   => ['title' => 'Breathable & Soft', 'body' => 'All day comfort'],
            'fit'    => ['title' => 'Flattering Fit',    'body' => 'For every body'],
            'style'  => ['title' => 'Versatile Style',   'body' => 'From day to night'],
        ],
    ],

    'shop' => [
        'title'        => 'Shop all denim',
        'lead'         => 'Every piece in the HOOR range, filtered your way.',
        'results'      => '{0} No products|{1} :count product|[2,*] :count products',
        'filters'      => 'Filters',
        'clear_all'    => 'Clear all',
        'clear'        => 'Clear',
        'apply'        => 'Show :count',
        'close'        => 'Close filters',
        'sort_by'      => 'Sort by',
        'none'         => 'No products match these filters.',
        'none_hint'    => 'Try removing a filter or two.',
        'active'       => 'Active filters',
        'load_more'    => 'Load more',
        'loading'      => 'Loading…',
        'all_loaded'   => "That's everything.",
        'showing'      => 'Showing :count of :total',

        'facets' => [
            'category'     => 'Category',
            'size'         => 'Size',
            'color'        => 'Colour',
            'price'        => 'Price',
            'availability' => 'Availability',
            'min'          => 'Min',
            'max'          => 'Max',
            'new_arrivals' => 'New arrivals',
            'on_sale'      => 'On sale',
            'in_stock'     => 'In stock only',
        ],

        'sorts' => [
            'newest'       => 'Newest',
            'price_asc'    => 'Price: low to high',
            'price_desc'   => 'Price: high to low',
            'name'         => 'Name',
            'best_selling' => 'Best selling',
        ],

        'wishlist' => [
            'add'    => 'Add to wishlist',
            'remove' => 'Remove from wishlist',
        ],

        'colors_count' => '{1} 1 colour|[2,*] :count colours',
    ],

    'product' => [
        'choose_color'   => 'Colour',
        'choose_size'    => 'Size',
        'chosen'         => 'Selected: :value',
        'quantity'       => 'Quantity',
        'add_to_cart'    => 'Add to bag',
        'select_first'   => 'Select a size and colour',
        'sold_out'       => 'Sold out',
        'unavailable'    => 'Unavailable in this colour',
        'only_left'      => '{1} Only 1 left|[2,*] Only :count left',
        'in_stock'       => 'In stock',
        'sku'            => 'SKU',
        'size_guide'     => 'Size guide',
        'size_guide_hint'=> 'Measurements in centimetres. If you are between sizes, we suggest sizing up.',
        'related'        => 'You may also like',
        'gallery_prev'   => 'Previous image',
        'gallery_next'   => 'Next image',
        'view_image'     => 'View image :number',

        'sections' => [
            'description' => 'Description',
            'material'    => 'Fabric & care',
            'shipping'    => 'Shipping & returns',
            'size_guide'  => 'Size guide',
        ],

        'shipping' => [
            'cod'      => 'Cash on delivery anywhere in Egypt — pay when your order arrives.',
            'delivery' => 'Delivery within 2–5 working days depending on your governorate.',
            'returns'  => 'Exchange within 14 days if the size is not right, in original condition.',
        ],

        'size_table' => [
            'size'  => 'Size',
            'waist' => 'Waist',
            'hip'   => 'Hip',
            'length'=> 'Inseam',
        ],
    ],

    'lookbook' => [
        'eyebrow' => 'Lookbook',
        'title'   => 'Styled by HOOR',
        'lead'    => 'See how the collection is worn.',
        'follow'  => 'Follow us on Instagram',
    ],

    'newsletter' => [
        'title'   => 'First look at every drop',
        'lead'    => 'New arrivals, restocks and private offers — before they reach anyone else.',
        'privacy' => 'We only email about HOOR. Unsubscribe any time.',
    ],

    'footer' => [
        'about'             => 'HOOR designs modest denim for women who want comfort without compromise.',
        'shop'              => 'Shop',
        'help'              => 'Help',
        'company'           => 'Company',
        'follow'            => 'Follow us',
        'newsletter'        => 'Join our newsletter',
        'newsletter_hint'   => 'New arrivals and private offers, straight to your inbox.',
        'email_placeholder' => 'Your email address',
        'subscribe'         => 'Subscribe',
        'rights'            => 'All rights reserved.',
        'cod_note'          => 'Cash on delivery across Egypt',
    ],

    'home' => [
        'placeholder_title' => 'The storefront is being prepared',
        'placeholder_body'  => 'Catalog, cart and checkout arrive in the next phases. The brand foundation is live.',
    ],
    'pages' => [
        'about'   => 'About HOOR',
        'contact' => 'Contact us',
    ],

    'contact' => [
        'name'       => 'Your name',
        'email'      => 'Email',
        'phone'      => 'Phone',
        'subject'    => 'Subject',
        'message'    => 'Your message',
        'send'       => 'Send message',
        'reach_hint' => 'Leave an email or a phone number so we can reply.',
        'address'    => 'Address',
        'hours'      => 'Opening hours',
        'sent'       => 'Thank you — we have your message and will reply soon.',
        'throttled'  => 'You have sent several messages already. Please try again in :minutes minutes.',
    ],

    'newsletter_page' => [
        'subscribed' => 'You are on the list. Welcome.',
    ],
    'about' => [
        'eyebrow'  => 'من الدنيم، نبتكر الأناقة المحتشمة',
        'headline' => 'Our Story. Our Stitch.<br>Our Promise.',
        'lead'     => 'HOOR is more than denim. It is a reflection of modesty, strength, and timeless style.',

        'mission' => [
            'eyebrow' => 'Our Mission',
            'body'    => 'To create premium denim wear for modern women who value modest fashion, comfort, and quality. We design every piece to empower you to feel confident, covered, and effortlessly stylish.',
            'pillars' => [
                'quality'   => 'Premium Quality',
                'modest'    => 'Modest by Design',
                'made'      => 'Made for You',
                'timeless'  => 'Timeless Fashion',
            ],
        ],

        'modest' => [
            'eyebrow'  => 'فلسفتنا في الدنيم المحتشم',
            'headline' => 'Modest by Choice.<br>Designed for Life.',
            'body'     => 'At HOOR, modesty is at the heart of every design. We blend contemporary cuts with full coverage, so you never have to choose between style and your values.',
            'features' => [
                'coverage' => ['title' => 'Full Coverage', 'body' => 'Pieces of modest women, comfy body'],
                'flatter'  => ['title' => 'Flattering Fits', 'body' => 'Designed to empower every body'],
                'comfort'  => ['title' => 'Everyday Comfort', 'body' => 'Soft, breathable and easy to wear'],
                'timeless' => ['title' => 'Timeless Style', 'body' => 'Pieces you will love after every season'],
            ],
        ],

        'quality' => [
            'headline' => 'Quality You Can Feel',
            'body'     => 'We use carefully selected denim fabrics and precise craftsmanship to ensure durability, comfort, and a perfect fit that lasts.',
            'points'   => [
                'premium'  => ['title' => 'Premium Denim', 'body' => 'Long Lasting'],
                'expert'   => ['title' => 'Expert', 'body' => 'Craftsmanship'],
                'durable'  => ['title' => 'Durable & Reliable', 'body' => 'Built to Last'],
                'inspect'  => ['title' => 'Carefully', 'body' => 'Inspected'],
            ],
        ],

        'founder' => [
            'eyebrow'  => 'A Note From Our Founder',
            'headline' => 'Our Promise to You',
            'body'     => 'HOOR was born from a simple belief — modesty and style can go hand in hand. We promise continued creating denim pieces that celebrate who you are, with honesty, quality, and care in every stitch.',
            'name'     => 'Nour Alkhatib',
            'quote'    => 'We don\'t just design clothes, we design confidence.',
        ],

        'values' => [
            'eyebrow' => 'Our Values',
            'items'   => [
                'faith'       => ['title' => 'Faith & Modesty', 'body' => 'Rooted in values'],
                'integrity'   => ['title' => 'Integrity', 'body' => 'Honest in every step'],
                'empower'     => ['title' => 'Empowerment', 'body' => 'For the modern woman'],
                'sustain'     => ['title' => 'Sustainability', 'body' => 'Responsibility matters'],
                'community'   => ['title' => 'Community', 'body' => 'Stronger together'],
            ],
        ],

        'follow' => [
            'title'  => 'Follow Our Journey',
            'handle' => '@hoor.denim',
            'cta'    => 'Follow Us',
        ],

        'newsletter' => [
            'title' => 'Stay in the Loop',
            'body'  => 'Be the first to hear about new arrivals, exclusive offers and style inspiration.',
        ],
    ],
    'contact_page' => [
        'headline'    => 'Contact Us',
        'headline_ar' => 'تواصل معنا',
        'lead'        => 'We are here to help you',
        'sub'         => 'Our team is ready to assist you with anything you need.',

        'cards' => [
            'phone' => [
                'title' => 'Phone / WhatsApp',
                'note'  => 'Saturday to Thursday, 10am – 8pm',
            ],
            'email' => [
                'title' => 'Email Us',
                'note'  => 'We reply within 24 hours',
            ],
            'follow' => [
                'title' => 'Follow Us',
                'note'  => 'Updated daily with new looks',
            ],
            'delivery' => [
                'title' => 'Delivery Help',
                'note'  => 'Shipping and delivery questions',
            ],
        ],

        'form' => [
            'title' => 'Send Us a Message',
            'lead'  => 'We would love to hear from you. Fill in the form and we will get back to you as soon as possible.',
        ],

        'faq' => [
            'title' => 'Frequently Asked Questions',
            'empty' => 'No questions have been added yet.',
        ],

        'order_help' => [
            'title'  => 'Need Help with Your Order?',
            'body'   => 'Track your order, start a return or exchange, or get quick answers to common questions.',
            'cta'    => 'Track Your Order',
            'points' => [
                'returns' => ['title' => 'Easy Returns', 'body' => 'Within 14 days of delivery'],
                'payment' => ['title' => 'Cash on Delivery', 'body' => 'Pay when it reaches you'],
                'support' => ['title' => 'Fast Support', 'body' => 'We are here for you'],
            ],
        ],

        'location' => [
            'title'      => 'Our Location',
            'store'      => 'Store Information',
            'directions' => 'Open in Maps',
            'address'    => 'Address',
            'hours'      => 'Opening hours',
        ],
    ],
];
