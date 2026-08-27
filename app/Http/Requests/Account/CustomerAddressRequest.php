<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Models\Area;
use App\Models\Governorate;
use App\Support\EgyptianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Saving an address to the customer's book.
 *
 * Mirrors the checkout address rules, including the one that only arithmetic
 * can catch: an area must belong to the governorate chosen beside it, or the
 * shipping fee quoted will not be the fee that applies.
 */
class CustomerAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label'     => ['nullable', 'string', 'max:60'],
            'full_name' => ['required', 'string', 'max:160'],

            'phone'     => ['required', 'string', EgyptianPhone::RULE],
            'phone_alt' => ['nullable', 'string', EgyptianPhone::RULE, 'different:phone'],

            'governorate_id' => [
                'required',
                Rule::exists('governorates', 'id')->where('is_active', true),
            ],

            'area_id' => ['nullable', Rule::exists('areas', 'id')->where('is_active', true)],

            'address'  => ['required', 'string', 'max:500'],
            'landmark' => ['nullable', 'string', 'max:190'],

            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $areaId = $this->input('area_id');

            if (blank($areaId)) {
                return;
            }

            // An area belonging to a different governorate would price the
            // delivery wrongly, so the pair is checked rather than each field.
            $belongs = Area::query()
                ->whereKey($areaId)
                ->where('governorate_id', (int) $this->input('governorate_id'))
                ->exists();

            if (! $belongs) {
                $validator->errors()->add('area_id', __('checkout.errors.area_mismatch'));
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone'     => EgyptianPhone::normalise($this->input('phone')),
            'phone_alt' => EgyptianPhone::normalise($this->input('phone_alt')),
            'area_id'   => $this->input('area_id') ?: null,
        ]);
    }

    /**
     * The values to persist, cast at the boundary.
     *
     * HTML posts strings; letting '1' reach an int-typed service is exactly
     * what broke checkout in an earlier phase.
     *
     * @return array<string, mixed>
     */
    public function addressData(): array
    {
        return [
            'label'          => $this->input('label') ?: null,
            'full_name'      => (string) $this->input('full_name'),
            'phone'          => (string) $this->input('phone'),
            'phone_alt'      => $this->input('phone_alt') ?: null,
            'governorate_id' => (int) $this->input('governorate_id'),
            'area_id'        => $this->input('area_id') ? (int) $this->input('area_id') : null,
            'address'        => (string) $this->input('address'),
            'landmark'       => $this->input('landmark') ?: null,
            'is_default'     => $this->boolean('is_default'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'label'          => __('account.addresses.label'),
            'full_name'      => __('checkout.fields.full_name'),
            'phone'          => __('checkout.fields.phone'),
            'phone_alt'      => __('checkout.fields.phone_alt'),
            'governorate_id' => __('checkout.fields.governorate'),
            'area_id'        => __('checkout.fields.area'),
            'address'        => __('checkout.fields.address'),
            'landmark'       => __('checkout.fields.landmark'),
        ];
    }
}
