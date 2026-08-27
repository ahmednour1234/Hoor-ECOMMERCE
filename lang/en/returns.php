<?php

declare(strict_types=1);

return [

    'title' => 'Returns and exchanges',

    'type' => [
        'return'   => 'Return',
        'exchange' => 'Exchange',
    ],

    'status' => [
        'requested' => 'Requested',
        'approved'  => 'Approved',
        'rejected'  => 'Not approved',
        'received'  => 'Received',
        'completed' => 'Completed',
    ],

    'reason' => [
        'wrong_size'       => 'The size did not fit',
        'not_as_described' => 'Not as described',
        'damaged'          => 'Arrived damaged',
        'wrong_item'       => 'Wrong item sent',
        'changed_mind'     => 'Changed my mind',
        'other'            => 'Another reason',
    ],

    'fields' => [
        'type'   => 'Return or exchange',
        'reason' => 'Reason',
        'note'   => 'Anything you would like to add',
        'items'  => 'Pieces',
        'quantity' => 'How many',
        'replacement'      => 'Send me instead',
        'replacement_hint' => 'Only sizes and colours we have in stock are listed.',
        'received'         => 'How many came back',
    ],

    'errors' => [
        'not_delivered'     => 'Only a delivered order can be returned.',
        'window_closed'     => 'Returns are accepted within :days days of delivery.',
        'no_items'          => 'Choose at least one piece to send back.',
        'quantity_exceeded' => 'You asked to return more of ":product" than the order holds.',
        'item_not_on_order' => 'One of the pieces is not on this order.',
        'too_many_open'     => 'This order already has :max requests under review.',
        'already_decided'   => 'This request has already been decided.',
        'invalid_transition' => 'A request that is :from cannot be marked :to.',
        'replacement_required'       => 'Choose what you would like instead of ":product".',
        'replacement_not_on_product' => 'The replacement must be another size or colour of the same piece.',
        'replacement_inactive'       => 'The replacement :sku is no longer available.',
        'replacement_out_of_stock'   => 'The replacement :sku has sold out. Please choose another.',
        'received_too_many'          => 'You recorded more of ":product" than the request covers.',
    ],

    'messages' => [
        'submitted' => 'Request :number received. We will be in touch.',
        'withdrawn' => 'Your request has been withdrawn.',
        'decided'   => 'Request :number is now :status.',
    ],

    'history' => [
        'approved' => 'Return :number approved.',
        'received' => 'Return :number received.',
    ],

    'customer' => [
        'title'        => 'My returns',
        'empty'        => 'You have not requested a return.',
        'number'       => 'Request',
        'order'        => 'Order',
        'raised'       => 'Requested',
        'view'         => 'View request',
        'withdraw'     => 'Withdraw request',
        'confirm'      => 'Withdraw this request?',
        'create_title' => 'Send something back',
        'create_intro' => 'Choose the pieces you would like to return or exchange.',
        'submit'       => 'Send request',
        'returned_already' => 'Already requested',
        'our_reply'    => 'Our reply',
        'your_note'    => 'What you told us',
        'decided_on'   => 'Answered on :date',
        'nothing_left' => 'Every piece on this order has already been requested.',
        'exchange_hint' => 'Pick the size or colour you would like instead of each piece.',
        'replacement'   => 'Replacement',
        'no_stock'      => 'Nothing else is in stock for this piece, so it can only be returned.',
    ],

    'admin' => [
        'title'      => 'Returns',
        'all'        => 'All requests',
        'empty'      => 'No requests match this filter.',
        'customer'   => 'Customer',
        'order'      => 'Order',
        'type'       => 'Type',
        'reason'     => 'Reason',
        'raised'     => 'Requested',
        'pieces'     => 'Pieces',
        'view'       => 'View',
        'back'       => 'Back to returns',
        'decide'     => 'Decide',
        'approve'    => 'Approve',
        'reject'     => 'Reject',
        'complete'   => 'Mark completed',
        'note'       => 'Note to the customer (optional)',
        'decided_by' => 'Decided by :name on :date',
        'items'      => 'Pieces being sent back',
        'restock_note'  => 'Stock moves when the parcel arrives, not now — mark it received once it is back with you.',
        'receive'       => 'Mark received',
        'receive_hint'  => 'Records what actually came back and moves the stock.',
        'received_by'   => 'Received by :name on :date',
        'replacement'   => 'Replacement',
        'replacements'  => 'Replacements going out',
        'exchange_note' => 'Receiving an exchange takes the replacement off the shelf.',
    ],
];
