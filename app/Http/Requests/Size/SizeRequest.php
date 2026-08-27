<?php

declare(strict_types=1);

namespace App\Http\Requests\Size;

use App\Models\Size;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class SizeRequest extends FormRequest
{
    protected function size(): ?Size
    {
        return $this->route('size');
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
        $id = $this->size()?->id;

        return [
            'name_ar' => ['required', 'string', 'max:40'],
            'name_en' => ['required', 'string', 'max:40'],
            'code'    => [
                'required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/',
                Rule::unique('sizes', 'code')->ignore($id),
            ],
            // Drives every size listing, so XS never sorts after L.
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active'  => ['boolean'],
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
        return __('catalog.attributes');
    }
}
