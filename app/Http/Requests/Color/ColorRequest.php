<?php

declare(strict_types=1);

namespace App\Http\Requests\Color;

use App\Models\Color;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ColorRequest extends FormRequest
{
    protected function color(): ?Color
    {
        return $this->route('color');
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
        $id = $this->color()?->id;

        return [
            'name_ar' => ['required', 'string', 'max:60'],
            'name_en' => ['required', 'string', 'max:60'],
            'slug'    => [
                'nullable', 'string', 'max:80', 'alpha_dash',
                Rule::unique('colors', 'slug')->ignore($id),
            ],
            // Six-digit hex, with or without the leading hash; the model
            // normalises it to uppercase with a hash on save.
            'hex'        => ['required', 'string', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'  => ['boolean'],
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
        return __('catalog.attributes');
    }
}
