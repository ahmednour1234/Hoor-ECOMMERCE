<?php

declare(strict_types=1);

return [

    'title'    => 'Coupons',
    'singular' => 'Coupon',

    'type' => [
        'fixed'      => 'Fixed amount',
        'percentage' => 'Percentage',
    ],

    'status' => [
        'live'      => 'Live',
        'inactive'  => 'Inactive',
        'scheduled' => 'Scheduled',
        'expired'   => 'Expired',
        'exhausted' => 'Fully used',
    ],

    'fields' => [
        'code'               => 'Code',
        'name'               => 'Campaign name',
        'type'               => 'Discount type',
        'value'              => 'Value',
        'max_discount'       => 'Maximum discount',
        'min_order'          => 'Minimum order',
        'starts_at'          => 'Starts',
        'expires_at'         => 'Expires',
        'usage_limit'        => 'Total uses',
        'per_customer_limit' => 'Uses per customer',
        'is_active'          => 'Active',
        'used'               => 'Used',
        'discount'           => 'Discount',
    ],

    'hints' => [
        'code'               => 'Letters, numbers, dashes. Customers type this, so keep it short.',
        'value_fixed'        => 'Amount off, in EGP.',
        'value_percentage'   => 'Whole percent, 1 to 100.',
        'max_discount'       => 'The most a percentage coupon can take off. Leave empty for no ceiling.',
        'min_order'          => 'The basket must reach this before the code applies.',
        'usage_limit'        => 'Across all customers. Leave empty for unlimited.',
        'per_customer_limit' => 'Counted by phone number, so it works for guests too. Leave empty for unlimited.',
        'schedule'           => 'Leave dates empty to run until you switch it off.',
    ],

    'summary' => [
        'up_to' => 'up to :amount',
        'over'  => 'over :amount',
    ],

    'messages' => [
        'saved'    => 'Coupon :code saved.',
        'deleted'  => 'Coupon deleted.',
        'enabled'  => 'Coupon :code is live.',
        'disabled' => 'Coupon :code switched off.',
        'applied'  => 'Code :code applied.',
        'removed'  => 'Code removed.',
    ],

    'errors' => [
        // Shown to customers.
        'not_found'     => 'We do not recognise that code.',
        'inactive'      => 'That code is no longer available.',
        'not_started'   => 'That code is not active yet.',
        'expired'       => 'That code has expired.',
        'exhausted'     => 'That code has been fully used.',
        'below_minimum' => 'Your basket has not reached the minimum for that code.',
        'already_used'  => 'You have already used that code.',
        'no_value'      => 'That code takes nothing off this basket.',
        'unavailable'   => 'Discount codes are not available right now.',

        // Shown to staff.
        'max_on_fixed'       => 'A maximum discount only applies to percentage coupons.',
        'value_over_minimum' => 'The amount off is more than the minimum order, so the basket would be free.',
        'has_redemptions'    => 'This coupon has been used, so it cannot be deleted. Switch it off instead.',
    ],

    'admin' => [
        'add'         => 'Add a coupon',
        'edit'        => 'Edit coupon',
        'empty'       => 'No coupons yet.',
        'search'      => 'Search by code',
        'terms'       => 'Terms',
        'window'      => 'Runs',
        'usage'       => 'Usage',
        'unlimited'   => 'Unlimited',
        'remaining'   => ':count left',
        'view'        => 'View',
        'back'        => 'Back to coupons',
        'enable'      => 'Switch on',
        'disable'     => 'Switch off',
        'confirm'     => 'Delete this coupon?',
        'redemptions' => 'Who has used it',
        'no_redemptions' => 'Nobody has used this code yet.',
        'customer'    => 'Customer',
        'order'       => 'Order',
        'when'        => 'When',
        'guest'       => 'Guest',
    ],

    'cart' => [
        'have_code' => 'Have a discount code?',
        'placeholder' => 'Enter code',
        'apply'     => 'Apply',
        'remove'    => 'Remove',
        'applied'   => 'Code :code applied',
    ],
];
