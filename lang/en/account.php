<?php

declare(strict_types=1);

return [

    'title'   => 'My account',
    'welcome' => 'Welcome back, :name',

    'nav' => [
        'overview'  => 'Overview',
        'profile'   => 'Profile',
        'orders'    => 'Orders',
        'addresses' => 'Addresses',
        'wishlist'  => 'Wishlist',
        'returns'   => 'Returns',
        'logout'    => 'Sign out',
    ],

    'summary' => [
        'orders'       => 'Orders placed',
        'addresses'    => 'Saved addresses',
        'wishlist'     => 'Saved products',
        'open_returns' => 'Open returns',
        'recent'       => 'Recent orders',
        'view_all'     => 'View all',
    ],

    'pieces_count' => '{0} No pieces|{1} 1 piece|[2,*] :count pieces',

    'orders' => [
        'title'      => 'My orders',
        'empty'      => 'You have not placed an order yet.',
        'empty_cta'  => 'Start shopping',
        'number'     => 'Order',
        'placed'     => 'Placed',
        'items'      => 'Items',
        'total'      => 'Total',
        'view'       => 'View order',
        'back'       => 'Back to orders',
        'track'      => 'Track this order',
        'request_return' => 'Return or exchange',
        'returns_on_order' => 'Requests on this order',
    ],

    'addresses' => [
        'title'       => 'My addresses',
        'empty'       => 'You have no saved addresses.',
        'add'         => 'Add an address',
        'edit'        => 'Edit address',
        'label'       => 'Label',
        'label_hint'  => 'A name you will recognise, like Home or Work.',
        'default'     => 'Default',
        'make_default'=> 'Make default',
        'default_hint'=> 'Used to prefill checkout.',
        'saved'       => 'Address saved.',
        'updated'     => 'Address updated.',
        'deleted'     => 'Address removed.',
        'default_set' => 'Default address updated.',
        'confirm_delete' => 'Remove this address?',
    ],

    'wishlist' => [
        'title'   => 'My wishlist',
        'empty'   => 'You have not saved anything yet.',
        'browse'  => 'Browse the shop',
        'added'   => 'Saved to your wishlist.',
        'removed' => 'Removed from your wishlist.',
        'remove'  => 'Remove',
        'save'    => 'Save for later',
    ],
];
