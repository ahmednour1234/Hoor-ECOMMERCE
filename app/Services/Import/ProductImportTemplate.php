<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\Category;
use App\Models\Color;
use App\Models\Size;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color as SpoutColor;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * The spreadsheet a shop fills in to import its catalogue.
 *
 * One row per **variant**, not per product. A product with three sizes in two
 * colours is six rows sharing a SKU prefix and a name — because that is what a
 * spreadsheet is good at, and because stock is tracked per variant, so every
 * variant needs its own row to carry its own quantity.
 *
 * The template ships with its own instructions and with the shop's real
 * categories, sizes and colours listed on a reference sheet, so the person
 * filling it in is copying values that exist rather than inventing ones the
 * import will reject.
 */
class ProductImportTemplate
{
    /**
     * The columns, in order. The key is what the importer reads; the label is
     * what the person filling it in sees.
     *
     * @var array<string, array{label: string, hint: string, required: bool}>
     */
    public const COLUMNS = [
        'sku' => [
            'label' => 'SKU *',
            'hint'  => 'Unique per variant. e.g. HOOR-JN-01-M-IND',
            'required' => true,
        ],
        'name_en' => [
            'label' => 'Product name (EN) *',
            'hint'  => 'Repeat on every row of the same product.',
            'required' => true,
        ],
        'name_ar' => [
            'label' => 'Product name (AR) *',
            'hint'  => 'اسم المنتج بالعربية',
            'required' => true,
        ],
        'category' => [
            'label' => 'Category *',
            'hint'  => 'Must match a category on the Reference sheet.',
            'required' => true,
        ],
        'price' => [
            'label' => 'Price (EGP) *',
            'hint'  => 'Numbers only, e.g. 1200 or 1200.50',
            'required' => true,
        ],
        'sale_price' => [
            'label' => 'Sale price (EGP)',
            'hint'  => 'Leave empty if not on sale. Must be below the price.',
            'required' => false,
        ],
        'size' => [
            'label' => 'Size *',
            'hint'  => 'Must match a size on the Reference sheet.',
            'required' => true,
        ],
        'color' => [
            'label' => 'Colour *',
            'hint'  => 'Must match a colour on the Reference sheet.',
            'required' => true,
        ],
        'stock' => [
            'label' => 'Stock *',
            'hint'  => 'Whole number. 0 means out of stock.',
            'required' => true,
        ],
        'images' => [
            'label' => 'Images',
            'hint'  => 'File names from the images folder, separated by commas. First one is the main image.',
            'required' => false,
        ],
        'description_en' => [
            'label' => 'Description (EN)',
            'hint'  => 'Optional. Repeat on every row of the same product.',
            'required' => false,
        ],
        'description_ar' => [
            'label' => 'Description (AR)',
            'hint'  => 'اختياري',
            'required' => false,
        ],
    ];

    /**
     * Write the template to a path.
     */
    public function writeTo(string $path): void
    {
        $writer = new Writer();
        $writer->openToFile($path);

        $this->writeProductsSheet($writer);
        $this->writeReferenceSheet($writer);

        $writer->close();
    }

    /**
     * The sheet the shop fills in: headers, hints, and one worked example.
     */
    private function writeProductsSheet(Writer $writer): void
    {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Products');

        $header = (new Style())
            ->setFontBold()
            ->setFontColor(SpoutColor::WHITE)
            ->setBackgroundColor('082540');   // hoor navy

        $hint = (new Style())
            ->setFontItalic()
            ->setFontSize(9)
            ->setFontColor('808080');

        $writer->addRow(Row::fromValues(
            array_column(self::COLUMNS, 'label'),
            $header,
        ));

        // The hints live in the sheet rather than in a separate document,
        // where they would not be read.
        $writer->addRow(Row::fromValues(
            array_column(self::COLUMNS, 'hint'),
            $hint,
        ));

        // Two rows of the same product, so the shape is obvious: the product
        // details repeat and only the variant columns change.
        $example = (new Style())->setFontColor('9AA5B1');

        $writer->addRow(Row::fromValues([
            'HOOR-JN-01-M-IND', 'Wide Leg Jeans', 'جينز واسع', 'Jeans',
            '1200', '', 'M', 'Indigo', '10',
            'jeans-1.jpg, jeans-2.jpg',
            'Relaxed through the leg.', 'قصة واسعة ومريحة.',
        ], $example));

        $writer->addRow(Row::fromValues([
            'HOOR-JN-01-L-IND', 'Wide Leg Jeans', 'جينز واسع', 'Jeans',
            '1200', '', 'L', 'Indigo', '6',
            'jeans-1.jpg, jeans-2.jpg',
            'Relaxed through the leg.', 'قصة واسعة ومريحة.',
        ], $example));
    }

    /**
     * The values that actually exist, so nobody invents a category.
     */
    private function writeReferenceSheet(Writer $writer): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Reference');

        $header = (new Style())
            ->setFontBold()
            ->setFontColor(SpoutColor::WHITE)
            ->setBackgroundColor('082540');

        $writer->addRow(Row::fromValues(['Categories', 'Sizes', 'Colours'], $header));

        $categories = Category::query()->orderBy('name_en')->pluck('name_en')->all();
        $sizes = Size::query()->orderBy('sort_order')->pluck('name_en')->all();
        $colors = Color::query()->orderBy('name_en')->pluck('name_en')->all();

        $rows = max(count($categories), count($sizes), count($colors));

        for ($i = 0; $i < $rows; $i++) {
            $writer->addRow(Row::fromValues([
                $categories[$i] ?? '',
                $sizes[$i] ?? '',
                $colors[$i] ?? '',
            ]));
        }
    }
}
