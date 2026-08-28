<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Order matters: products reference categories, sizes and colours.
        $this->call([
            AdminUserSeeder::class,
            ShippingSeeder::class,
            SizeSeeder::class,
            ColorSeeder::class,

            // The wider palette, so an import naming "Charcoal" or "Mocha"
            // finds it rather than being refused.
            ColorLibrarySeeder::class,

            CategorySeeder::class,

            // The wider tree, so a spreadsheet naming "Denim Shirts" or
            // "Abayas" finds it rather than being refused.
            CategoryLibrarySeeder::class,
            ProductSeeder::class,
            SettingsSeeder::class,
            FaqSeeder::class,
            WelcomeCouponSeeder::class,
        ]);
    }
}
