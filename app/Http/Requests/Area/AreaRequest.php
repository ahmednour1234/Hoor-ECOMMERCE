<?php

declare(strict_types=1);

namespace App\Http\Requests\Area;

use App\Models\Area;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class AreaRequest extends FormRequest
{
    protected function area(): ?Area
    {
        return $this->route('area');
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
        $governorateId = $this->route('governorate')?->id;

        return [
            'name_ar' => ['required', 'string', 'max:140'],
            'name_en' => [
                'required', 'string', 'max:140',
                // Unique within the governorate only: the same district name
                // may legitimately recur elsewhere in Egypt.
                Rule::unique('areas', 'name_en')
                    ->where('governorate_id', $governorateId)
                    ->ignore($this->area()?->id),
            ],

            // Blank means "inherit the governorate fee", which is not the same
            // as free delivery.
            'shipping_fee' => ['nullable', 'numeric', 'min:0', 'max:99999'],

            'is_active'  => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('shipping.attributes');
    }
}
