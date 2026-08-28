<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;
use App\Services\Export\CategoryExporter;
use App\Services\Export\ProductExporter;
use App\Services\Import\ProductImporter;
use App\Services\Import\ProductImportTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

/**
 * Exporting the catalogue, and handing it back.
 *
 * The round-trip is the point: an export that cannot be re-imported is a
 * report, not a way to edit the catalogue in bulk. So most of these read a
 * real generated file back through the real importer, rather than trusting
 * that the two formats agree.
 */
class CatalogExportTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = storage_path('app/testing-export-'.uniqid().'.xlsx');
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }

        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /**
     * A product with one variant, built from named reference data so the
     * export has real category, size and colour names to write.
     */
    private function product(array $attributes = []): Product
    {
        $category = Category::factory()->create(['name_en' => 'Jeans', 'name_ar' => 'الجينز']);
        $size     = Size::factory()->create(['name_en' => 'M']);
        $color    = Color::factory()->create(['name_en' => 'Indigo']);

        $product = Product::factory()->create($attributes + [
            'name_en'     => 'Wide Leg Jeans',
            'name_ar'     => 'جينز واسع',
            'category_id' => $category->id,
            'base_price'  => 120000,
            'sale_price'  => null,
        ]);

        ProductVariant::factory()->for($product)->create([
            // Derived from the product, so building two of them in one test
            // does not collide on the unique SKU.
            'sku'            => 'HOOR-JN-'.$product->id.'-M-IND',
            'size_id'        => $size->id,
            'color_id'       => $color->id,
            'stock_quantity' => 10,
        ]);

        return $product->refresh();
    }

    /** @return list<list<string>> */
    private function read(string $path): array
    {
        $reader = new Reader();
        $reader->open($path);

        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = array_map(static fn ($c): string => trim((string) $c), $row->toArray());
            }

            break;
        }

        $reader->close();

        return $rows;
    }

    /** @return array<string, int> */
    private function columns(): array
    {
        return array_flip(array_keys(ProductImportTemplate::COLUMNS));
    }

    // -------------------------------------------------------------- Products

    public function test_the_export_downloads(): void
    {
        $this->product();

        $this->actingAs($this->admin())
            ->get(route('admin.products.export', ['locale' => 'en']))
            ->assertOk()
            ->assertDownload();
    }

    public function test_a_customer_cannot_export_the_catalogue(): void
    {
        // Prices, stock and every draft product in one file. Not something a
        // signed-in shopper may pull.
        $this->actingAs(User::factory()->create())
            ->get(route('admin.products.export', ['locale' => 'en']))
            ->assertForbidden();
    }

    public function test_the_export_writes_one_row_per_variant(): void
    {
        $product = $this->product();

        ProductVariant::factory()->for($product)->create([
            'sku'            => 'HOOR-JN-01-L-IND',
            'size_id'        => Size::factory()->create(['name_en' => 'L'])->id,
            'color_id'       => $product->variants->first()->color_id,
            'stock_quantity' => 6,
        ]);

        app(ProductExporter::class)->writeTo($this->path);

        // Two rows of preamble, then one row per variant.
        $this->assertCount(4, $this->read($this->path));
    }

    public function test_the_export_keeps_the_two_preamble_rows_the_importer_skips(): void
    {
        $this->product();

        app(ProductExporter::class)->writeTo($this->path);

        $rows = $this->read($this->path);

        // The importer skips rows 1 and 2 whatever they hold. Drop the hint
        // row and the first real product is eaten as preamble.
        $this->assertSame(array_column(ProductImportTemplate::COLUMNS, 'label'), $rows[0]);
        $this->assertSame(array_column(ProductImportTemplate::COLUMNS, 'hint'), $rows[1]);
    }

    public function test_prices_export_as_plain_numbers_the_importer_accepts(): void
    {
        $this->product(['base_price' => 120000, 'sale_price' => 99950]);

        app(ProductExporter::class)->writeTo($this->path);

        $row = $this->read($this->path)[2];
        $columns = $this->columns();

        // A thousands separator would come back as text the importer refuses.
        $this->assertSame('1200.00', $row[$columns['price']]);
        $this->assertSame('999.50', $row[$columns['sale_price']]);
    }

    public function test_the_export_can_be_narrowed_to_one_status(): void
    {
        $this->product(['name_en' => 'Published One', 'status' => ProductStatus::Published]);
        $this->product(['name_en' => 'Draft One', 'status' => ProductStatus::Draft]);

        app(ProductExporter::class)->writeTo($this->path, ['status' => ProductStatus::Draft->value]);

        $names = array_column(array_slice($this->read($this->path), 2), $this->columns()['name_en']);

        $this->assertContains('Draft One', $names);
        $this->assertNotContains('Published One', $names);
    }

    // ---------------------------------------------------------- Round trip

    public function test_re_importing_an_untouched_export_updates_and_does_not_duplicate(): void
    {
        $product = $this->product();

        app(ProductExporter::class)->writeTo($this->path);

        $result = app(ProductImporter::class)->import($this->path, null);

        $this->assertSame([], $result->errors);

        // The whole point: uploading the same file twice must not leave the
        // shop with two of everything.
        $this->assertSame(1, Product::count());
        $this->assertSame(1, ProductVariant::count());
        $this->assertSame(1, $result->productsUpdated);
        $this->assertSame(0, $result->productsCreated);
        $this->assertSame($product->id, Product::first()->id);
    }

    public function test_editing_the_export_updates_the_existing_product(): void
    {
        $product = $this->product();

        app(ProductExporter::class)->writeTo($this->path);

        // What the shop actually does with the file: change a price and a
        // stock level, then upload it back.
        $this->rewrite($this->path, function (array $row, array $columns): array {
            $row[$columns['price']] = '1350.00';
            $row[$columns['stock']] = '42';

            return $row;
        });

        $result = app(ProductImporter::class)->import($this->path, null);

        $this->assertSame([], $result->errors);
        $this->assertSame(135000, $product->fresh()->base_price);
        $this->assertSame(42, $product->variants()->first()->stock_quantity);
        $this->assertSame(1, Product::count());
    }

    public function test_a_new_row_in_the_export_creates_a_product(): void
    {
        $this->product();

        app(ProductExporter::class)->writeTo($this->path);

        $rows = $this->read($this->path);
        $columns = $this->columns();

        $new = $rows[2];
        $new[$columns['sku']]     = 'HOOR-NEW-01-M-IND';
        $new[$columns['name_en']] = 'Straight Leg Jeans';
        $new[$columns['name_ar']] = 'جينز مستقيم';

        $rows[] = $new;

        $this->write($this->path, $rows);

        $result = app(ProductImporter::class)->import($this->path, null);

        $this->assertSame([], $result->errors);
        $this->assertSame(1, $result->productsCreated);
        $this->assertSame(1, $result->productsUpdated);
        $this->assertSame(2, Product::count());
    }

    // -------------------------------------------------------- Categories

    public function test_the_category_export_writes_the_tree(): void
    {
        $parent = Category::factory()->create([
            'name_en' => 'Jeans', 'name_ar' => 'الجينز', 'parent_id' => null,
        ]);

        Category::factory()->create([
            'name_en'   => 'Wide Leg Jeans',
            'name_ar'   => 'جينز واسع الساق',
            'parent_id' => $parent->id,
        ]);

        app(CategoryExporter::class)->writeTo($this->path);

        $rows = $this->read($this->path);

        $this->assertCount(3, $rows);             // header + two categories
        $this->assertSame('Jeans', $rows[1][0]);
        $this->assertSame('Wide Leg Jeans', $rows[2][0]);
        $this->assertSame('Jeans', $rows[2][2]);  // the child names its parent
    }

    public function test_the_category_export_downloads(): void
    {
        Category::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.categories.export', ['locale' => 'en']))
            ->assertOk()
            ->assertDownload();
    }

    // ------------------------------------------------------------- Helpers

    /**
     * Rewrite every data row through a callback.
     *
     * @param  \Closure(array<int, string>, array<string, int>): array<int, string>  $edit
     */
    private function rewrite(string $path, \Closure $edit): void
    {
        $rows = $this->read($path);
        $columns = $this->columns();

        foreach ($rows as $i => $row) {
            if ($i < 2) {
                continue;
            }

            $rows[$i] = $edit($row, $columns);
        }

        $this->write($path, $rows);
    }

    /** @param  list<list<string>>  $rows */
    private function write(string $path, array $rows): void
    {
        $writer = new Writer();
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName('Products');

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();
    }
}
