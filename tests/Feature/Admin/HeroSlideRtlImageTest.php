<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\HeroSlide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A slide carries a photograph per reading direction.
 *
 * The hero puts its copy in the half the model does not occupy, and direction
 * decides which half that is. Before this, the Arabic plate was found by
 * filename convention — hero-1.jpg beside hero-1-rtl.jpg — which only the
 * seeded brand plates follow. Every slide uploaded through the admin fell back
 * to its left-composed image, so the Arabic hero put the model under the words.
 */
class HeroSlideRtlImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('hoor.media.disk'));
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function photograph(string $name): UploadedFile
    {
        return UploadedFile::fake()->image($name, 2200, 1100);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'headline_en' => 'Denim that suits you',
            'headline_ar' => 'دنيم يناسبك',
            'position'    => 0,
            'is_active'   => 1,
        ];
    }

    // -------------------------------------------------------------- Storing

    public function test_a_slide_can_be_created_with_both_photographs(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.slides.store', ['locale' => 'en']), $this->payload([
                'image'     => $this->photograph('english.jpg'),
                'image_rtl' => $this->photograph('arabic.jpg'),
            ]))
            ->assertRedirect();

        $slide = HeroSlide::sole();

        $this->assertNotNull($slide->image_path_rtl);
        $this->assertNotSame($slide->image_path, $slide->image_path_rtl);

        Storage::disk(config('hoor.media.disk'))->assertExists($slide->image_path);
        Storage::disk(config('hoor.media.disk'))->assertExists($slide->image_path_rtl);
    }

    public function test_the_arabic_photograph_is_optional(): void
    {
        // A shop with one photograph must still be able to put a slide up.
        $this->actingAs($this->admin())
            ->post(route('admin.slides.store', ['locale' => 'en']), $this->payload([
                'image' => $this->photograph('english.jpg'),
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $slide = HeroSlide::sole();

        $this->assertNull($slide->image_path_rtl);

        // And it falls back rather than resolving to nothing.
        $this->assertSame($slide->imageUrl(), $slide->imageUrlRtl());
        $this->assertFalse($slide->hasRtlImage());
    }

    // ------------------------------------------------------------ Updating

    public function test_the_arabic_photograph_can_be_added_to_an_existing_slide(): void
    {
        $slide = HeroSlide::factory()->create(['image_path_rtl' => null]);

        $this->actingAs($this->admin())
            ->patch(route('admin.slides.update', ['locale' => 'en', 'slide' => $slide]), $this->payload([
                'image_rtl' => $this->photograph('arabic.jpg'),
            ]))
            ->assertRedirect();

        $this->assertTrue($slide->fresh()->hasRtlImage());
    }

    public function test_replacing_the_arabic_photograph_removes_the_old_file(): void
    {
        $disk = Storage::disk(config('hoor.media.disk'));

        $slide = HeroSlide::factory()->create();

        $this->actingAs($this->admin())
            ->patch(route('admin.slides.update', ['locale' => 'en', 'slide' => $slide]), $this->payload([
                'image_rtl' => $this->photograph('first.jpg'),
            ]));

        $first = $slide->fresh()->image_path_rtl;

        $this->actingAs($this->admin())
            ->patch(route('admin.slides.update', ['locale' => 'en', 'slide' => $slide]), $this->payload([
                'image_rtl' => $this->photograph('second.jpg'),
            ]));

        $second = $slide->fresh()->image_path_rtl;

        $this->assertNotSame($first, $second);

        // Otherwise every re-upload leaves a file nothing points at.
        $disk->assertMissing($first);
        $disk->assertExists($second);
    }

    public function test_the_arabic_photograph_can_be_removed(): void
    {
        $slide = HeroSlide::factory()->create();

        $this->actingAs($this->admin())
            ->patch(route('admin.slides.update', ['locale' => 'en', 'slide' => $slide]), $this->payload([
                'image_rtl' => $this->photograph('arabic.jpg'),
            ]));

        $this->assertTrue($slide->fresh()->hasRtlImage());

        $this->actingAs($this->admin())
            ->patch(route('admin.slides.update', ['locale' => 'en', 'slide' => $slide]), $this->payload([
                'remove_image_rtl' => 1,
            ]))
            ->assertRedirect();

        $this->assertFalse($slide->fresh()->hasRtlImage());
    }

    public function test_editing_the_copy_leaves_both_photographs_alone(): void
    {
        $slide = HeroSlide::factory()->create([
            'image_path'     => 'hero/english.jpg',
            'image_path_rtl' => 'hero/arabic.jpg',
        ]);

        // Neither file field is submitted, which is what happens whenever an
        // admin only reworded the headline.
        $this->actingAs($this->admin())
            ->patch(route('admin.slides.update', ['locale' => 'en', 'slide' => $slide]), $this->payload([
                'headline_en' => 'Reworded',
            ]))
            ->assertRedirect();

        $slide->refresh();

        $this->assertSame('hero/english.jpg', $slide->image_path);
        $this->assertSame('hero/arabic.jpg', $slide->image_path_rtl);
    }

    // ------------------------------------------------------------ Rendering

    public function test_each_locale_is_served_its_own_photograph(): void
    {
        HeroSlide::factory()->create([
            'image_path'     => 'hero/composed-for-english.jpg',
            'image_path_rtl' => 'hero/composed-for-arabic.jpg',
            'is_active'      => true,
        ]);

        $english = $this->get(route('store.home', ['locale' => 'en']))->assertOk()->getContent();
        $arabic  = $this->get(route('store.home', ['locale' => 'ar']))->assertOk()->getContent();

        $this->assertStringContainsString('composed-for-english.jpg', $english);
        $this->assertStringNotContainsString('composed-for-arabic.jpg', $english);

        $this->assertStringContainsString('composed-for-arabic.jpg', $arabic);
        $this->assertStringNotContainsString('composed-for-english.jpg', $arabic);
    }

    public function test_an_uploaded_twin_is_not_overwritten_by_the_filename_convention(): void
    {
        /*
         * The regression this guards.
         *
         * The component derives a twin by appending -rtl to the filename and
         * checking the disk. Run unconditionally, that lookup finds nothing
         * for an uploaded pair whose names do not follow the convention, and
         * replaces the admin's own Arabic plate with the English one.
         */
        HeroSlide::factory()->create([
            'image_path'     => 'hero/anything.jpg',
            'image_path_rtl' => 'hero/chosen-by-the-admin.jpg',
            'is_active'      => true,
        ]);

        $arabic = $this->get(route('store.home', ['locale' => 'ar']))->assertOk()->getContent();

        $this->assertStringContainsString('chosen-by-the-admin.jpg', $arabic);
        $this->assertStringNotContainsString('anything.jpg', $arabic);
    }

    public function test_a_slide_without_a_twin_still_renders_in_arabic(): void
    {
        HeroSlide::factory()->create([
            'image_path'     => 'hero/only-one.jpg',
            'image_path_rtl' => null,
            'is_active'      => true,
        ]);

        // A left-composed photograph in the Arabic hero is wrong, but a broken
        // one is worse.
        $this->assertStringContainsString(
            'only-one.jpg',
            $this->get(route('store.home', ['locale' => 'ar']))->assertOk()->getContent(),
        );
    }

    // ------------------------------------------------------------- Deleting

    public function test_deleting_a_slide_removes_both_photographs(): void
    {
        $disk = Storage::disk(config('hoor.media.disk'));

        $this->actingAs($this->admin())
            ->post(route('admin.slides.store', ['locale' => 'en']), $this->payload([
                'image'     => $this->photograph('english.jpg'),
                'image_rtl' => $this->photograph('arabic.jpg'),
            ]));

        $slide = HeroSlide::sole();
        $paths = [$slide->image_path, $slide->image_path_rtl];

        $this->actingAs($this->admin())
            ->delete(route('admin.slides.destroy', ['locale' => 'en', 'slide' => $slide]))
            ->assertRedirect();

        // The second file was the one at risk of being orphaned.
        foreach ($paths as $path) {
            $disk->assertMissing($path);
        }
    }

    // ----------------------------------------------------------- The form

    public function test_the_form_offers_a_field_for_each_direction(): void
    {
        $slide = HeroSlide::factory()->create(['image_path_rtl' => null]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.slides.edit', ['locale' => 'en', 'slide' => $slide]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="image"', $html);
        $this->assertStringContainsString('name="image_rtl"', $html);

        // Said plainly, because the consequence is invisible until an Arabic
        // visitor loads the page.
        $this->assertStringContainsString(__('content.slides.image_rtl_missing'), $html);
    }
}
