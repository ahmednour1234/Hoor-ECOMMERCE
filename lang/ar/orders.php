<?php

declare(strict_types=1);

return [
    'status' => [
        'pending'            => 'في انتظار التأكيد',
        'confirmed'          => 'مؤكد',
        'preparing'          => 'قيد التجهيز',
        'ready_for_shipping' => 'جاهز للشحن',
        'shipped'            => 'تم الشحن',
        'out_for_delivery'   => 'خارج للتوصيل',
        'delivered'          => 'تم التسليم',
        'cancelled'          => 'ملغي',
        'delivery_failed'    => 'تعذر التسليم',
        'returned'           => 'مرتجع',
    ],

    'payment' => [
        'cash_on_delivery' => 'الدفع عند الاستلام',
    ],

    'history' => [
        'system' => 'النظام',
        'placed' => 'تم إنشاء الطلب من العميلة.',
    ],
    'errors' => [
        'invalid_transition'  => 'لا يمكن نقل طلب حالته :from إلى :to.',
        'restock_unavailable' => 'إعادة تفعيل هذا الطلب تحتاج كمية لم تعد متوفرة (:sku).',
    ],

    'fields' => [
        'status' => 'الحالة',
        'note'   => 'الملاحظة',
    ],

    'messages' => [
        'status_updated' => 'الطلب :number أصبح :status.',
    ],

    'admin' => [
        'title'       => 'الطلبات',
        'all'         => 'كل الطلبات',
        'search'      => 'بحث',
        'search_hint' => 'رقم الطلب أو اسم العميلة أو رقم الهاتف أو كود المنتج',
        'from'        => 'من',
        'to'          => 'إلى',
        'filter'      => 'تصفية',
        'reset'       => 'إعادة ضبط',
        'empty'       => 'لا توجد طلبات مطابقة لهذه التصفية.',
        'number'      => 'الطلب',
        'customer'    => 'العميلة',
        'placed'      => 'تاريخ الطلب',
        'items'       => 'القطع',
        'total'       => 'الإجمالي',
        'view'        => 'عرض',
        'back'        => 'العودة إلى الطلبات',

        'customer_details' => 'بيانات العميلة',
        'name'             => 'الاسم',
        'phone'            => 'الهاتف',
        'phone_alt'        => 'هاتف بديل',
        'email'            => 'البريد الإلكتروني',
        'account'          => 'الحساب',
        'guest'            => 'طلب بدون حساب',
        'address'          => 'عنوان التوصيل',
        'governorate'      => 'المحافظة',
        'area'             => 'المنطقة',
        'street'           => 'العنوان',
        'pin'          => 'موقع الخريطة',
        'open_map'     => 'فتح في الخرائط',
        'landmark'         => 'علامة مميزة',

        'products'    => 'المنتجات',
        'product'     => 'المنتج',
        'sku'         => 'الكود',
        'variant'     => 'المقاس واللون',
        'unit_price'  => 'سعر القطعة',
        'quantity'    => 'الكمية',
        'line_total'  => 'الإجمالي',
        'subtotal'    => 'المجموع الفرعي',
        'shipping'    => 'الشحن',
        'discount'    => 'الخصم',
        'grand_total' => 'الإجمالي النهائي',
        'payment'     => 'طريقة الدفع',
        'notes'       => 'ملاحظات العميلة',
        'no_notes'    => 'لم تترك العميلة أي ملاحظات.',

        'change_status'   => 'تغيير الحالة',
        'new_status'      => 'الحالة الجديدة',
        'internal_note'   => 'ملاحظة داخلية (اختياري)',
        'apply'           => 'تنفيذ',
        'no_transitions'  => 'وصل هذا الطلب إلى حالة نهائية ولا يمكن نقله.',
        'history'         => 'سجل الحالات',
        'history_by'      => 'بواسطة :actor',
        'history_initial' => 'تم إنشاء الطلب',
    ],
    'mail' => [
        'subject'   => 'طلبك من حور :number',
        'greeting'  => 'شكرًا لكِ يا :name',
        'lead'      => 'استلمنا طلبك وسنتصل بك قريبًا لتأكيده.',
        'number'    => 'رقم الطلب',
        'placed'    => 'تاريخ الطلب',
        'items'     => 'محتويات الطلب',
        'delivery'  => 'عنوان التوصيل',
        'payment'   => 'الدفع',
        'cod'       => 'الدفع عند الاستلام — تدفعين لمندوب التوصيل عند وصول طلبك.',
        'track'     => 'تتبعي طلبك',
        'track_hint'=> 'استخدمي رقم الطلب ورقم الهاتف أعلاه. لا حاجة إلى حساب.',
        'help'      => 'لديك سؤال؟ ردي على هذا البريد أو اتصلي بنا على :phone.',
        'signoff'   => 'حور — Denim Wear',
    ],
];
