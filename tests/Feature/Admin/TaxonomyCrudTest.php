<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Categories, colours and sizes: the lookup data the product form depends on.
 */
class TaxonomyCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->admin()->create();
        $this->staff = User::factory()->staff()->create();
    }

    // ------------------------------------------------------------ Categories

    public function test_admin_can_create_a_category(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.categories.store', ['locale' => 'en']), [
                'name_en'    => 'Denim Skirts',
                'name_ar'    => 'جيبات الدنيم',
                'is_active'  => 1,
                'sort_order' => 2,
            ])
            ->assertRedirect(route('admin.categories.index', ['locale' => 'en']))
            ->assertSessionHasNoErrors();

        $category = Category::query()->firstOrFail();

        $this->assertSame('Denim Skirts', $category->name_en);
        $this->assertSame('denim-skirts', $category->slug);
    }

    public function test_category_banner_is_stored_on_disk(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.categories.store', ['locale' => 'en']), [
                'name_en' => 'Jackets',
                'name_ar' => 'جاكيتات',
                'image'   => UploadedFile::fake()->image('banner.jpg'),
            ])
            ->assertSessionHasNoErrors();

        $category = Category::query()->firstOrFail();

        $this->assertNotNull($category->image);
        Storage::disk('public')->assertExists($category->image);
    }

    public function test_replacing_a_category_banner_removes_the_old_file(): void
    {
        $old = 'categories/old.jpg';
        Storage::disk('public')->put($old, 'binary');

        $category = Category::factory()->create(['image' => $old]);

        $this->actingAs($this->admin)
            ->put(route('admin.categories.update', ['locale' => 'en', 'category' => $category]), [
                'name_en' => $category->name_en,
                'name_ar' => $category->name_ar,
                'slug'    => $category->slug,
                'image'   => UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertSessionHasNoErrors();

        $category->refresh();

        $this->assertNotSame($old, $category->image);
        Storage::disk('public')->assertExists($category->image);
        Storage::disk('public')->assertMissing($old);
    }

    public function test_a_category_cannot_become_its_own_parent(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.categories.update', ['locale' => 'en', 'category' => $category]), [
                'name_en'   => $category->name_en,
                'name_ar'   => $category->name_ar,
                'slug'      => $category->slug,
                'parent_id' => $category->id,
            ])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_a_category_cannot_be_parented_to_its_own_descendant(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->childOf($parent)->create();

        // Making the parent a child of its own child would create a loop.
        $this->actingAs($this->admin)
            ->put(route('admin.categories.update', ['locale' => 'en', 'category' => $parent]), [
                'name_en'   => $parent->name_en,
                'name_ar'   => $parent->name_ar,
                'slug'      => $parent->slug,
                'parent_id' => $child->id,
            ])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_a_category_holding_products_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->actingAs($this->admin)
            ->from(route('admin.categories.index', ['locale' => 'en']))
            ->delete(route('admin.categories.destroy', ['locale' => 'en', 'category' => $category]))
            ->assertSessionHasErrors('category');

        $this->assertNotSoftDeleted($category);
    }

    public function test_only_administrators_may_delete_categories(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->staff)
            ->delete(route('admin.categories.destroy', ['locale' => 'en', 'category' => $category]))
            ->assertForbidden();
    }

    public function test_staff_can_still_edit_categories(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->staff)
            ->get(route('admin.categories.edit', ['locale' => 'en', 'category' => $category]))
            ->assertOk();
    }

    // ---------------------------------------------------------------- Colours

    public function test_admin_can_create_a_colour_and_the_hex_is_normalised(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.colors.store', ['locale' => 'en']), [
                'name_en'   => 'Indigo',
                'name_ar'   => 'نيلي',
                'hex'       => '2b4166',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.colors.index', ['locale' => 'en']))
            ->assertSessionHasNoErrors();

        $color = Color::query()->firstOrFail();

        $this->assertSame('#2B4166', $color->hex);
        $this->assertSame('indigo', $color->slug);
    }

    public function test_invalid_hex_values_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.colors.store', ['locale' => 'en']), [
                'name_en' => 'Broken',
                'name_ar' => 'معطل',
                'hex'     => 'not-a-colour',
            ])
            ->assertSessionHasErrors('hex');
    }

    public function test_a_colour_used_by_a_variant_cannot_be_deleted(): void
    {
        $color = Color::factory()->create();
        ProductVariant::factory()->for($color)->create();

        $this->actingAs($this->admin)
            ->from(route('admin.colors.index', ['locale' => 'en']))
            ->delete(route('admin.colors.destroy', ['locale' => 'en', 'color' => $color]))
            ->assertSessionHasErrors('color');

        $this->assertModelExists($color);
    }

    // ----------------------------------------------------------------- Sizes

    public function test_admin_can_create_a_size(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.sizes.store', ['locale' => 'en']), [
                'name_en'    => 'XL',
                'name_ar'    => 'XL',
                'code'       => 'xl',
                'sort_order' => 4,
                'is_active'  => 1,
            ])
            ->assertRedirect(route('admin.sizes.index', ['locale' => 'en']))
            ->assertSessionHasNoErrors();

        $size = Size::query()->firstOrFail();

        // Codes are upper-cased so XL and xl can never coexist.
        $this->assertSame('XL', $size->code);
        $this->assertSame(4, $size->sort_order);
    }

    public function test_size_codes_must_be_unique(): void
    {
        Size::factory()->create(['code' => 'M']);

        $this->actingAs($this->admin)
            ->post(route('admin.sizes.store', ['locale' => 'en']), [
                'name_en'    => 'Medium',
                'name_ar'    => 'متوسط',
                'code'       => 'm',
                'sort_order' => 2,
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_a_size_used_by_a_variant_cannot_be_deleted(): void
    {
        $size = Size::factory()->create();
        ProductVariant::factory()->for($size)->create();

        $this->actingAs($this->admin)
            ->from(route('admin.sizes.index', ['locale' => 'en']))
            ->delete(route('admin.sizes.destroy', ['locale' => 'en', 'size' => $size]))
            ->assertSessionHasErrors('size');

        $this->assertModelExists($size);
    }

    // -------------------------------------------------------------- Rendering

    public function test_every_admin_catalog_screen_renders_in_arabic(): void
    {
        Category::factory()->create();
        Color::factory()->create();
        Size::factory()->create();

        foreach (['categories', 'colors', 'sizes', 'products'] as $section) {
            $this->actingAs($this->admin)
                ->get(route("admin.{$section}.index", ['locale' => 'ar']))
                ->assertOk()
                ->assertSee('dir="rtl"', escape: false);
        }
    }

    public function test_product_form_renders_all_five_tabs(): void
    {
        Category::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.products.create', ['locale' => 'en']))
            ->assertOk();

        foreach (['general', 'pricing', 'images', 'variants', 'seo'] as $tab) {
            $response->assertSee(__("catalog.tabs.{$tab}"));
        }
    }
}
