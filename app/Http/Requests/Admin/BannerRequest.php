<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating or editing a promotional banner.
 */
class BannerRequest extends FormRequest
{
    /**
     * Where a banner may be placed.
     *
     * A fixed list because each placement has a template rendering it; an
     * arbitrary string would save happily and then show up nowhere.
     *
     * @var list<string>
     */
    public const PLACEMENTS = ['announcement', 'home_promo', 'shop_top'];

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
            'placement' => ['required', Rule::in(self::PLACEMENTS)],

            'image' => [
                'nullable', 'image', 'mimes:jpg,jpeg,png,webp',
                'max:'.config('hoor.media.max_upload', 4096),
            ],

            'title_ar' => ['nullable', 'string', 'max:190'],
            'title_en' => ['nullable', 'string', 'max:190'],
            'body_ar'  => ['nullable', 'string', 'max:255'],
            'body_en'  => ['nullable', 'string', 'max:255'],

            'cta_label_ar' => ['nullable', 'string', 'max:80'],
            'cta_label_en' => ['nullable', 'string', 'max:80'],
            'cta_url'      => ['nullable', 'string', 'max:255'],

            'starts_at' => ['nullable', 'date'],
            // A run that ends before it starts is a slip, and would show the
            // banner never.
            'ends_at'   => ['nullable', 'date', 'after_or_equal:starts_at'],

            'position'  => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function bannerData(): array
    {
        return [
            'placement'    => (string) $this->input('placement'),
            'title_ar'     => $this->input('title_ar') ?: null,
            'title_en'     => $this->input('title_en') ?: null,
            'body_ar'      => $this->input('body_ar') ?: null,
            'body_en'      => $this->input('body_en') ?: null,
            'cta_label_ar' => $this->input('cta_label_ar') ?: null,
            'cta_label_en' => $this->input('cta_label_en') ?: null,
            'cta_url'      => $this->input('cta_url') ?: null,
            'starts_at'    => $this->input('starts_at') ?: null,
            'ends_at'      => $this->input('ends_at') ?: null,
            'position'     => (int) $this->input('position', 0),
            'is_active'    => $this->boolean('is_active'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'placement' => __('content.banners.placement'),
            'starts_at' => __('content.banners.starts_at'),
            'ends_at'   => __('content.banners.ends_at'),
            'cta_url'   => __('content.banners.cta_url'),
        ];
    }
}
