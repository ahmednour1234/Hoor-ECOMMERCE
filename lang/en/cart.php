<?php

declare(strict_types=1);

return [
    'title'    => 'Your bag',
    'subtitle' => 'Items are held in your bag, not reserved — checkout to secure them.',

    'empty'        => 'Your bag is empty.',
    'empty_hint'   => 'Once you add something, it will appear here.',
    'continue'     => 'Continue shopping',
    'checkout'     => 'Proceed to checkout',
    'clear'        => 'Empty bag',
    'clear_confirm'=> 'Remove everything from your bag?',
    'remove'       => 'Remove',
    'quantity'     => 'Quantity',
    'update'       => 'Update',
    'item'         => 'Item',
    'price'        => 'Price',
    'total'        => 'Total',

    'summary' => [
        'title'      => 'Order summary',
        'subtotal'   => 'Subtotal',
        'savings'    => 'You save',
        'shipping'   => 'Shipping',
        'shipping_at_checkout' => 'Calculated at checkout',
        'total'      => 'Total',
        'total_note' => 'Shipping is added at checkout once you choose your governorate.',
        'cod'        => 'Cash on delivery — pay when your order arrives.',
    ],

    'coupon' => [
        'label'       => 'Discount code',
        'placeholder' => 'Enter your code',
        'apply'       => 'Apply',
        'coming_soon' => 'Discount codes are coming soon.',
    ],

    'messages' => [
        'added'           => ':name (:variant) was added to your bag.',
        'updated'         => 'Your bag was updated.',
        'removed'         => ':name was removed from your bag.',
        'removed_generic' => 'The item was removed from your bag.',
        'cleared'         => 'Your bag is now empty.',
    ],

    /*
    | Notices raised while loading the cart, when what the customer is holding
    | no longer matches what the catalog can supply.
    */
    'notices' => [
        'an_item'  => 'An item',
        'removed'  => ':name is no longer available and was removed from your bag.',
        'sold_out' => ':name (:variant) has sold out and was removed from your bag.',
        'reduced'  => 'Only :count left of :name (:variant), so your quantity was reduced.',
        'added_capped' => ':name (:variant) — only :count available, so that is what your bag now holds.',
        'title'    => 'Some items changed',
    ],

    'errors' => [
        'unavailable'         => 'This product is no longer available.',
        'invalid_combination' => 'That size and colour combination is not available.',
        'out_of_stock'        => 'That combination is out of stock.',
        'insufficient_stock'  => 'Only :count left in that combination.',
        'select_options'      => 'Please choose a size and colour first.',
        'network'             => 'Something went wrong. Please try again.',
        'cart_full'           => 'Your bag is full. Remove something before adding more.',
        'already_holding_all' => 'Your bag already holds all :count available.',
        'unavailable_lines'   => 'Some items are unavailable. Adjust them before checking out.',
    ],
];
