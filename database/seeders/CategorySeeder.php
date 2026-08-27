<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * HOOR's catalog structure: denim-led, with one level of sub-categories under
 * Jeans so shoppers can narrow by fit.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            [
                'slug'    => 'jeans',
                'name_en' => 'Jeans',
                'name_ar' => 'الجينز',
                'description_en' => 'Modest denim cuts designed for everyday comfort.',
                'description_ar' => 'قصّات دنيم محتشمة مصممة لراحة يومية.',
                'children' => [
                    ['slug' => 'wide-leg',      'name_en' => 'Wide Leg',      'name_ar' => 'واسع الساق'],
                    ['slug' => 'straight-leg',  'name_en' => 'Straight Leg',  'name_ar' => 'مستقيم'],
                    ['slug' => 'mom-jeans',     'name_en' => 'Mom Jeans',     'name_ar' => 'موم جينز'],
                    ['slug' => 'flared',        'name_en' => 'Flared',        'name_ar' => 'شارلستون'],
                ],
            ],
            [
                'slug'    => 'denim-skirts',
                'name_en' => 'Denim Skirts',
                'name_ar' => 'جيبات الدنيم',
                'description_en' => 'Full-length denim skirts with a modest silhouette.',
                'description_ar' => 'جيبات دنيم طويلة بقصّة محتشمة.',
                'children' => [],
            ],
            [
                'slug'    => 'jackets',
                'name_en' => 'Denim Jackets',
                'name_ar' => 'جاكيتات الدنيم',
                'description_en' => 'Layering pieces in classic and oversized fits.',
                'description_ar' => 'قطع تُلبس فوق الملابس بقصّات كلاسيكية وواسعة.',
                'children' => [],
            ],
            [
                'slug'    => 'wide-trousers',
                'name_en' => 'Wide Trousers',
                'name_ar' => 'بناطيل واسعة',
                'description_en' => 'Relaxed non-denim trousers that pair with our jackets.',
                'description_ar' => 'بناطيل واسعة غير دنيم تنسّق مع الجاكيتات.',
                'children' => [],
            ],
        ];

        foreach ($tree as $index => $node) {
            $children = $node['children'];
            unset($node['children']);

            $parent = Category::query()->updateOrCreate(
                ['slug' => $node['slug']],
                $node + ['sort_order' => $index, 'is_active' => true],
            );

            foreach ($children as $childIndex => $child) {
                Category::query()->updateOrCreate(
                    ['slug' => $child['slug']],
                    $child + [
                        'parent_id'  => $parent->id,
                        'sort_order' => $childIndex,
                        'is_active'  => true,
                    ],
                );
            }
        }
    }
}
