<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Banner;
use App\Models\HeroSlide;
use Illuminate\Support\Collection;

/**
 * The storefront's managed content: hero slides and promotional banners.
 *
 * Both are read on the homepage, so both are cached — and both fall back to
 * the brand's own artwork when an admin has not configured anything, so a
 * fresh install still looks like a shop rather than an empty frame.
 */
class ContentService
{
    private const CACHE_TTL = 300;

    /**
     * The brand's own hero plates.
     *
     * Wide 12:5 compositions with the model toward the start edge and open
     * ground after her, so the headline has clear space at every width.
     *
     * @var list<array{image: string, backdrop: string}>
     */
    /**
     * Slides for the hero, in the shape the component expects.
     *
     * @return list<array<string, mixed>>
     */
    public function heroSlides(): array
    {
        /*
         * Whatever the shop has published, and nothing else.
         *
         * There used to be a set of brand plates here for when no slide was
         * active. It meant deactivating every slide put three photographs back
         * on the homepage that the admin could not see, edit or remove from
         * any screen — the hero stopped answering to the database.
         *
         * An empty list is now honoured. The homepage has its own switch for
         * the hero section, which is the visible way to turn it off.
         */
        return $this->cached('hero_slides', fn (): array => HeroSlide::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (HeroSlide $slide): array => $slide->toSlideArray())
            ->all());
    }

    /**
     * Live banners for one placement.
     *
     * @return Collection<int, Banner>
     */
    public function banners(string $placement): Collection
    {
        /*
         * Not cached: a banner's visibility depends on the clock, and a
         * five-minute cache would keep a finished sale on screen past its end.
         * One indexed query on a handful of rows is cheap enough to run live.
         */
        return Banner::query()
            ->placement($placement)
            ->live()
            ->ordered()
            ->get();
    }

    /**
     * The first live banner for a placement, for slots that show only one.
     */
    public function banner(string $placement): ?Banner
    {
        return $this->banners($placement)->first();
    }

    /**
     * Drop the cached content after an admin edit.
     */
    public function flush(): void
    {
        \Illuminate\Support\Facades\Cache::forget($this->key('hero_slides'));
    }

    /**
     * @template T
     *
     * @param  callable(): T  $resolver
     * @return T
     */
    private function cached(string $name, callable $resolver): mixed
    {
        return \Illuminate\Support\Facades\Cache::remember($this->key($name), self::CACHE_TTL, $resolver);
    }

    private function key(string $name): string
    {
        // Slide copy is locale-dependent, so each locale caches separately.
        return 'hoor.content.'.$name.'.'.app()->getLocale();
    }
}
