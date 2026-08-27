<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\HeroSlide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The hero in Arabic.
 *
 * The fault worth guarding: the photograph was mirrored for RTL while the copy
 * was pushed to the edge with a logical property that also flips. Both moved,
 * and they landed on top of each other — the headline sat across the model.
 */
class HeroRtlTest extends TestCase
{
    use RefreshDatabase;

    private function seedSlide(): void
    {
        HeroSlide::factory()->create(['is_active' => true, 'position' => 0]);
    }

    private function homepage(string $locale): string
    {
        return $this->get(route('store.home', ['locale' => $locale]))
            ->assertOk()
            ->getContent();
    }

    /**
     * Mirroring a photograph of clothing reverses buttons and plackets, which
     * misrepresents the garment — and it was what pushed the model under the
     * copy in Arabic.
     */
    public function test_the_hero_photograph_is_never_mirrored(): void
    {
        $this->seedSlide();

        foreach (['en', 'ar'] as $locale) {
            $html = $this->homepage($locale);

            preg_match_all('#<img[^>]*hero[^>]*>#', $html, $images);

            $this->assertNotEmpty($images[0], "no hero image rendered for {$locale}");

            foreach ($images[0] as $tag) {
                $this->assertStringNotContainsString(
                    'scale-x',
                    $tag,
                    "the hero photograph must not be mirrored ({$locale})",
                );
            }
        }
    }

    /**
     * Each direction gets a plate composed for it.
     *
     * object-position could not solve this: the plates are 2.00 and the band
     * about 2.05, so object-cover crops nothing horizontally and the focal
     * point has nothing to move. The fix is a second plate with the subject
     * on the other side.
     */
    public function test_arabic_uses_the_right_handed_plates(): void
    {
        $this->seedSlide();

        $english = $this->homepage('en');
        $arabic = $this->homepage('ar');

        $this->assertStringContainsString('hero-1.jpg', $english);
        $this->assertStringNotContainsString('hero-1-rtl.jpg', $english);

        $this->assertStringContainsString('hero-1-rtl.jpg', $arabic);
    }

    /**
     * A slide uploaded through the admin has no right-handed twin, so it must
     * fall back to the image it does have rather than to a missing file.
     */
    public function test_a_slide_without_a_twin_falls_back_to_its_own_image(): void
    {
        $this->seedSlide();

        $html = $this->homepage('ar');

        // Nothing should point at a file that is not on the disk.
        preg_match_all('#/storage/(hero/[^"]+\.jpg)#', $html, $matches);

        $this->assertNotEmpty($matches[1]);

        foreach (array_unique($matches[1]) as $path) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Storage::disk(config('hoor.media.disk'))->exists($path),
                "the hero references {$path}, which is not on the disk",
            );
        }
    }

    /**
     * object-contain centred the model on desktop, so no side was ever free
     * for the words.
     */
    public function test_the_photograph_fills_its_side_rather_than_being_centred(): void
    {
        $this->seedSlide();

        $this->assertStringNotContainsString('md:object-contain', $this->homepage('ar'));
    }

    public function test_the_hero_renders_in_both_directions(): void
    {
        $this->seedSlide();

        $this->assertStringContainsString('dir="ltr"', $this->homepage('en'));
        $this->assertStringContainsString('dir="rtl"', $this->homepage('ar'));
    }
}
