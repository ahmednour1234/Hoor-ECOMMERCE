<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\HeroSlide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The hero shows the whole photograph, and can be stepped through by hand.
 *
 * The framing rule is arithmetic, not taste: object-cover keeps band ÷ plate
 * of the width, so a band whose ratio is below the plate's 2.00 crops the
 * model out at both sides. A fixed 44rem height at lg gave 1024/704 = 1.45,
 * which kept 73% and cut her off — the markup claimed nothing was cropped
 * there. Giving the band the plate's own ratio is what makes that true.
 */
class HeroFramingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * The hero renders only what the shop has published, so slides have to
         * exist before there is any framing to assert.
         *
         * Two of them, because the arrows are drawn only when there is
         * somewhere to go — one slide is a still photograph, not a slider.
         */
        HeroSlide::factory()->create(['is_active' => true, 'position' => 0]);
        HeroSlide::factory()->create(['is_active' => true, 'position' => 1]);
    }

    private function hero(string $locale = 'en'): string
    {
        return $this->get(route('store.home', ['locale' => $locale]))
            ->assertOk()
            ->getContent();
    }

    public function test_the_band_carries_the_plates_own_ratio_on_wide_screens(): void
    {
        $html = $this->hero();

        // Without this the band is a fixed height, its ratio drifts away from
        // the plate's, and object-cover trims whatever does not fit.
        $this->assertStringContainsString('lg:aspect-[2/1]', $html);
        $this->assertStringContainsString('lg:h-auto', $html);
    }

    public function test_the_declared_dimensions_match_the_plates(): void
    {
        $html = $this->hero();

        /*
         * The browser sizes the box from these before any CSS arrives. They
         * said 1122x1402 — a portrait 0.80 left over from the studio crops the
         * plates were composed from — so it reserved a tall slot for a wide
         * photograph and the hero jumped as it settled.
         */
        $this->assertStringContainsString('width="2200" height="1100"', $html);

        // Not asserted by absence: 1122x1402 is the product photograph's own
        // shape and is correct on the cards further down the same page.
        $hero = substr($html, (int) strpos($html, 'aria-roledescription="carousel"'), 4000);

        $this->assertStringNotContainsString('width="1122"', $hero);
    }

    public function test_the_band_never_grows_taller_than_the_window(): void
    {
        // 2:1 grows without limit: at 1920px the band came out 960px tall,
        // taller than the window, so the hero could not be seen in one go.
        $this->assertStringContainsString('lg:max-h-[calc(100vh-8rem)]', $this->hero());
    }

    public function test_the_photograph_is_never_trimmed_on_wide_screens(): void
    {
        $html = $this->hero();

        // contain rather than cover: an admin-uploaded slide need not be 2:1,
        // and cover would quietly cut whatever they chose.
        $this->assertStringContainsString('lg:object-contain', $html);
    }

    public function test_small_screens_still_crop_toward_the_model(): void
    {
        $html = $this->hero();

        /*
         * A 2:1 band on a phone would be 187px tall — too short for the
         * headline and the button — so those keep a height and accept a crop.
         * The anchor is what points that crop at the model rather than at the
         * empty half.
         */
        $this->assertStringContainsString('[object-position:12%_center]', $html);
        $this->assertStringContainsString('h-[30rem]', $html);
    }

    public function test_the_slider_has_a_control_in_each_direction(): void
    {
        $html = $this->hero();

        $this->assertStringContainsString(__('store.hero.previous'), $html);
        $this->assertStringContainsString(__('store.hero.next'), $html);

        // Anchored to the reading direction, so Arabic gets them on the sides
        // its readers expect rather than pinned to left and right.
        $this->assertStringContainsString('start-3', $html);
        $this->assertStringContainsString('end-3', $html);
    }

    public function test_the_arrows_are_drawn_rather_than_set_as_glyphs(): void
    {
        $html = $this->hero();

        // The ‹ and › glyphs inherited the body font size and rendered as a
        // hairline on a button wide enough to look empty.
        $this->assertStringNotContainsString('&#8249;', $html);
        $this->assertStringNotContainsString('&#8250;', $html);

        // The two chevron paths, left and right.
        $this->assertStringContainsString('M15 19l-7-7 7-7', $html);
        $this->assertStringContainsString('M9 5l7 7-7 7', $html);
    }

    public function test_the_controls_are_reachable_in_both_locales(): void
    {
        foreach (['en', 'ar'] as $locale) {
            $html = $this->hero($locale);

            $this->assertStringContainsString('M15 19l-7-7 7-7', $html);
            $this->assertStringContainsString('M9 5l7 7-7 7', $html);
        }
    }
}
