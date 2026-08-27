<?php

declare(strict_types=1);

return [

    'title'    => 'Track your order',
    'subtitle' => 'Enter your order number and the phone number you ordered with. No account needed.',

    'fields' => [
        'number'      => 'Order number',
        'number_hint' => 'It looks like HOOR-2026-000042 and is on your confirmation.',
        'phone'       => 'Phone number',
        'phone_hint'  => 'The number the courier calls.',
    ],

    'submit' => 'Find my order',

    'errors' => [
        // One message for both "no such order" and "wrong phone": telling them
        // apart would confirm which order numbers exist.
        'not_found' => 'We could not find an order with that number and phone number.',
        'throttled' => 'Too many attempts. Please try again in :seconds seconds.',
    ],

    'show' => [
        'title'     => 'Order :number',
        'placed'    => 'Placed on :date',
        'progress'  => 'Progress',
        'items'     => 'What you ordered',
        'delivery'  => 'Delivery address',
        'totals'    => 'Summary',
        'another'   => 'Track another order',
        'help'      => 'Something wrong? Call us and quote your order number.',
    ],
];
