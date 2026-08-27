<?php

declare(strict_types=1);

return [
    'title'   => 'HOOR Admin',
    'welcome' => 'Welcome back, :name',

    'nav' => [
        'overview'    => 'Overview',
        'dashboard'   => 'Dashboard',
        'catalog'     => 'Catalog',
        'products'    => 'Products',
        'categories'  => 'Categories',
        'inventory'   => 'Inventory',
        'colors'      => 'Colours',
        'sizes'       => 'Sizes',
        'sales'       => 'Sales',
        'orders'      => 'Orders',
        'returns'     => 'Returns',
        'customers'   => 'Customers',
        'marketing'   => 'Marketing',
        'coupons'     => 'Coupons',
        'banners'     => 'Banners',
        'sliders'     => 'Sliders',
        'newsletter'  => 'Newsletter',
        'messages'    => 'Messages',
        'faqs'        => 'FAQs',
        'settings'    => 'Settings',
        'shipping'    => 'Shipping',
        'pages'       => 'Pages',
        'general'     => 'General',
        'view_store'  => 'View store',
    ],

    'stats' => [
        'orders_period'     => 'Orders',
        'revenue_period'    => 'Revenue',
        'revenue_note'      => 'Delivered orders only',
        'orders_today'      => 'Orders today',
        'revenue_month'     => 'Revenue this month',
        'low_stock'         => 'Low stock variants',
        'out_of_stock'      => 'Out of stock variants',
        'pending_orders'    => 'Pending orders',
        'awaiting_shipping' => 'Awaiting shipping',
        'delivered'         => 'Delivered orders',
        'cancelled'         => 'Cancelled orders',
        'needs_action'      => 'Needs action',
        'right_now'         => 'Right now',
    ],

    'dashboard' => [
        'vs_previous'      => 'vs previous',
        'no_data'          => 'Nothing to show for this period.',
        'no_sales_yet'     => 'No sales in this period.',
        'stock_healthy'    => 'Every variant is above its reorder threshold.',
        'peak'             => 'Peak',
        'chart_alt'        => 'Chart of :metric over the selected period',

        'sales_over_time'  => 'Sales over time',
        'orders_over_time' => 'Orders over time',
        'orders_by_status' => 'Orders by status',
        'recent_orders'    => 'Recent orders',
        'low_stock'        => 'Low stock',
        'best_selling'     => 'Best selling products',
        'top_categories'   => 'Top categories',
        'top_sizes'        => 'Top sizes',
        'top_colors'       => 'Top colours',
        'by_governorate'   => 'Sales by governorate',
        'view_all_orders'  => 'View all orders',

        'period' => [
            'today'  => 'Today',
            'week'   => '7 days',
            'month'  => '30 days',
            'custom' => 'Custom',
            'from'   => 'From',
            'to'     => 'To',
            'apply'  => 'Apply',
        ],
    ],

    'errors' => [
        'forbidden' => 'You are not authorized to access the HOOR dashboard.',
    ],
];
