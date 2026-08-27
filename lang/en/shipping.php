<?php

declare(strict_types=1);

return [
    'title'    => 'Shipping',
    'subtitle' => 'Governorates, areas and the delivery fee for each.',

    'governorates' => [
        'title'    => 'Governorates',
        'subtitle' => 'Every destination HOOR delivers to, and what it costs.',
        'create'   => 'Add governorate',
        'edit'     => 'Edit governorate',
        'none'     => 'No governorates yet.',
        'delete_confirm' => 'Delete this governorate?',
        'areas_link'     => '{0} No areas|{1} 1 area|[2,*] :count areas',
        'manage_areas'   => 'Manage areas',
    ],

    'areas' => [
        'title'      => 'Areas in :governorate',
        'subtitle'   => 'Districts within this governorate. Leave a fee blank to inherit the governorate fee.',
        'create'     => 'Add area',
        'edit'       => 'Edit area',
        'none'       => 'No areas yet.',
        'none_hint'  => 'Areas are optional — orders can be placed with the governorate alone.',
        'back'       => 'Back to governorates',
        'delete_confirm' => 'Delete this area?',
    ],

    'fields' => [
        'name_ar'       => 'Name (Arabic)',
        'name_en'       => 'Name (English)',
        'code'          => 'Code',
        'code_hint'     => 'Short reference, e.g. C for Cairo.',
        'shipping_fee'  => 'Shipping fee',
        'fee_inherit'   => 'Leave blank to use the governorate fee',
        'delivery_days' => 'Delivery (working days)',
        'days_min'      => 'From',
        'days_max'      => 'To',
        'is_active'     => 'Active',
        'sort_order'    => 'Sort order',
        'areas'         => 'Areas',
        'inherits'      => 'Inherits',
    ],

    'states' => [
        'active'     => 'Active',
        'inactive'   => 'Inactive',
        'activate'   => 'Activate',
        'deactivate' => 'Deactivate',
    ],

    'messages' => [
        'governorate_created'     => 'Governorate ":name" was created.',
        'governorate_updated'     => 'Governorate ":name" was saved.',
        'governorate_deleted'     => 'Governorate ":name" was deleted.',
        'governorate_activated'   => 'Governorate ":name" is now active.',
        'governorate_deactivated' => 'Governorate ":name" is no longer delivered to.',
        'governorate_has_areas'   => 'This governorate still has areas, so it cannot be deleted. Deactivate it instead.',

        'area_created'     => 'Area ":name" was created.',
        'area_updated'     => 'Area ":name" was saved.',
        'area_deleted'     => 'Area ":name" was deleted.',
        'area_activated'   => 'Area ":name" is now active.',
        'area_deactivated' => 'Area ":name" is no longer delivered to.',
    ],

    'checkout' => [
        'governorate'  => 'Governorate',
        'area'         => 'Area',
        'choose'       => 'Choose a governorate',
        'choose_area'  => 'Choose an area (optional)',
        'fee'          => 'Shipping',
        'delivery'     => 'Delivery in :days working days',
        'unavailable'  => 'We do not currently deliver to that destination.',
    ],

    'attributes' => [
        'name_ar'           => 'Arabic name',
        'name_en'           => 'English name',
        'code'              => 'code',
        'shipping_fee'      => 'shipping fee',
        'delivery_days_min' => 'minimum delivery days',
        'delivery_days_max' => 'maximum delivery days',
        'sort_order'        => 'sort order',
    ],
];
