<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Casts\Money;
use App\Models\Area;
use App\Models\Governorate;
use Illuminate\Database\Seeder;

/**
 * Seeds Egypt's 27 governorates, and areas for the three where district names
 * are well established.
 *
 * Idempotent: keyed on the governorate code and on the area's name within its
 * governorate, so re-running updates rather than duplicating.
 *
 * Fees are starting points banded by delivery difficulty. The admin owns them
 * and is expected to set real courier pricing — nothing in the application
 * hardcodes a shipping figure.
 */
class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedGovernorates();
        $this->seedAreas();
    }

    private function seedGovernorates(): void
    {
        foreach (require __DIR__.'/data/egypt-governorates.php' as $index => $row) {
            Governorate::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name_en'           => $row['name_en'],
                    'name_ar'           => $row['name_ar'],
                    'shipping_fee'      => Money::fromMajor($row['fee']),
                    'delivery_days_min' => $row['days'][0],
                    'delivery_days_max' => $row['days'][1],
                    'is_active'         => true,
                    'sort_order'        => $index,
                ],
            );
        }
    }

    private function seedAreas(): void
    {
        $governorates = Governorate::query()->get()->keyBy('code');

        foreach (require __DIR__.'/data/egypt-areas.php' as $code => $areas) {
            $governorate = $governorates->get($code);

            if ($governorate === null) {
                continue;
            }

            foreach ($areas as $index => $area) {
                Area::query()->updateOrCreate(
                    [
                        'governorate_id' => $governorate->id,
                        'name_en'        => $area['name_en'],
                    ],
                    [
                        'name_ar' => $area['name_ar'],
                        // Null: inherits the governorate fee unless the admin
                        // sets an override.
                        'shipping_fee' => $area['fee'] ?? null,
                        'is_active'    => true,
                        'sort_order'   => $index,
                    ],
                );
            }
        }
    }
}
