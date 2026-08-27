<?php

declare(strict_types=1);

namespace App\Http\Requests\Governorate;

use App\Models\Governorate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class GovernorateRequest extends FormRequest
{
    protected function governorate(): ?Governorate
    {
        return $this->route('governorate');
    }

    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->governorate()?->id;

        return [
            'name_ar' => ['required', 'string', 'max:120'],
            'name_en' => ['required', 'string', 'max:120'],
            'code'    => [
                'required', 'string', 'max:10', 'regex:/^[A-Za-z]{1,10}$/',
                Rule::unique('governorates', 'code')->ignore($id),
            ],

            // Entered in EGP; the controller converts to piastres.
            'shipping_fee' => ['required', 'numeric', 'min:0', 'max:99999'],

            'delivery_days_min' => ['required', 'integer', 'min:1', 'max:60'],
            'delivery_days_max' => ['required', 'integer', 'min:1', 'max:60', 'gte:delivery_days_min'],

            'is_active'  => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'code'      => strtoupper(trim((string) $this->input('code'))),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('shipping.attributes');
    }
}
