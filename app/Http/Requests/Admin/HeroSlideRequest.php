<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Creating or editing a hero slide.
 */
class HeroSlideRequest extends FormRequest
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
        return [
            // Required only when creating: editing copy should not force a
            // re-upload of an image that is already there.
            'image' => [
                $this->routeIsCreate() ? 'required' : 'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.config('hoor.media.max_upload', 4096),
            ],

            'backdrop' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            'eyebrow_ar'     => ['nullable', 'string', 'max:120'],
            'eyebrow_en'     => ['nullable', 'string', 'max:120'],
            'headline_ar'    => ['nullable', 'string', 'max:190'],
            'headline_en'    => ['nullable', 'string', 'max:190'],
            'subheadline_ar' => ['nullable', 'string', 'max:255'],
            'subheadline_en' => ['nullable', 'string', 'max:255'],

            'cta_label_ar' => ['nullable', 'string', 'max:80'],
            'cta_label_en' => ['nullable', 'string', 'max:80'],
            'cta_url'      => ['nullable', 'string', 'max:255'],

            'position'  => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function routeIsCreate(): bool
    {
        return $this->route('slide') === null;
    }

    /**
     * The values to persist, without the file.
     *
     * @return array<string, mixed>
     */
    public function slideData(): array
    {
        return [
            'backdrop'       => $this->input('backdrop') ?: null,
            'eyebrow_ar'     => $this->input('eyebrow_ar') ?: null,
            'eyebrow_en'     => $this->input('eyebrow_en') ?: null,
            'headline_ar'    => $this->input('headline_ar') ?: null,
            'headline_en'    => $this->input('headline_en') ?: null,
            'subheadline_ar' => $this->input('subheadline_ar') ?: null,
            'subheadline_en' => $this->input('subheadline_en') ?: null,
            'cta_label_ar'   => $this->input('cta_label_ar') ?: null,
            'cta_label_en'   => $this->input('cta_label_en') ?: null,
            'cta_url'        => $this->input('cta_url') ?: null,
            'position'       => (int) $this->input('position', 0),
            'is_active'      => $this->boolean('is_active'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'image'       => __('content.slides.image'),
            'backdrop'    => __('content.slides.backdrop'),
            'headline_ar' => __('content.slides.headline'),
            'headline_en' => __('content.slides.headline'),
            'cta_url'     => __('content.slides.cta_url'),
        ];
    }
}
