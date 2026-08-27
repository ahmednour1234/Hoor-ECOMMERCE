<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\HeroSlide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The homepage hero.
 *
 * The bug worth guarding: the copy block once sat outside the slide loop while
 * still reading $slide, so every slide rendered the *last* one's words. The
 * carousel looked static even though it was turning, because only the
 * photograph behind the text ever changed.
 */
class HeroSliderTest extends TestCase
{
    use RefreshDatabase;

    private function seedSlides(): void
    {
        foreach (['First eyebrow', 'Second eyebrow', 'Third eyebrow'] as $position => $eyebrow) {
            HeroSlide::factory()->create([
                'eyebrow_en' => $eyebrow,
                'eyebrow_ar' => 'عنوان '.($position + 1),
                'position'   => $position,
                'is_active'  => true,
            ]);
        }
    }

    private function homepage(string $locale = 'en'): string
    {
        return $this->get(route('store.home', ['locale' => $locale]))
            ->assertOk()
            ->getContent();
    }

    /**
     * Each slide must carry its own copy, not the last slide's.
     */
    public function test_every_slide_renders_its_own_words(): void
    {
        $this->seedSlides();

        $html = $this->homepage();

        foreach (['First eyebrow', 'Second eyebrow', 'Third eyebrow'] as $eyebrow) {
            $this->assertStringContainsString($eyebrow, $html);
        }
    }

    public function test_the_copy_appears_once_per_slide(): void
    {
        $this->seedSlides();

        $html = $this->homepage();

        // Three slides, three call-to-action buttons inside the carousel.
        $this->assertSame(3, substr_count($html, 'hero-drift'));
    }

    /**
     * The animation classes are bound to the active slide, so they restart on
     * every turn rather than running once at load.
     */
    public function test_the_animation_is_bound_to_the_active_slide(): void
    {
        $this->seedSlides();

        $html = $this->homepage();

        $this->assertStringContainsString("? 'hero-drift' : ''", $html);
        $this->assertStringContainsString("? 'hero-in' : ''", $html);
    }

    public function test_the_hero_renders_in_both_locales(): void
    {
        $this->seedSlides();

        $this->assertStringContainsString('First eyebrow', $this->homepage('en'));
        $this->assertStringContainsString('عنوان 1', $this->homepage('ar'));
    }
}
