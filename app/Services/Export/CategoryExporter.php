<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Models\Category;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color as SpoutColor;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * The category tree as a spreadsheet.
 *
 * Mainly a reference: the product sheet names its category in one cell, and
 * this is the list of what may go there. So the English name — the value the
 * importer matches on — is the first column, and the tree is written parent
 * first with its children beneath it, in the order the shop sorted them.
 */
class CategoryExporter
{
    /**
     * The columns, in order.
     *
     * @var array<string, string>
     */
    private const COLUMNS = [
        'name_en'  => 'Category (EN)',
        'name_ar'  => 'Category (AR)',
        'parent'   => 'Parent',
        'slug'     => 'Slug',
        'active'   => 'Active',
        'products' => 'Products',
    ];

    /**
     * Write the tree to a path, returning how many categories were written.
     */
    public function writeTo(string $path): int
    {
        $writer = new Writer();
        $writer->openToFile($path);

        $writer->getCurrentSheet()->setName('Categories');

        $writer->addRow(Row::fromValues(
            array_values(self::COLUMNS),
            (new Style())
                ->setFontBold()
                ->setFontColor(SpoutColor::WHITE)
                ->setBackgroundColor('082540'),
        ));

        $written = $this->writeTree($writer);

        $writer->close();

        return $written;
    }

    private function writeTree(Writer $writer): int
    {
        // withCount rather than counting per row: the tree is small, but an
        // N+1 here would still be a query per category for no reason.
        $categories = Category::query()
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->get();

        $children = $categories->whereNotNull('parent_id')->groupBy('parent_id');

        $written = 0;

        $parent = (new Style())->setFontBold();

        foreach ($categories->whereNull('parent_id') as $top) {
            $writer->addRow(Row::fromValues($this->line($top, null), $parent));
            $written++;

            foreach ($children->get($top->id, collect()) as $child) {
                $writer->addRow(Row::fromValues($this->line($child, $top)));
                $written++;
            }
        }

        /*
         * A category whose parent was deleted would otherwise never be
         * reached by the walk above, and would vanish from a file the shop
         * reads as the complete list.
         */
        $seen = $categories->whereNull('parent_id')->pluck('id')
            ->merge($children->flatten()->pluck('id'));

        foreach ($categories->whereNotIn('id', $seen) as $orphan) {
            $writer->addRow(Row::fromValues($this->line($orphan, null)));
            $written++;
        }

        return $written;
    }

    /** @return list<string> */
    private function line(Category $category, ?Category $parent): array
    {
        return [
            (string) $category->name_en,
            (string) $category->name_ar,
            (string) ($parent?->name_en ?? ''),
            (string) $category->slug,
            $category->is_active ? 'Yes' : 'No',
            (string) $category->products_count,
        ];
    }
}
