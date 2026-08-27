<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Applying a discount code to the basket.
 *
 * Shape only. Whether the code exists, is live, or is worth anything is decided
 * by CouponService against the server's own view of the cart — a customer
 * submits a code, never an amount.
 */
class ApplyCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'coupon_code' => ['required', 'string', 'max:64'],
        ];
    }

    public function code(): string
    {
        return (string) $this->input('coupon_code');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['coupon_code' => __('coupons.fields.code')];
    }
}
