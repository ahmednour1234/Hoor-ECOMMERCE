<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Casts\Money;
use App\Models\Product;
use App\Services\Import\ProductImportTemplate;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color as SpoutColor;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * The catalogue as a spreadsheet.
 *
 * Written in the import's own shape, so a download can be edited and handed
 * straight back: same columns, in the same order, behind the same two rows of
 * preamble. The importer reads columns by position and skips rows 1 and 2
 * unconditionally, so any drift here turns a re-import into silent nonsense.
 *
 * One row per variant, because that is the grain the importer reads. A product
 * with three sizes exports as three rows repeating the product details, which
 * is the shape the template's worked example teaches.
 */
class ProductExporter
{
    /**
     * Streamed in chunks: a catalogue big enough to be worth exporting is big
     * enough to exhaust memory if it is all loaded first.
     */
    private const CHUNK = 200;

    /**
     * Write the catalogue to a path, returning how many rows were written.
     *
     * @param  array<string, mixed>  $filters  Narrows the export; empty takes
     *                                         everything, drafts included.
     */
    public function writeTo(string $path, array $filters = []): int
    {
        $writer = new Writer();
        $writer->openToFile($path);

        // The importer looks for a sheet named "Products" before falling back
        // to the first sheet, so it is named rather than left as "Sheet1".
        $writer->getCurrentSheet()->setName('Products');

        $this->writeHeader($writer);
        $written = $this->writeProducts($writer, $filters);

        $writer->close();

        return $written;
    }

    private function writeHeader(Writer $writer): void
    {
        $header = (new Style())
            ->setFontBold()
            ->setFontColor(SpoutColor::WHITE)
            ->setBackgroundColor('082540');   // hoor navy

        $hint = (new Style())
            ->setFontItalic()
            ->setFontSize(9)
            ->setFontColor('808080');

        $writer->addRow(Row::fromValues(
            array_column(ProductImportTemplate::COLUMNS, 'label'),
            $header,
        ));

        /*
         * The hint row is not decoration. The importer skips rows 1 and 2
         * whatever they hold, so leaving it out would feed the first real
         * product in as preamble and lose it without an error.
         */
        $writer->addRow(Row::fromValues(
            array_column(ProductImportTemplate::COLUMNS, 'hint'),
            $hint,
        ));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function writeProducts(Writer $writer, array $filters): int
    {
        $written = 0;

        $query = Product::query()
            ->with([
                'category:id,name_en',
                'variants'       => fn ($q) => $q->orderBy('id'),
                'variants.size:id,name_en',
                'variants.color:id,name_en',
                'images'         => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
            ])
            ->orderBy('id');

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['category_id'] ?? null)) {
            $query->where('category_id', $filters['category_id']);
        }

        $query->chunk(self::CHUNK, function ($products) use ($writer, &$written): void {
            foreach ($products as $product) {
                foreach ($this->rowsFor($product) as $row) {
                    $writer->addRow(Row::fromValues($row));
                    $written++;
                }
            }
        });

        return $written;
    }

    /**
     * One row per variant.
     *
     * A product with no variants still gets a row. Omitting it would make the
     * export a quietly lossy copy of the catalogue, and the shop would
     * re-import it and wonder where the product went.
     *
     * @return list<list<string>>
     */
    private function rowsFor(Product $product): array
    {
        $images = $product->images
            // Bare file names, because that is what the importer resolves
            // against the uploaded images folder — a stored path would not be
            // found there.
            ->map(fn ($image): string => basename((string) $image->path))
            ->implode(', ');

        $shared = [
            'name_en'        => (string) $product->name_en,
            'name_ar'        => (string) $product->name_ar,
            'category'       => (string) ($product->category?->name_en ?? ''),
            'price'          => $this->money($product->base_price),
            'sale_price'     => $this->money($product->sale_price),
            'images'         => $images,
            'description_en' => (string) $product->description_en,
            'description_ar' => (string) $product->description_ar,
        ];

        if ($product->variants->isEmpty()) {
            return [$this->line($shared + ['sku' => '', 'size' => '', 'color' => '', 'stock' => ''])];
        }

        return $product->variants
            ->map(fn ($variant): array => $this->line($shared + [
                'sku'   => (string) $variant->sku,
                'size'  => (string) ($variant->size?->name_en ?? ''),
                'color' => (string) ($variant->color?->name_en ?? ''),
                'stock' => (string) $variant->stock_quantity,
            ]))
            ->all();
    }

    /**
     * Order the cells the way the importer reads them.
     *
     * Built from the template's own column list rather than a second
     * hand-maintained order, so a column added there cannot leave the two out
     * of step.
     *
     * @param  array<string, string>  $values
     * @return list<string>
     */
    private function line(array $values): array
    {
        $line = [];

        foreach (array_keys(ProductImportTemplate::COLUMNS) as $key) {
            $line[] = $values[$key] ?? '';
        }

        return $line;
    }

    /**
     * Piastres back to the major unit the sheet is filled in with.
     *
     * Unformatted digits deliberately: a thousands separator would come back
     * as text the importer refuses.
     */
    private function money(?int $piastres): string
    {
        return $piastres === null ? '' : number_format(Money::toMajor($piastres), 2, '.', '');
    }
}
