<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\Export\CategoryExporter;
use App\Services\Export\ProductExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Downloading the catalogue as a spreadsheet.
 *
 * The product export is deliberately the import's own format, so the file can
 * be edited and handed back to update what is already there. That round-trip
 * is the point of it — it is not only a report.
 */
class CatalogExportController extends Controller
{
    public function __construct(
        private readonly ProductExporter $products,
        private readonly CategoryExporter $categories,
    ) {
    }

    /**
     * Products, one row per variant.
     */
    public function products(Request $request): BinaryFileResponse
    {
        // Reading the whole catalogue, prices and stock included, so it is
        // gated on the same permission as editing it rather than on merely
        // being able to see the admin.
        $this->authorize('create', Product::class);

        $filters = $request->validate([
            'status'      => ['nullable', 'string', 'in:'.implode(',', array_column(ProductStatus::cases(), 'value'))],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        return $this->download(
            'hoor-products-'.now()->format('Y-m-d-His').'.xlsx',
            fn (string $path): int => $this->products->writeTo($path, $filters),
        );
    }

    /**
     * The category tree.
     */
    public function categories(): BinaryFileResponse
    {
        $this->authorize('create', Category::class);

        return $this->download(
            'hoor-categories-'.now()->format('Y-m-d-His').'.xlsx',
            fn (string $path): int => $this->categories->writeTo($path),
        );
    }

    /**
     * Write to a temporary file and stream it back.
     *
     * The file is deleted after sending: an export holds the whole catalogue
     * with its cost prices, and leaving copies in storage would be a slow leak
     * of exactly the thing worth protecting.
     *
     * @param  \Closure(string): int  $write
     */
    private function download(string $name, \Closure $write): BinaryFileResponse
    {
        $path = storage_path('app/'.$name);

        $write($path);

        return response()->download($path, $name)->deleteFileAfterSend();
    }
}
