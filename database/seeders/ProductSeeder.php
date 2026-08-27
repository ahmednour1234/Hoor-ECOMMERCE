<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Casts\Money;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Realistic HOOR demo catalog.
 *
 * Each definition declares the colours and sizes a product is offered in; the
 * seeder expands that into the full variant matrix with deliberately varied
 * stock levels, so the low-stock and sold-out paths have real data to exercise.
 *
 * Every write is an updateOrCreate keyed on a natural key, which makes the
 * seeder safe to re-run against an existing database.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = Size::query()->ordered()->get()->keyBy('code');
        $colors = Color::query()->ordered()->get()->keyBy('slug');
        $categories = Category::query()->get()->keyBy('slug');

        foreach ($this->definitions() as $definition) {
            DB::transaction(function () use ($definition, $sizes, $colors, $categories): void {
                $this->seedProduct($definition, $sizes, $colors, $categories);
            });
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  Collection<string, Size>  $sizes
     * @param  Collection<string, Color>  $colors
     * @param  Collection<string, Category>  $categories
     */
    private function seedProduct(array $definition, Collection $sizes, Collection $colors, Collection $categories): void
    {
        $category = $categories->get($definition['category']);

        if ($category === null) {
            return;
        }

        $product = Product::query()->updateOrCreate(
            ['slug' => $definition['slug']],
            [
                'category_id'          => $category->id,
                'name_en'              => $definition['name_en'],
                'name_ar'              => $definition['name_ar'],
                'short_description_en' => $definition['short_en'],
                'short_description_ar' => $definition['short_ar'],
                'description_en'       => $definition['description_en'],
                'description_ar'       => $definition['description_ar'],
                'base_price'           => Money::fromMajor($definition['price']),
                'sale_price'           => isset($definition['sale']) ? Money::fromMajor($definition['sale']) : null,
                'status'               => ProductStatus::Published,
                'is_featured'          => $definition['featured'] ?? false,
                'is_new'               => $definition['new'] ?? false,
                'fabric_en'            => $definition['fabric_en'],
                'fabric_ar'            => $definition['fabric_ar'],
                'care_en'              => 'Machine wash cold, inside out. Do not tumble dry.',
                'care_ar'              => 'يُغسل بماء بارد مقلوباً. لا يُجفف بالمجفف.',
                'meta_title_en'        => $definition['name_en'].' | HOOR',
                'meta_title_ar'        => $definition['name_ar'].' | حور',
                'meta_description_en'  => $definition['short_en'],
                'meta_description_ar'  => $definition['short_ar'],
                'published_at'         => now(),
            ],
        );

        $this->seedVariants($product, $definition, $sizes, $colors);
        $this->seedImages($product, $definition);
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  Collection<string, Size>  $sizes
     * @param  Collection<string, Color>  $colors
     */
    private function seedVariants(Product $product, array $definition, Collection $sizes, Collection $colors): void
    {
        foreach ($definition['colors'] as $colorSlug) {
            $color = $colors->get($colorSlug);

            if ($color === null) {
                continue;
            }

            foreach ($definition['sizes'] as $sizeCode) {
                $size = $sizes->get($sizeCode);

                if ($size === null) {
                    continue;
                }

                ProductVariant::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'color_id'   => $color->id,
                        'size_id'    => $size->id,
                    ],
                    [
                        'sku' => sprintf(
                            'HOOR-%s-%s-%s',
                            str_pad((string) $product->id, 4, '0', STR_PAD_LEFT),
                            strtoupper(substr($color->slug, 0, 3)),
                            $size->code,
                        ),
                        'stock_quantity'      => $this->stockFor($sizeCode),
                        'low_stock_threshold' => 3,
                        'is_active'           => true,
                    ],
                );
            }
        }
    }

    /**
     * Mid sizes sell fastest, so they carry the thinnest stock. This mirrors how
     * a real denim range depletes and gives every stock state demo coverage.
     */
    private function stockFor(string $sizeCode): int
    {
        return match ($sizeCode) {
            'XS'    => 0,   // sold out
            'S'     => 2,   // low stock
            'M'     => 3,   // exactly at threshold
            'L'     => 14,
            'XL'    => 9,
            'XXL'   => 6,
            default => 8,
        };
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function seedImages(Product $product, array $definition): void
    {
        // The demo catalog ships without photography; placeholder paths keep the
        // gallery structure exercisable until real assets are uploaded.
        if ($product->images()->exists()) {
            return;
        }

        // Each definition names the brand photographs it ships with. Files that
        // are not on disk are skipped rather than creating rows that point at
        // nothing.
        $disk = Storage::disk(config('hoor.media.disk'));
        $order = 0;

        foreach ($definition['images'] ?? [] as $file) {
            $path = 'products/'.$file;

            if (! $disk->exists($path)) {
                continue;
            }

            ProductImage::query()->create([
                'product_id' => $product->id,
                'path'       => $path,
                'alt_en'     => $definition['name_en'],
                'alt_ar'     => $definition['name_ar'],
                'sort_order' => $order,
                'is_primary' => $order === 0,
            ]);

            $order++;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function definitions(): array
    {
        return require __DIR__.'/data/hoor-catalog.php';
    }
}
