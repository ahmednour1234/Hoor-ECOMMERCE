<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Seeder;

/**
 * The denim washes and neutrals HOOR sells in.
 *
 * Hex values are real denim washes so the colour selector reads correctly
 * without photography.
 */
class ColorSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['slug' => 'indigo',       'name_en' => 'Indigo',       'name_ar' => 'نيلي',        'hex' => '#2B4166'],
            ['slug' => 'dark-wash',    'name_en' => 'Dark Wash',    'name_ar' => 'أزرق غامق',   'hex' => '#1E3048'],
            ['slug' => 'mid-blue',     'name_en' => 'Mid Blue',     'name_ar' => 'أزرق متوسط',  'hex' => '#4A73A0'],
            ['slug' => 'light-wash',   'name_en' => 'Light Wash',   'name_ar' => 'أزرق فاتح',   'hex' => '#9BB6D2'],
            ['slug' => 'black-denim',  'name_en' => 'Black Denim',  'name_ar' => 'أسود',        'hex' => '#1C1C1E'],
            ['slug' => 'ecru',         'name_en' => 'Ecru',         'name_ar' => 'بيج فاتح',    'hex' => '#EFE7DA'],
            ['slug' => 'sand',         'name_en' => 'Sand',         'name_ar' => 'رملي',        'hex' => '#D8C6B3'],
            ['slug' => 'olive',        'name_en' => 'Olive',        'name_ar' => 'زيتي',        'hex' => '#5C6350'],
        ];

        foreach ($colors as $index => $color) {
            Color::query()->updateOrCreate(
                ['slug' => $color['slug']],
                $color + ['sort_order' => $index, 'is_active' => true],
            );
        }
    }
}
