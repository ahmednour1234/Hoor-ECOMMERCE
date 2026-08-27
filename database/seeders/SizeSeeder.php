<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Size;
use Illuminate\Database\Seeder;

/**
 * The garment size run HOOR stocks.
 *
 * sort_order is what every listing sorts by, so XS never lands after L.
 */
class SizeSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = [
            ['code' => 'XS',  'name_en' => 'XS',  'name_ar' => 'XS'],
            ['code' => 'S',   'name_en' => 'S',   'name_ar' => 'S'],
            ['code' => 'M',   'name_en' => 'M',   'name_ar' => 'M'],
            ['code' => 'L',   'name_en' => 'L',   'name_ar' => 'L'],
            ['code' => 'XL',  'name_en' => 'XL',  'name_ar' => 'XL'],
            ['code' => 'XXL', 'name_en' => 'XXL', 'name_ar' => 'XXL'],
        ];

        foreach ($sizes as $index => $size) {
            Size::query()->updateOrCreate(
                ['code' => $size['code']],
                $size + ['sort_order' => $index, 'is_active' => true],
            );
        }
    }
}
