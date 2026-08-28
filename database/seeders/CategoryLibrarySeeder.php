<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The category tree a modest denim shop actually sells from.
 *
 * Built so a spreadsheet import finds a match for whatever the shop types.
 * ProductImporter looks categories up **by name**, in either language, so two
 * rules follow from that and are enforced here:
 *
 *   - every name is unique across the whole tree, parents and children alike.
 *     Two categories called "Wide Leg" under different parents would collide
 *     in the lookup and the import would silently pick one of them.
 *
 *   - children are named so they read on their own. "Wide Leg Jeans" rather
 *     than "Wide Leg", because the sheet has one column and no way to say
 *     which parent was meant.
 *
 * Existing categories are never overwritten: the shop may have renamed one or
 * written its own description, and a reseed must not undo that.
 */
class CategoryLibrarySeeder extends Seeder
{
    /**
     * parent => [name_en, name_ar, children].
     *
     * @var list<array{0: string, 1: string, 2: list<array{0: string, 1: string}>}>
     */
    private const TREE = [
        ['Jeans', 'الجينز', [
            ['Wide Leg Jeans',      'جينز واسع الساق'],
            ['Straight Leg Jeans',  'جينز مستقيم'],
            ['Mom Jeans',           'موم جينز'],
            ['Flared Jeans',        'جينز شارلستون'],
            ['Boyfriend Jeans',     'جينز بويفريند'],
            ['Skinny Jeans',        'جينز ضيق'],
            ['Cargo Jeans',         'جينز كارجو'],
            ['High Waist Jeans',    'جينز خصر عالٍ'],
        ]],

        ['Denim Skirts', 'جيبات الدنيم', [
            ['Long Denim Skirts',   'جيبات دنيم طويلة'],
            ['A-Line Denim Skirts', 'جيبات دنيم واسعة'],
            ['Pencil Denim Skirts', 'جيبات دنيم ضيقة'],
            ['Buttoned Denim Skirts', 'جيبات دنيم بأزرار'],
        ]],

        ['Denim Jackets', 'جاكيتات الدنيم', [
            ['Oversized Denim Jackets', 'جاكيتات دنيم أوفرسايز'],
            ['Classic Denim Jackets',   'جاكيتات دنيم كلاسيكية'],
            ['Cropped Denim Jackets',   'جاكيتات دنيم قصيرة'],
            ['Denim Shackets',          'شاكيت دنيم'],
        ]],

        ['Denim Dresses', 'فساتين الدنيم', [
            ['Shirt Dresses',   'فساتين قميص'],
            ['Maxi Denim Dresses', 'فساتين دنيم طويلة'],
            ['Pinafore Dresses', 'فساتين بحمالات'],
        ]],

        ['Tops', 'البلوزات', [
            ['Denim Shirts',    'قمصان دنيم'],
            ['Denim Tunics',    'تونيكات دنيم'],
            ['Blouses',         'بلوزات'],
            ['Basic Tops',      'تيشيرتات أساسية'],
        ]],

        ['Trousers', 'البناطيل', [
            ['Wide Trousers',   'بناطيل واسعة'],
            ['Tailored Trousers', 'بناطيل كلاسيك'],
            ['Cargo Trousers',  'بناطيل كارجو'],
            ['Linen Trousers',  'بناطيل كتان'],
        ]],

        ['Outerwear', 'الملابس الخارجية', [
            ['Coats',           'معاطف'],
            ['Trench Coats',    'ترنش كوت'],
            ['Cardigans',       'كارديجان'],
            ['Abayas',          'عبايات'],
        ]],

        ['Sets', 'الأطقم', [
            ['Denim Sets',      'أطقم دنيم'],
            ['Two Piece Sets',  'أطقم قطعتين'],
        ]],

        // Not a garment type, but shops file pieces here and a sheet will name
        // it — better to have it than to reject the row.
        ['Accessories', 'الإكسسوارات', [
            ['Belts',           'أحزمة'],
            ['Scarves',         'إيشاربات'],
            ['Bags',            'شنط'],
        ]],
    ];

    public function run(): void
    {
        $position = (int) Category::query()->max('sort_order');

        foreach (self::TREE as [$parentEn, $parentAr, $children]) {
            $parent = $this->upsert($parentEn, $parentAr, null, ++$position);

            foreach ($children as [$childEn, $childAr]) {
                $this->upsert($childEn, $childAr, $parent->id, ++$position);
            }
        }
    }

    /**
     * Create a category, or leave the existing one alone.
     *
     * Matched on slug, which is what the unique index protects. Skipped rather
     * than updated, because the shop may have renamed it or written its own
     * description and a reseed must not undo their work.
     */
    private function upsert(string $english, string $arabic, ?int $parentId, int $position): Category
    {
        $slug = Str::slug($english);

        /*
         * Matched on name as well as slug.
         *
         * ProductImporter looks categories up by name, so two rows sharing one
         * would collide there — and a category seeded earlier may hold the same
         * name under a different slug, which a slug-only check misses.
         */
        $existing = Category::withTrashed()
            ->where('slug', $slug)
            ->orWhereRaw('LOWER(name_en) = ?', [mb_strtolower($english)])
            ->orWhereRaw('LOWER(name_ar) = ?', [mb_strtolower($arabic)])
            ->first();

        if ($existing !== null) {
            // A category the shop deleted and is now reseeding is being brought
            // back deliberately.
            if ($existing->trashed()) {
                $existing->restore();
            }

            return $existing;
        }

        return Category::create([
            'name_en'    => $english,
            'name_ar'    => $arabic,
            'slug'       => $slug,
            'parent_id'  => $parentId,
            'sort_order' => $position,
            'is_active'  => true,
        ]);
    }
}
