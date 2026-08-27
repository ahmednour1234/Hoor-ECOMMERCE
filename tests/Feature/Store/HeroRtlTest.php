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
     * The crop, not a mirror, decides which side the model stands on.
     */
    public function test_the_focal_point_flips_with_the_writing_direction(): void
    {
        $this->seedSlide();

        $html = $this->homepage('ar');

        $this->assertStringContainsString('object-position:22%', $html);
        $this->assertStringContainsString('object-position:78%', $html);
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
