<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Services\Import\ImportArchive;
use App\Services\Import\ProductImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;
use ZipArchive;

/**
 * Importing a catalogue from a spreadsheet.
 *
 * A spreadsheet is filled in by hand and will contain mistakes, so most of
 * these are about what the import refuses — and about refusing it whole, since
 * a half-imported catalogue is worse than none.
 */
class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Size $small;

    private Size $large;

    private Color $color;

    private string $scratch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::factory()->create(['name_en' => 'Jeans', 'name_ar' => 'جينز']);
        $this->small = Size::factory()->create(['name_en' => 'S', 'name_ar' => 'S', 'code' => 'S']);
        $this->large = Size::factory()->create(['name_en' => 'L', 'name_ar' => 'L', 'code' => 'L']);
        $this->color = Color::factory()->create(['name_en' => 'Indigo', 'name_ar' => 'نيلي']);

        $this->scratch = sys_get_temp_dir().'/hoor-import-test-'.bin2hex(random_bytes(4));
        @mkdir($this->scratch, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->scratch.'/*') ?: [] as $file) {
            is_dir($file) ? array_map('unlink', glob($file.'/*') ?: []) && @rmdir($file) : @unlink($file);
        }

        @rmdir($this->scratch);

        parent::tearDown();
    }

    /**
     * One row, with sensible defaults that every test can override.
     *
     * @param  array<string, string>  $overrides
     * @return list<string>
     */
    private function row(array $overrides = []): array
    {
        $values = array_merge([
            'sku'        => 'IMP-01-S',
            'name_en'    => 'Test Jeans',
            'name_ar'    => 'جينز تجريبي',
            'category'   => 'Jeans',
            'price'      => '1200',
            'sale_price' => '',
            'size'       => 'S',
            'color'      => 'Indigo',
            'stock'      => '5',
            'images'     => '',
            'desc_en'    => '',
            'desc_ar'    => '',
        ], $overrides);

        return array_values($values);
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function sheet(array $rows): string
    {
        $path = $this->scratch.'/products.xlsx';

        $writer = new Writer();
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName('Products');

        // The importer skips the first two rows: headers and hints.
        $writer->addRow(Row::fromValues(array_fill(0, 12, 'header')));
        $writer->addRow(Row::fromValues(array_fill(0, 12, 'hint')));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return $path;
    }

    private function importer(): ProductImporter
    {
        return app(ProductImporter::class);
    }

    // ------------------------------------------------------------ Importing

    public function test_a_product_and_its_variants_are_imported(): void
    {
        $result = $this->importer()->import($this->sheet([
            $this->row(['sku' => 'IMP-01-S', 'size' => 'S', 'stock' => '7']),
            $this->row(['sku' => 'IMP-01-L', 'size' => 'L', 'stock' => '3']),
        ]));

        $this->assertFalse($result->hasErrors());

        // Two rows, one product: rows sharing a name are the same garment.
        $this->assertSame(1, $result->productsCreated);
        $this->assertSame(2, $result->variantsCreated);

        $product = Product::query()->where('name_en', 'Test Jeans')->firstOrFail();

        $this->assertSame(120000, $product->base_price);
        $this->assertSame(2, $product->variants()->count());
        $this->assertSame(7, ProductVariant::query()->where('sku', 'IMP-01-S')->value('stock_quantity'));
    }

    /**
     * Imported products should not appear in the shop until someone has looked
     * at them.
     */
    public function test_imported_products_are_drafts(): void
    {
        $this->importer()->import($this->sheet([$this->row()]));

        $this->assertSame(
            \App\Enums\ProductStatus::Draft,
            Product::query()->where('name_en', 'Test Jeans')->value('status'),
        );
    }

    public function test_prices_are_read_however_the_spreadsheet_formatted_them(): void
    {
        foreach (['1200' => 120000, '1,200.00' => 120000, 'EGP 1200' => 120000, '1200.50' => 120050] as $written => $expected) {
            Product::query()->delete();

            $this->importer()->import($this->sheet([
                $this->row(['price' => (string) $written]),
            ]));

            $this->assertSame(
                $expected,
                Product::query()->where('name_en', 'Test Jeans')->value('base_price'),
                "for a cell reading: {$written}",
            );
        }
    }

    /**
     * A sheet filled in in Arabic should import as readily as one in English.
     */
    public function test_arabic_category_and_size_names_are_matched(): void
    {
        $result = $this->importer()->import($this->sheet([
            $this->row(['category' => 'جينز', 'color' => 'نيلي']),
        ]));

        $this->assertFalse($result->hasErrors(), json_encode($result->errors));
        $this->assertSame(1, $result->productsCreated);
    }

    public function test_a_second_import_updates_rather_than_duplicates(): void
    {
        $this->importer()->import($this->sheet([$this->row(['stock' => '5'])]));
        $this->importer()->import($this->sheet([$this->row(['stock' => '9'])]));

        $this->assertSame(1, Product::query()->where('name_en', 'Test Jeans')->count());
        $this->assertSame(9, ProductVariant::query()->where('sku', 'IMP-01-S')->value('stock_quantity'));
    }

    // ------------------------------------------------------------- Refusing

    /**
     * A sheet with a mistake in row 180 must not leave 179 products imported.
     */
    public function test_nothing_is_imported_when_any_row_is_wrong(): void
    {
        $result = $this->importer()->import($this->sheet([
            $this->row(['sku' => 'IMP-01-S']),
            $this->row(['sku' => 'IMP-01-L', 'size' => 'L', 'category' => 'Nonexistent']),
        ]));

        $this->assertTrue($result->hasErrors());

        // Not even the row that was fine.
        $this->assertSame(0, Product::query()->count());
        $this->assertSame(0, ProductVariant::query()->count());
    }

    public function test_an_unknown_category_names_the_row(): void
    {
        $result = $this->importer()->import($this->sheet([
            $this->row(['category' => 'Handbags']),
        ]));

        $this->assertTrue($result->hasErrors());
        $this->assertSame(3, $result->errors[0]['row']);
        $this->assertStringContainsString('Handbags', $result->errors[0]['message']);
    }

    public function test_a_missing_required_column_is_refused(): void
    {
        $result = $this->importer()->import($this->sheet([
            $this->row(['sku' => '']),
        ]));

        $this->assertTrue($result->hasErrors());
    }

    public function test_a_duplicate_sku_in_the_sheet_is_refused(): void
    {
        $result = $this->importer()->import($this->sheet([
            $this->row(['sku' => 'SAME', 'size' => 'S']),
            $this->row(['sku' => 'SAME', 'size' => 'L']),
        ]));

        $this->assertTrue($result->hasErrors());
    }

    /**
     * The database forbids two variants of one product sharing a size and
     * colour; without this check the import would die mid-transaction.
     */
    public function test_the_same_size_and_colour_twice_is_refused(): void
    {
        $result = $this->importer()->import($this->sheet([
            $this->row(['sku' => 'IMP-A', 'size' => 'S']),
            $this->row(['sku' => 'IMP-B', 'size' => 'S']),
        ]));

        $this->assertTrue($result->hasErrors());
        $this->assertSame(0, Product::query()->count());
    }

    public function test_a_sale_price_above_the_price_is_refused(): void
    {
        $result = $this->importer()->import($this->sheet([
            $this->row(['price' => '1000', 'sale_price' => '1500']),
        ]));

        $this->assertTrue($result->hasErrors());
    }

    public function test_a_non_numeric_stock_is_refused(): void
    {
        $result = $this->importer()->import($this->sheet([
            $this->row(['stock' => 'plenty']),
        ]));

        $this->assertTrue($result->hasErrors());
    }

    public function test_an_empty_sheet_says_so(): void
    {
        $result = $this->importer()->import($this->sheet([]));

        $this->assertTrue($result->hasErrors());
    }

    // -------------------------------------------------------------- The zip

    public function test_a_zip_yields_a_sheet_and_an_images_folder(): void
    {
        $zip = $this->buildZip();

        $archive = ImportArchive::fromUpload(
            new UploadedFile($zip, 'catalogue.zip', 'application/zip', null, true),
        );

        $this->assertFileExists($archive->sheetPath);
        $this->assertNotNull($archive->imageDirectory);
        $this->assertFileExists($archive->imageDirectory.'/photo.png');

        $archive->cleanUp();
    }

    /**
     * An archive entry named "../../.env" must not write outside the folder we
     * unpack into.
     */
    public function test_a_traversing_entry_cannot_escape(): void
    {
        $zip = $this->buildZip(hostile: true);

        $archive = ImportArchive::fromUpload(
            new UploadedFile($zip, 'catalogue.zip', 'application/zip', null, true),
        );

        // Two levels up from the images folder is the system temp directory.
        $this->assertFileDoesNotExist(dirname($archive->imageDirectory, 2).'/escaped.txt');

        $archive->cleanUp();
    }

    public function test_a_zip_without_a_spreadsheet_is_refused(): void
    {
        $path = $this->scratch.'/no-sheet.zip';

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('images/photo.png', 'not really a png');
        $zip->close();

        $this->expectException(\RuntimeException::class);

        ImportArchive::fromUpload(new UploadedFile($path, 'x.zip', 'application/zip', null, true));
    }

    public function test_unpacked_files_are_removed_afterwards(): void
    {
        $archive = ImportArchive::fromUpload(
            new UploadedFile($this->buildZip(), 'catalogue.zip', 'application/zip', null, true),
        );

        $directory = $archive->imageDirectory;

        $archive->cleanUp();

        $this->assertDirectoryDoesNotExist($directory);
    }

    private function buildZip(bool $hostile = false): string
    {
        $sheet = $this->sheet([$this->row(['images' => 'photo.png'])]);
        $path = $this->scratch.'/catalogue.zip';

        @unlink($path);

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFile($sheet, 'products.xlsx');
        $zip->addFromString('images/photo.png', 'pretend png bytes');

        if ($hostile) {
            $zip->addFromString('../../escaped.txt', 'must not land outside');
        }

        $zip->close();

        return $path;
    }

    // ---------------------------------------------------------------- Admin

    public function test_the_import_page_needs_permission(): void
    {
        $customer = \App\Models\User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('admin.products.import.create', ['locale' => 'en']))
            ->assertForbidden();
    }

    public function test_staff_can_download_a_template(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('admin.products.import.template', ['locale' => 'en']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_the_import_screens_render_in_both_locales(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin', 'is_active' => true]);

        foreach (['en', 'ar'] as $locale) {
            $this->actingAs($admin)
                ->get(route('admin.products.import.create', ['locale' => $locale]))
                ->assertOk();
        }
    }
}
