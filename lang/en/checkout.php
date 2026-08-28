<?php

declare(strict_types=1);

return [
    'title'    => 'Checkout',
    'subtitle' => 'Cash on delivery — pay when your order arrives.',

    'sections' => [
        'contact'  => 'Your details',
        'delivery' => 'Delivery address',
        'notes'    => 'Order notes',
        'payment'  => 'Payment',
        'summary'  => 'Order summary',
    ],

    'fields' => [
        'full_name'      => 'Full name',
        'phone'          => 'Phone number',
        'phone_hint'     => 'The courier will call this number.',
        'email'          => 'Email address',
        'email_hint'     => 'We send your order confirmation here. No account needed.',
        'phone_alt'      => 'Second phone (optional)',
        'phone_alt_hint' => 'Used if we cannot reach the first.',
        'governorate'    => 'Governorate',
        'area'           => 'Area',
        'area_optional'  => 'Area (optional)',
        'address'        => 'Address',
        'address_hint'   => 'Street, building, floor and flat.',
        'landmark'       => 'Landmark (optional)',
        'landmark_hint'  => 'A nearby shop or well-known place helps the courier.',
        'notes'          => 'Notes (optional)',
        'notes_hint'     => 'Anything we should know about your order or delivery.',
    ],

    'payment' => [
        'cod'       => 'Cash on delivery',
        'cod_note'  => 'You pay the courier in cash when your order arrives. No card details are needed.',
        'only'      => 'This is currently our only payment method.',
    ],

    'summary' => [
        'subtotal'     => 'Subtotal',
        'discount'     => 'Discount',
        'shipping'     => 'Shipping',
        'shipping_hint'=> 'Choose a governorate to see the fee',
        'total'        => 'Total',
        'delivery_in'  => 'Delivery in :days working days',
        'items'        => '{1} 1 item|[2,*] :count items',
    ],

    'place_order' => 'Place order',
    'placing'     => 'Placing your order…',

    'success' => [
        'title'      => 'Thank you — your order is confirmed',
        'lead'       => 'We have received your order and will call you shortly to confirm it.',
        'number'     => 'Order number',
        'name'       => 'Name',
        'total'      => 'Total to pay',
        'status'     => 'Status',
        'next_title' => 'What happens next',

        'next' => [
            'call'    => 'Our team will call you on :phone to confirm your order.',
            'prepare' => 'We prepare and pack your pieces.',
            'deliver' => 'Your order arrives in :days working days at the address below.',
            'pay'     => 'You pay :total in cash to the courier on delivery.',
        ],

        'keep_number'  => 'Keep your order number — you will need it to track your order.',
        'track'        => 'Track your order',
        'continue'     => 'Continue shopping',
        'delivering_to'=> 'Delivering to',
    ],

    'errors' => [
        'cart_empty'    => 'Your bag is empty.',
        'area_mismatch' => 'That area does not belong to the governorate you chose.',
        'line_sold_out' => ':name (:variant) sold out while you were checking out and was removed.',
        'line_short'    => 'Only :available left of :name (:variant). Please adjust your bag.',
        'failed'        => 'We could not place your order. Please try again.',
    ],

    'attributes' => [
        'full_name'      => 'full name',
        'phone'          => 'phone number',
        'email'          => 'email address',
        'phone_alt'      => 'second phone',
        'governorate_id' => 'governorate',
        'area_id'        => 'area',
        'address'        => 'address',
        'landmark'       => 'landmark',
        'notes'          => 'notes',
    ],
    'welcome_offer' => [
        'title'   => 'Save :percent% on this order',
        'body'    => 'Sign in with Google and we will take :percent% off — it is applied automatically, no code to type.',
        'saving'  => 'You save :amount',
        'cta'     => 'Sign in with Google',
        'applied' => 'Your :percent% welcome discount has been applied.',
        'note'    => 'One welcome discount per customer.',
    ],
];
