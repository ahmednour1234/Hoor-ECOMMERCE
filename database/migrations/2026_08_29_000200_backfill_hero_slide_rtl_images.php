<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Name the Arabic plate on the slides that were seeded before the column
 * existed.
 *
 * Those rows relied on the filename convention — hero-1.jpg paired with
 * hero-1-rtl.jpg — which the hero resolved at render time. That lookup is
 * staying as a fallback, but a slide's photographs should be a property of the
 * row rather than something inferred from a string, so the shop can see and
 * change them in the admin.
 *
 * Only the seeded brand plates are touched, and only where the twin is really
 * on disk: an uploaded slide never followed the convention, and writing a path
 * to a file that is not there would put a broken image in the hero.
 */
return new class extends Migration
{
    private const PLATES = ['hero/hero-1.jpg', 'hero/hero-2.jpg', 'hero/hero-3.jpg'];

    public function up(): void
    {
        $disk = Storage::disk(config('hoor.media.disk'));

        foreach (self::PLATES as $plate) {
            $twin = preg_replace('/(\.jpg)$/i', '-rtl$1', $plate);

            if (! $disk->exists($twin)) {
                continue;
            }

            DB::table('hero_slides')
                ->where('image_path', $plate)
                // Never overwrite a plate the shop uploaded itself.
                ->whereNull('image_path_rtl')
                ->update(['image_path_rtl' => $twin]);
        }
    }

    public function down(): void
    {
        DB::table('hero_slides')
            ->whereIn('image_path', self::PLATES)
            ->update(['image_path_rtl' => null]);
    }
};
