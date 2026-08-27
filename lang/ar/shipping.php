<?php

declare(strict_types=1);

return [
    'title'    => 'الشحن',
    'subtitle' => 'المحافظات والمناطق وتكلفة التوصيل لكل منها.',

    'governorates' => [
        'title'          => 'المحافظات',
        'subtitle'       => 'كل الوجهات التي توصل إليها حور، وتكلفة كل منها.',
        'create'         => 'إضافة محافظة',
        'edit'           => 'تعديل المحافظة',
        'none'           => 'لا توجد محافظات بعد.',
        'delete_confirm' => 'حذف هذه المحافظة؟',
        'areas_link'     => '{0} لا توجد مناطق|{1} منطقة واحدة|{2} منطقتان|[3,10] :count مناطق|[11,*] :count منطقة',
        'manage_areas'   => 'إدارة المناطق',
    ],

    'areas' => [
        'title'          => 'مناطق :governorate',
        'subtitle'       => 'الأحياء داخل هذه المحافظة. اتركي التكلفة فارغة لتطبيق تكلفة المحافظة.',
        'create'         => 'إضافة منطقة',
        'edit'           => 'تعديل المنطقة',
        'none'           => 'لا توجد مناطق بعد.',
        'none_hint'      => 'المناطق اختيارية — يمكن إتمام الطلب بالمحافظة وحدها.',
        'back'           => 'رجوع إلى المحافظات',
        'delete_confirm' => 'حذف هذه المنطقة؟',
    ],

    'fields' => [
        'name_ar'       => 'الاسم بالعربية',
        'name_en'       => 'الاسم بالإنجليزية',
        'code'          => 'الكود',
        'code_hint'     => 'اختصار قصير، مثل C للقاهرة.',
        'shipping_fee'  => 'تكلفة الشحن',
        'fee_inherit'   => 'اتركيها فارغة لتطبيق تكلفة المحافظة',
        'delivery_days' => 'التوصيل (أيام عمل)',
        'days_min'      => 'من',
        'days_max'      => 'إلى',
        'is_active'     => 'مفعّلة',
        'sort_order'    => 'الترتيب',
        'areas'         => 'المناطق',
        'inherits'      => 'يرث',
    ],

    'states' => [
        'active'     => 'مفعّلة',
        'inactive'   => 'غير مفعّلة',
        'activate'   => 'تفعيل',
        'deactivate' => 'إلغاء التفعيل',
    ],

    'messages' => [
        'governorate_created'     => 'تم إنشاء المحافظة «:name».',
        'governorate_updated'     => 'تم حفظ المحافظة «:name».',
        'governorate_deleted'     => 'تم حذف المحافظة «:name».',
        'governorate_activated'   => 'المحافظة «:name» مفعّلة الآن.',
        'governorate_deactivated' => 'لم نعد نوصل إلى المحافظة «:name».',
        'governorate_has_areas'   => 'لا يمكن حذف هذه المحافظة لأنها تحتوي على مناطق. قومي بإلغاء تفعيلها بدلاً من ذلك.',

        'area_created'     => 'تم إنشاء المنطقة «:name».',
        'area_updated'     => 'تم حفظ المنطقة «:name».',
        'area_deleted'     => 'تم حذف المنطقة «:name».',
        'area_activated'   => 'المنطقة «:name» مفعّلة الآن.',
        'area_deactivated' => 'لم نعد نوصل إلى المنطقة «:name».',
    ],

    'checkout' => [
        'governorate' => 'المحافظة',
        'area'        => 'المنطقة',
        'choose'      => 'اختاري المحافظة',
        'choose_area' => 'اختاري المنطقة (اختياري)',
        'fee'         => 'الشحن',
        'delivery'    => 'التوصيل خلال :days أيام عمل',
        'unavailable' => 'لا نوصل حالياً إلى هذه الوجهة.',
    ],

    'attributes' => [
        'name_ar'           => 'الاسم بالعربية',
        'name_en'           => 'الاسم بالإنجليزية',
        'code'              => 'الكود',
        'shipping_fee'      => 'تكلفة الشحن',
        'delivery_days_min' => 'أقل عدد أيام للتوصيل',
        'delivery_days_max' => 'أكبر عدد أيام للتوصيل',
        'sort_order'        => 'الترتيب',
    ],
];
