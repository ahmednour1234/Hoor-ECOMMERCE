<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Seeder;

/**
 * The welcome discount offered to customers who sign in at checkout.
 *
 * A real coupon rather than a special case in the checkout code: it goes
 * through the same CouponService every other discount does, so it is validated
 * server-side, recorded as a redemption, released if the order is cancelled,
 * and visible in the admin like any other campaign.
 *
 * `per_customer_limit = 1` is what makes it a welcome offer rather than a
 * standing discount — and because redemptions are keyed by phone as well as
 * account, creating a second Google account does not earn a second one.
 */
class WelcomeCouponSeeder extends Seeder
{
    /**
     * The code the checkout banner applies.
     *
     * Named in config rather than hardcoded here, so the shop can point the
     * banner at a different campaign without a deployment.
     */
    public const CODE = 'WELCOME5';

    public function run(): void
    {
        // Never overwrite a campaign the business has since edited.
        if (Coupon::query()->code(self::CODE)->exists()) {
            return;
        }

        Coupon::create([
            'code'    => self::CODE,
            'name_en' => 'Welcome discount',
            'name_ar' => 'خصم الترحيب',

            'type'  => CouponType::Percentage,
            'value' => 5,

            /*
             * A ceiling, because a percentage with none gives away more than
             * the campaign intends on a large basket. 150 EGP is roughly a
             * 3,000 EGP order, which is well beyond a typical first purchase.
             */
            'max_discount' => 15000,

            // No minimum: the point is to make a first order easy, not to push
            // its size.
            'min_order' => null,

            // Once per customer, ever. This is what makes it a welcome offer.
            'per_customer_limit' => 1,

            // No overall cap: the campaign runs until it is switched off.
            'usage_limit' => null,

            'is_active' => true,
        ]);
    }
}
