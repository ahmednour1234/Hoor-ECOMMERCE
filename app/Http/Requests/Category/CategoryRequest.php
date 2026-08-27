<?php

declare(strict_types=1);

namespace App\Http\Requests\Category;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class CategoryRequest extends FormRequest
{
    protected function category(): ?Category
    {
        return $this->route('category');
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
        $id = $this->category()?->id;

        return [
            'name_ar' => ['required', 'string', 'max:120'],
            'name_en' => ['required', 'string', 'max:120'],
            'slug'    => [
                'nullable', 'string', 'max:140', 'alpha_dash',
                Rule::unique('categories', 'slug')->ignore($id),
            ],

            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],

            'description_ar' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],

            'image' => [
                'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp',
                'max:'.config('hoor.media.max_upload', 4096),
            ],
            'remove_image' => ['boolean'],

            'is_active'  => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],

            'meta_title_ar'       => ['nullable', 'string', 'max:180'],
            'meta_title_en'       => ['nullable', 'string', 'max:180'],
            'meta_description_ar' => ['nullable', 'string', 'max:320'],
            'meta_description_en' => ['nullable', 'string', 'max:320'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateParentIsNotCircular($validator);
        });
    }

    /**
     * A category may not be its own parent, nor the child of one of its
     * descendants — either would create a cycle the menu builder cannot walk.
     */
    private function validateParentIsNotCircular(Validator $validator): void
    {
        $category = $this->category();
        $parentId = $this->input('parent_id');

        if ($category === null || blank($parentId)) {
            return;
        }

        if ((int) $parentId === $category->id) {
            $validator->errors()->add('parent_id', __('catalog.validation.self_parent'));

            return;
        }

        $descendants = $this->descendantIdsOf($category);

        if (in_array((int) $parentId, $descendants, strict: true)) {
            $validator->errors()->add('parent_id', __('catalog.validation.circular_parent'));
        }
    }

    /**
     * @return list<int>
     */
    private function descendantIdsOf(Category $category): array
    {
        $ids = [];
        $frontier = [$category->id];

        // Iterative walk: the depth is unbounded in principle, and recursion
        // would risk a stack overflow on a corrupted tree.
        while ($frontier !== []) {
            $children = Category::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();

            $children = array_values(array_diff($children, $ids));

            if ($children === []) {
                break;
            }

            $ids = array_merge($ids, $children);
            $frontier = $children;
        }

        return $ids;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'    => $this->boolean('is_active'),
            'remove_image' => $this->boolean('remove_image'),
            'parent_id'    => $this->input('parent_id') ?: null,
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
