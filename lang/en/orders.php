<?php

declare(strict_types=1);

return [
    'status' => [
        'pending'            => 'Pending confirmation',
        'confirmed'          => 'Confirmed',
        'preparing'          => 'Preparing',
        'ready_for_shipping' => 'Ready for shipping',
        'shipped'            => 'Shipped',
        'out_for_delivery'   => 'Out for delivery',
        'delivered'          => 'Delivered',
        'cancelled'          => 'Cancelled',
        'delivery_failed'    => 'Delivery failed',
        'returned'           => 'Returned',
    ],

    'payment' => [
        'cash_on_delivery' => 'Cash on delivery',
    ],

    'history' => [
        'system' => 'System',
        'placed' => 'Order placed by the customer.',
    ],
    'errors' => [
        'invalid_transition'  => 'An order that is :from cannot be moved to :to.',
        'restock_unavailable' => 'Reinstating this order needs stock that is no longer available (:sku).',
    ],

    'fields' => [
        'status' => 'status',
        'note'   => 'note',
    ],

    'messages' => [
        'status_updated' => 'Order :number is now :status.',
    ],

    'admin' => [
        'title'       => 'Orders',
        'all'         => 'All orders',
        'search'      => 'Search',
        'search_hint' => 'Order number, customer name, phone or SKU',
        'from'        => 'From',
        'to'          => 'To',
        'filter'      => 'Filter',
        'reset'       => 'Reset',
        'empty'       => 'No orders match these filters.',
        'number'      => 'Order',
        'customer'    => 'Customer',
        'placed'      => 'Placed',
        'items'       => 'Items',
        'total'       => 'Total',
        'view'        => 'View',
        'back'        => 'Back to orders',

        'customer_details' => 'Customer details',
        'name'             => 'Name',
        'phone'            => 'Phone',
        'phone_alt'        => 'Alternate phone',
        'email'            => 'Email',
        'account'          => 'Account',
        'guest'            => 'Guest checkout',
        'address'          => 'Delivery address',
        'governorate'      => 'Governorate',
        'area'             => 'Area',
        'street'           => 'Street address',
        'landmark'         => 'Landmark',

        'products'    => 'Products',
        'product'     => 'Product',
        'sku'         => 'SKU',
        'variant'     => 'Variant',
        'unit_price'  => 'Unit price',
        'quantity'    => 'Qty',
        'line_total'  => 'Line total',
        'subtotal'    => 'Subtotal',
        'shipping'    => 'Shipping',
        'discount'    => 'Discount',
        'grand_total' => 'Total',
        'payment'     => 'Payment method',
        'notes'       => 'Customer notes',
        'no_notes'    => 'The customer left no notes.',

        'change_status'   => 'Change status',
        'new_status'      => 'New status',
        'internal_note'   => 'Internal note (optional)',
        'apply'           => 'Apply',
        'no_transitions'  => 'This order has reached a final status and cannot be moved further.',
        'history'         => 'Status history',
        'history_by'      => 'by :actor',
        'history_initial' => 'Order placed',
    ],
];
