<?php

declare(strict_types=1);

return [

    'title' => 'المرتجعات والاستبدال',

    'type' => [
        'return'   => 'إرجاع',
        'exchange' => 'استبدال',
    ],

    'status' => [
        'requested' => 'قيد المراجعة',
        'approved'  => 'تمت الموافقة',
        'rejected'  => 'مرفوض',
        'received'  => 'تم الاستلام',
        'completed' => 'مكتمل',
    ],

    'reason' => [
        'wrong_size'       => 'المقاس غير مناسب',
        'not_as_described' => 'مختلف عن الوصف',
        'damaged'          => 'وصل تالفًا',
        'wrong_item'       => 'وصل منتج خاطئ',
        'changed_mind'     => 'غيّرت رأيي',
        'other'            => 'سبب آخر',
    ],

    'fields' => [
        'type'   => 'إرجاع أم استبدال',
        'reason' => 'السبب',
        'note'   => 'أي تفاصيل تودين إضافتها',
        'items'  => 'القطع',
        'quantity' => 'الكمية',
        'replacement'      => 'أرسلوا لي بدلًا منها',
        'replacement_hint' => 'تظهر المقاسات والألوان المتوفرة في المخزون فقط.',
        'received'         => 'الكمية المستلمة',
    ],

    'errors' => [
        'not_delivered'     => 'لا يمكن إرجاع طلب لم يتم تسليمه.',
        'window_closed'     => 'نقبل المرتجعات خلال :days يومًا من التسليم.',
        'no_items'          => 'اختاري قطعة واحدة على الأقل.',
        'quantity_exceeded' => 'طلبتِ إرجاع كمية من ":product" أكبر مما يحتويه الطلب.',
        'item_not_on_order' => 'إحدى القطع ليست ضمن هذا الطلب.',
        'too_many_open'     => 'هذا الطلب لديه بالفعل :max طلبات قيد المراجعة.',
        'already_decided'   => 'تم البت في هذا الطلب بالفعل.',
        'invalid_transition' => 'لا يمكن نقل طلب حالته :from إلى :to.',
        'replacement_required'       => 'اختاري ما تريدينه بدلًا من ":product".',
        'replacement_not_on_product' => 'يجب أن يكون البديل مقاسًا أو لونًا آخر من نفس القطعة.',
        'replacement_inactive'       => 'البديل :sku لم يعد متاحًا.',
        'replacement_out_of_stock'   => 'البديل :sku نفد من المخزون. برجاء اختيار بديل آخر.',
        'received_too_many'          => 'سجّلتِ كمية من ":product" أكبر مما يشمله الطلب.',
    ],

    'messages' => [
        'submitted' => 'تم استلام الطلب :number وسنتواصل معك.',
        'withdrawn' => 'تم سحب طلبك.',
        'decided'   => 'الطلب :number أصبح :status.',
    ],

    'history' => [
        'approved' => 'تمت الموافقة على الإرجاع :number.',
        'received' => 'تم استلام الإرجاع :number.',
    ],

    'customer' => [
        'title'        => 'مرتجعاتي',
        'empty'        => 'لم تطلبي أي إرجاع.',
        'number'       => 'رقم الطلب',
        'order'        => 'الطلب',
        'raised'       => 'تاريخ الطلب',
        'view'         => 'عرض الطلب',
        'withdraw'     => 'سحب الطلب',
        'confirm'      => 'هل تريدين سحب هذا الطلب؟',
        'create_title' => 'إرجاع أو استبدال',
        'create_intro' => 'اختاري القطع التي تودين إرجاعها أو استبدالها.',
        'submit'       => 'إرسال الطلب',
        'returned_already' => 'تم طلبها من قبل',
        'our_reply'    => 'ردنا',
        'your_note'    => 'ملاحظتك',
        'decided_on'   => 'تمت الإجابة في :date',
        'nothing_left' => 'تم طلب إرجاع كل القطع في هذا الطلب بالفعل.',
        'exchange_hint' => 'اختاري المقاس أو اللون الذي تريدينه بدلًا من كل قطعة.',
        'replacement'   => 'البديل',
        'no_stock'      => 'لا يوجد بديل متاح لهذه القطعة، لذا يمكن إرجاعها فقط.',
    ],

    'admin' => [
        'title'      => 'المرتجعات',
        'all'        => 'كل الطلبات',
        'empty'      => 'لا توجد طلبات مطابقة.',
        'customer'   => 'العميلة',
        'order'      => 'الطلب',
        'type'       => 'النوع',
        'reason'     => 'السبب',
        'raised'     => 'تاريخ الطلب',
        'pieces'     => 'القطع',
        'view'       => 'عرض',
        'back'       => 'العودة إلى المرتجعات',
        'decide'     => 'اتخاذ قرار',
        'approve'    => 'موافقة',
        'reject'     => 'رفض',
        'complete'   => 'وضع علامة مكتمل',
        'note'       => 'ملاحظة للعميلة (اختياري)',
        'decided_by' => 'تم البت بواسطة :name في :date',
        'items'      => 'القطع المرتجعة',
        'restock_note'  => 'يتحرك المخزون عند وصول الشحنة، وليس الآن — سجّلي الاستلام عند وصولها.',
        'receive'       => 'تسجيل الاستلام',
        'receive_hint'  => 'يسجّل ما وصل فعليًا ويحرّك المخزون.',
        'received_by'   => 'تم الاستلام بواسطة :name في :date',
        'replacement'   => 'البديل',
        'replacements'  => 'البدائل المرسلة',
        'exchange_note' => 'استلام الاستبدال يخصم البديل من المخزون.',
    ],
];
