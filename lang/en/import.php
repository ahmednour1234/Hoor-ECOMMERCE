<?php

declare(strict_types=1);

return [

    'title'    => 'Import products',
    'subtitle' => 'Add or update your whole catalogue from one spreadsheet.',

    'steps' => [
        'title' => 'How it works',
        'one'   => 'Download the template. It already lists your categories, sizes and colours.',
        'two'   => 'Fill in one row per variant — a garment in three sizes is three rows sharing a name.',
        'three' => 'Put your photographs in a folder called images, next to the spreadsheet.',
        'four'  => 'Zip the two together and upload the zip here.',
    ],

    'template'      => 'Download the template',
    'template_note' => 'Generated fresh, so it always matches your current categories.',

    'file'      => 'Spreadsheet or zip',
    'file_hint' => 'A .zip with your images, or a bare .xlsx to update prices and stock only.',
    'submit'    => 'Import',

    'summary' => ':products products and :variants variants imported, with :images images.',

    'errors' => [
        'title'      => 'Nothing was imported',
        'lead'       => 'The spreadsheet has :count problems. Fix them and upload it again — nothing has been changed.',
        'rejected'   => 'The spreadsheet has :count problems. Nothing was imported.',
        'row'        => 'Row :row',

        'empty'          => 'The spreadsheet has no rows to import.',
        'unreadable'     => 'We could not read that spreadsheet. Please export it as .xlsx and try again.',
        'bad_zip'        => 'We could not open that zip file.',
        'no_sheet'       => 'The zip has no .xlsx spreadsheet in it.',
        'archive_too_large' => 'That archive is too large to unpack.',
        'temp_failed'    => 'We could not unpack the upload. Please try again.',
        'too_many'       => 'The spreadsheet has more than :max rows.',

        'required'       => ':column is required.',
        'duplicate_variant' => 'Row :row already has this size and colour for the same product.',
        'duplicate_sku'  => 'SKU :sku is already used on row :row.',
        'unknown_category' => 'No category called ":value". Check the Reference sheet.',
        'unknown_size'   => 'No size called ":value". Check the Reference sheet.',
        'unknown_color'  => 'No colour called ":value". Check the Reference sheet.',
        'bad_price'      => '":value" is not a valid price.',
        'sale_too_high'  => 'The sale price must be below the price.',
        'bad_stock'      => '":value" is not a whole number.',
    ],

    'imported_as_draft' => 'Imported products are saved as drafts, so you can review them before they appear in the shop.',
];
