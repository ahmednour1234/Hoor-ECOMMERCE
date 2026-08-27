<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Casts\Money;
use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Creating or editing a coupon.
 *
 * Amounts are typed in EGP and stored in piastres, like every other price in
 * the admin — the conversion happens here so no controller or service has to
 * remember which unit it is holding.
 */
class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $coupon = $this->route('coupon');

        return [
            'code' => [
                'required', 'string', 'max:64',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('coupons', 'code')->ignore($coupon?->id),
            ],

            'name_ar' => ['nullable', 'string', 'max:120'],
            'name_en' => ['nullable', 'string', 'max:120'],

            'type' => ['required', Rule::enum(CouponType::class)],

            /*
             * The value's meaning depends on the type, so its bounds do too: a
             * percentage above 100 would give away more than the goods are
             * worth, which is a different mistake from a large fixed amount.
             */
            'value' => $this->isPercentage()
                ? ['required', 'integer', 'min:1', 'max:100']
                : ['required', 'numeric', 'min:0.01', 'max:1000000'],

            'max_discount' => ['nullable', 'numeric', 'min:0.01', 'max:1000000'],
            'min_order'    => ['nullable', 'numeric', 'min:0', 'max:1000000'],

            'starts_at'  => ['nullable', 'date'],
            // A window that closes before it opens would never apply.
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],

            'usage_limit'        => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],

            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // A cap on a fixed coupon is meaningless — the amount is already
            // its own ceiling — and silently ignoring it would leave the admin
            // believing a limit is in force.
            if (! $this->isPercentage() && filled($this->input('max_discount'))) {
                $validator->errors()->add('max_discount', __('coupons.errors.max_on_fixed'));
            }

            /*
             * A fixed coupon worth more than its own minimum spend is almost
             * always a typo — "100 EGP off orders over 50 EGP" gives the goods
             * away. Flagged rather than blocked would be worse: the discount is
             * clamped to the subtotal anyway, so the shop would lose the sale
             * quietly instead of loudly.
             */
            if (! $this->isPercentage()) {
                $value = $this->piastres('value');
                $minimum = $this->piastres('min_order');

                if ($minimum !== null && $value !== null && $value > $minimum) {
                    $validator->errors()->add('value', __('coupons.errors.value_over_minimum'));
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => Coupon::normaliseCode((string) $this->input('code'))]);
    }

    private function isPercentage(): bool
    {
        return $this->input('type') === CouponType::Percentage->value;
    }

    /**
     * An EGP amount from the form, in piastres.
     */
    private function piastres(string $field): ?int
    {
        $value = $this->input($field);

        return blank($value) ? null : Money::fromMajor($value);
    }

    /**
     * The values to persist.
     *
     * @return array<string, mixed>
     */
    public function couponData(): array
    {
        $isPercentage = $this->isPercentage();

        return [
            'code'    => (string) $this->input('code'),
            'name_ar' => $this->input('name_ar') ?: null,
            'name_en' => $this->input('name_en') ?: null,
            'type'    => $this->input('type'),

            // A percentage is whole percent; a fixed amount is piastres.
            'value' => $isPercentage
                ? (int) $this->input('value')
                : $this->piastres('value'),

            // Dropped rather than stored for a fixed coupon, so a type changed
            // later does not resurrect a stale ceiling.
            'max_discount' => $isPercentage ? $this->piastres('max_discount') : null,
            'min_order'    => $this->piastres('min_order'),

            'starts_at'  => $this->input('starts_at') ?: null,
            'expires_at' => $this->input('expires_at') ?: null,

            'usage_limit'        => $this->input('usage_limit') ?: null,
            'per_customer_limit' => $this->input('per_customer_limit') ?: null,

            'is_active' => $this->boolean('is_active'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code'               => __('coupons.fields.code'),
            'type'               => __('coupons.fields.type'),
            'value'              => __('coupons.fields.value'),
            'max_discount'       => __('coupons.fields.max_discount'),
            'min_order'          => __('coupons.fields.min_order'),
            'starts_at'          => __('coupons.fields.starts_at'),
            'expires_at'         => __('coupons.fields.expires_at'),
            'usage_limit'        => __('coupons.fields.usage_limit'),
            'per_customer_limit' => __('coupons.fields.per_customer_limit'),
        ];
    }
}
