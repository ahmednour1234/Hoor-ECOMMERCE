<?php

declare(strict_types=1);

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates the checkout form.
 *
 * Note what is absent: no price, no subtotal, no shipping fee, no total. Money
 * is computed by CheckoutService from the database, so there is nothing here
 * for a tampered payload to influence. The form carries an address, a
 * destination and an optional coupon code — that is all.
 */
class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Guests check out without an account, which is the point of cash on
        // delivery in this market.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:3', 'max:160'],

            /*
             * Egyptian mobile numbers: 11 digits beginning 010, 011, 012 or 015.
             * Accepting only the local form keeps couriers from receiving
             * numbers they cannot dial.
             */
            'phone'     => ['required', 'string', 'regex:/^01[0125][0-9]{8}$/'],

            /*
             * Required, including for guests.
             *
             * Cash on delivery leaves the customer with nothing in writing: no
             * card statement, no payment receipt. The confirmation email is
             * the only record she has of what she ordered and what it cost,
             * and the only place her order number is written down — which is
             * half of what the tracking page asks for.
             */
            // Format only, no DNS lookup: the dns rule costs ~87ms on every
            // checkout attempt and fails outright when DNS is unreachable,
            // which is too much to put on the path to an order for a typo it
            // would rarely catch.
            'email'     => ['required', 'email:rfc', 'max:190'],
            'phone_alt' => ['nullable', 'string', 'regex:/^01[0125][0-9]{8}$/', 'different:phone'],

            'governorate_id' => [
                'required', 'integer',
                Rule::exists('governorates', 'id')->where('is_active', true),
            ],

            // Optional: a governorate with no areas is still deliverable.
            'area_id' => [
                'nullable', 'integer',
                Rule::exists('areas', 'id')->where('is_active', true),
            ],

            'address'  => ['required', 'string', 'min:10', 'max:500'],
            'landmark' => ['nullable', 'string', 'max:240'],

            /*
             * The pin, if she dropped one.
             *
             * Bounded to Egypt rather than the whole globe: the shop delivers
             * nowhere else, so a coordinate outside it is either a spoofed
             * payload or a browser reporting nonsense, and storing it would
             * send a courier somewhere absurd.
             */
            'latitude'  => ['nullable', 'numeric', 'between:21.5,32.5', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:24.0,37.5', 'required_with:latitude'],
            'notes'    => ['nullable', 'string', 'max:1000'],

            'coupon_code' => ['nullable', 'string', 'max:64'],

            // Present so the choice is explicit on the order; cash on delivery
            // is the only value HOOR accepts.
            'payment_method' => ['nullable', Rule::in(['cash_on_delivery'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateAreaBelongsToGovernorate($validator);
        });
    }

    /**
     * An area from another governorate is a mismatch, not a fallback.
     *
     * CheckoutService refuses it too; catching it here turns a 422 into a field
     * error the customer can actually fix.
     */
    private function validateAreaBelongsToGovernorate(Validator $validator): void
    {
        $areaId = $this->input('area_id');

        if (blank($areaId)) {
            return;
        }

        $belongs = \App\Models\Area::query()
            ->whereKey($areaId)
            ->where('governorate_id', $this->integer('governorate_id'))
            ->exists();

        if (! $belongs) {
            $validator->errors()->add('area_id', __('checkout.errors.area_mismatch'));
        }
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            // Arabic-Indic digits are common on Egyptian keyboards; normalise
            // them so a valid number is not rejected for being typed ٠١٢.
            'phone'     => $this->normalisePhone($this->input('phone')),
            'email'     => strtolower(trim((string) $this->input('email'))),
            'phone_alt' => $this->normalisePhone($this->input('phone_alt')),
            'area_id'   => $this->input('area_id') ?: null,
        ]);
    }

    private function normalisePhone(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $latin = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $value = str_replace($arabic, $latin, (string) $value);

        // Strip spaces, dashes and the +20 country prefix customers often add.
        $value = preg_replace('/[\s\-()]/', '', $value) ?? '';
        $value = preg_replace('/^(\+?20)/', '0', $value) ?? $value;

        return $value;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('checkout.attributes');
    }
}
