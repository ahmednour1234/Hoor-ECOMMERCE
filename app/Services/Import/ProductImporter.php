<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Casts\Money;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader;

/**
 * Imports a catalogue from a spreadsheet, with images from a folder beside it.
 *
 * One row per variant. Rows sharing a product name belong to the same product,
 * so a garment in three sizes is three rows and one product — which is how a
 * person naturally fills in a spreadsheet, and how stock has to be recorded
 * anyway, since it is tracked per variant.
 *
 * The whole import runs in one transaction. A spreadsheet is filled in by hand
 * and will contain mistakes; a partial import leaves a catalogue half-changed
 * with no clear way back, so either all of it lands or none of it does and the
 * shop is told which rows to fix.
 *
 * Images are read from a folder, referenced by file name. Names rather than
 * paths, so the person filling in the sheet writes what she sees in her own
 * folder — and so a crafted name cannot reach outside it.
 */
class ProductImporter
{
    /**
     * Rows to read before giving up.
     *
     * A guard against a spreadsheet with a million blank rows, which is a
     * common accident when a range is selected and deleted rather than the
     * rows themselves.
     */
    private const MAX_ROWS = 5000;

    /**
     * Cached lookups, so a 500-row sheet does not run 500 category queries.
     *
     * @var array<string, array<string, int>>
     */
    private array $lookups = [];

    /**
     * @param  string  $sheetPath  the .xlsx file
     * @param  string|null  $imageDirectory  a folder of images, if one came with it
     */
    public function import(string $sheetPath, ?string $imageDirectory = null): ProductImportResult
    {
        $result = new ProductImportResult();

        $rows = $this->readRows($sheetPath, $result);

        if ($result->hasErrors()) {
            return $result;
        }

        if ($rows === []) {
            $result->addError(0, __('import.errors.empty'));

            return $result;
        }

        $this->cacheLookups();

        // Validate everything before writing anything: a sheet with a typo in
        // row 180 should not leave 179 products imported.
        $parsed = $this->parseRows($rows, $result);

        if ($result->hasErrors()) {
            return $result;
        }

        DB::transaction(function () use ($parsed, $imageDirectory, $result): void {
            foreach ($this->groupByProduct($parsed) as $rowsForProduct) {
                $this->importProduct($rowsForProduct, $imageDirectory, $result);
            }
        });

        return $result;
    }

    // ------------------------------------------------------------- Reading

    /**
     * @return list<array{row: int, values: array<string, string>}>
     */
    private function readRows(string $path, ProductImportResult $result): array
    {
        $columns = array_keys(ProductImportTemplate::COLUMNS);
        $rows = [];

        try {
            $reader = new Reader();
            $reader->open($path);
        } catch (\Throwable $e) {
            $result->addError(0, __('import.errors.unreadable'));

            return [];
        }

        foreach ($reader->getSheetIterator() as $sheet) {
            // Only the first sheet. The template's second sheet is reference
            // data, and importing it would create nonsense products.
            if ($sheet->getName() !== 'Products' && $sheet->getIndex() > 0) {
                continue;
            }

            foreach ($sheet->getRowIterator() as $number => $row) {
                // Row 1 is the header, row 2 the hints.
                if ($number <= 2) {
                    continue;
                }

                if ($number > self::MAX_ROWS) {
                    $result->addError(0, __('import.errors.too_many', ['max' => self::MAX_ROWS]));
                    break 2;
                }

                $cells = array_map(
                    static fn ($cell): string => trim((string) $cell),
                    $row->toArray(),
                );

                // A row where every cell is blank is the end of the data, or a
                // gap somebody left. Either way there is nothing to import.
                if (implode('', $cells) === '') {
                    continue;
                }

                $values = [];

                foreach ($columns as $index => $key) {
                    $values[$key] = $cells[$index] ?? '';
                }

                $rows[] = ['row' => $number, 'values' => $values];
                $result->rowsRead++;
            }

            break;
        }

        $reader->close();

        return $rows;
    }

    // ---------------------------------------------------------- Validating

    /**
     * @param  list<array{row: int, values: array<string, string>}>  $rows
     * @return list<array<string, mixed>>
     */
    private function parseRows(array $rows, ProductImportResult $result): array
    {
        $parsed = [];
        $seenSkus = [];
        $seenCombinations = [];

        foreach ($rows as ['row' => $number, 'values' => $values]) {
            $errorsBefore = count($result->errors);

            foreach (ProductImportTemplate::COLUMNS as $key => $column) {
                if ($column['required'] && $values[$key] === '') {
                    $result->addError($number, __('import.errors.required', [
                        'column' => $column['label'],
                    ]));
                }
            }

            if (count($result->errors) > $errorsBefore) {
                continue;
            }

            $sku = strtoupper($values['sku']);

            /*
             * Two rows of the same product with the same size and colour are
             * the same variant twice — the database forbids it, and without
             * this check the import dies mid-transaction with a constraint
             * error instead of naming the row.
             */
            $combination = mb_strtolower($values['name_en'].'|'.$values['size'].'|'.$values['color']);

            if (isset($seenCombinations[$combination])) {
                $result->addError($number, __('import.errors.duplicate_variant', [
                    'row' => $seenCombinations[$combination],
                ]));

                continue;
            }

            $seenCombinations[$combination] = $number;

            // A duplicate SKU inside one sheet is a copy-paste slip, and the
            // second row would silently overwrite the first.
            if (isset($seenSkus[$sku])) {
                $result->addError($number, __('import.errors.duplicate_sku', [
                    'sku' => $sku,
                    'row' => $seenSkus[$sku],
                ]));

                continue;
            }

            $seenSkus[$sku] = $number;

            $categoryId = $this->lookup('categories', $values['category']);
            $sizeId = $this->lookup('sizes', $values['size']);
            $colorId = $this->lookup('colors', $values['color']);

            foreach ([
                'category' => $categoryId,
                'size'     => $sizeId,
                'color'    => $colorId,
            ] as $field => $id) {
                if ($id === null) {
                    $result->addError($number, __('import.errors.unknown_'.$field, [
                        'value' => $values[$field],
                    ]));
                }
            }

            $price = $this->money($values['price']);
            $salePrice = $values['sale_price'] === '' ? null : $this->money($values['sale_price']);

            if ($price === null || $price < 1) {
                $result->addError($number, __('import.errors.bad_price', ['value' => $values['price']]));
            }

            if ($values['sale_price'] !== '' && $salePrice === null) {
                $result->addError($number, __('import.errors.bad_price', ['value' => $values['sale_price']]));
            }

            // A sale price above the price would show a negative discount.
            if ($price !== null && $salePrice !== null && $salePrice >= $price) {
                $result->addError($number, __('import.errors.sale_too_high'));
            }

            if (! ctype_digit($values['stock'])) {
                $result->addError($number, __('import.errors.bad_stock', ['value' => $values['stock']]));
            }

            if (count($result->errors) > $errorsBefore) {
                continue;
            }

            $parsed[] = [
                'row'         => $number,
                'sku'         => $sku,
                'name_en'     => $values['name_en'],
                'name_ar'     => $values['name_ar'],
                'category_id' => $categoryId,
                'price'       => $price,
                'sale_price'  => $salePrice,
                'size_id'     => $sizeId,
                'color_id'    => $colorId,
                'stock'       => (int) $values['stock'],
                'images'      => $this->splitImages($values['images']),
                'description_en' => $values['description_en'],
                'description_ar' => $values['description_ar'],
            ];
        }

        return $parsed;
    }

    // ----------------------------------------------------------- Importing

    /**
     * Rows sharing a name are one product.
     *
     * Keyed on the English name because that is what the person filling in the
     * sheet repeats down the rows; the SKU differs per variant by design.
     *
     * @param  list<array<string, mixed>>  $parsed
     * @return list<list<array<string, mixed>>>
     */
    private function groupByProduct(array $parsed): array
    {
        $grouped = [];

        foreach ($parsed as $row) {
            $grouped[$row['name_en']][] = $row;
        }

        return array_values($grouped);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importProduct(array $rows, ?string $imageDirectory, ProductImportResult $result): void
    {
        $first = $rows[0];

        // Matched on name rather than SKU: the SKU belongs to a variant, and a
        // product's identity across a re-import is its name.
        // withTrashed() for the same reason as the slug: a soft-deleted
        // product still holds its name, and creating a second one would leave
        // two rows the shop cannot tell apart.
        $product = Product::withTrashed()->where('name_en', $first['name_en'])->first();

        $attributes = [
            'name_en'     => $first['name_en'],
            'name_ar'     => $first['name_ar'],
            'category_id' => $first['category_id'],
            'base_price'  => $first['price'],
            'sale_price'  => $first['sale_price'],
            'description_en' => $first['description_en'] ?: null,
            'description_ar' => $first['description_ar'] ?: null,
        ];

        if ($product === null) {
            $product = Product::create($attributes + [
                'slug'   => $this->uniqueSlug($first['name_en']),
                'status' => ProductStatus::Draft,
            ]);

            $result->productsCreated++;
        } else {
            // A product the shop deleted and is now importing again is being
            // brought back deliberately, so it is restored rather than left
            // trashed and invisible.
            if ($product->trashed()) {
                $product->restore();
            }

            $product->update($attributes);
            $result->productsUpdated++;
        }

        foreach ($rows as $row) {
            $this->importVariant($product, $row, $result);
        }

        if ($imageDirectory !== null) {
            $this->attachImages($product, $first['images'], $imageDirectory, $result);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importVariant(Product $product, array $row, ProductImportResult $result): void
    {
        $variant = ProductVariant::query()->where('sku', $row['sku'])->first();

        $attributes = [
            'product_id'     => $product->id,
            'size_id'        => $row['size_id'],
            'color_id'       => $row['color_id'],
            'stock_quantity' => $row['stock'],
            'is_active'      => true,
        ];

        if ($variant === null) {
            ProductVariant::create($attributes + ['sku' => $row['sku']]);
            $result->variantsCreated++;
        } else {
            $variant->update($attributes);
            $result->variantsUpdated++;
        }
    }

    /**
     * Copy the named images out of the uploaded folder.
     *
     * @param  list<string>  $names
     */
    private function attachImages(Product $product, array $names, string $directory, ProductImportResult $result): void
    {
        if ($names === []) {
            return;
        }

        $disk = Storage::disk(config('hoor.media.disk'));
        $position = $product->images()->max('sort_order') ?? -1;

        foreach ($names as $name) {
            /*
             * basename() strips any path the sheet might contain, so
             * "../../.env" becomes ".env" and is looked for inside the upload
             * folder — where it does not exist — rather than read from
             * anywhere else on the server.
             */
            $file = $directory.DIRECTORY_SEPARATOR.basename($name);

            if (! is_file($file)) {
                continue;
            }

            // Already attached, from an earlier import of the same sheet.
            if ($product->images()->where('path', 'like', '%'.pathinfo($name, PATHINFO_FILENAME).'%')->exists()) {
                continue;
            }

            $stored = $disk->putFile(
                config('hoor.media.paths.products'),
                new \Illuminate\Http\File($file),
            );

            if ($stored === false) {
                continue;
            }

            $position++;

            ProductImage::create([
                'product_id' => $product->id,
                'path'       => $stored,
                'sort_order' => $position,
                // The first image of the first row becomes the main one, which
                // is what "first one is the main image" in the template says.
                'is_primary' => $position === 0,
            ]);

            $result->imagesAttached++;
        }
    }

    // ------------------------------------------------------------ Internals

    private function cacheLookups(): void
    {
        $this->lookups = [
            'categories' => $this->keyByName(Category::query()->get(['id', 'name_en', 'name_ar'])),
            'sizes'      => $this->keyByName(Size::query()->get(['id', 'name_en', 'name_ar'])),
            'colors'     => $this->keyByName(Color::query()->get(['id', 'name_en', 'name_ar'])),
        ];
    }

    /**
     * Index by both languages, lower-cased, so a sheet filled in in Arabic
     * works as well as one filled in in English.
     *
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>  $models
     * @return array<string, int>
     */
    private function keyByName($models): array
    {
        $map = [];

        foreach ($models as $model) {
            foreach ([$model->name_en, $model->name_ar] as $name) {
                if (filled($name)) {
                    $map[mb_strtolower(trim((string) $name))] = $model->id;
                }
            }
        }

        return $map;
    }

    private function lookup(string $set, string $value): ?int
    {
        return $this->lookups[$set][mb_strtolower(trim($value))] ?? null;
    }

    /**
     * An EGP figure from a spreadsheet cell, in piastres.
     *
     * Spreadsheets hand over "1,200.00", "1200", or "EGP 1200" depending on
     * the cell's formatting, so everything but digits and a decimal point is
     * stripped before the value is read.
     */
    private function money(string $value): ?int
    {
        $clean = preg_replace('/[^0-9.]/', '', $value) ?? '';

        if ($clean === '' || ! is_numeric($clean)) {
            return null;
        }

        return Money::fromMajor((float) $clean);
    }

    /**
     * @return list<string>
     */
    private function splitImages(string $value): array
    {
        return collect(preg_split('/[,\n;]+/', $value) ?: [])
            ->map(static fn (string $name): string => trim($name))
            ->filter()
            ->values()
            ->all();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $suffix = 2;

        /*
         * withTrashed(), because Product soft-deletes: a deleted product keeps
         * its row and its slug, and the unique index does not care that it is
         * trashed. Without this, re-importing a product the shop once deleted
         * fails on a constraint the default scope hides.
         */
        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
