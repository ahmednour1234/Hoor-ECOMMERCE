<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Shared rules for creating and updating a product.
 *
 * The form submits every section at once — details, pricing, gallery, variants
 * and SEO — so all of it is validated here as a single payload.
 */
abstract class ProductRequest extends FormRequest
{
    /**
     * The product being edited, or null when creating.
     */
    protected function product(): ?Product
    {
        return $this->route('product');
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
        $productId = $this->product()?->id;

        return [
            // ------------------------------------------------------ General
            'name_ar'     => ['required', 'string', 'max:180'],
            'name_en'     => ['required', 'string', 'max:180'],
            'slug'        => [
                'nullable', 'string', 'max:200', 'alpha_dash',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'category_id' => [
                'required', 'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],

            'short_description_ar' => ['nullable', 'string', 'max:320'],
            'short_description_en' => ['nullable', 'string', 'max:320'],
            'description_ar'       => ['nullable', 'string', 'max:5000'],
            'description_en'       => ['nullable', 'string', 'max:5000'],

            'fabric_ar' => ['nullable', 'string', 'max:120'],
            'fabric_en' => ['nullable', 'string', 'max:120'],
            'care_ar'   => ['nullable', 'string', 'max:240'],
            'care_en'   => ['nullable', 'string', 'max:240'],

            'status'      => ['required', Rule::enum(ProductStatus::class)],
            'is_featured' => ['boolean'],
            'is_new'      => ['boolean'],

            // ------------------------------------------------------ Pricing
            // Entered in EGP; the service converts to piastres for storage.
            'base_price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'max:9999999', 'lt:base_price'],

            // ------------------------------------------------------- Images
            'images'   => ['array', 'max:12'],
            'images.*' => [
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.config('hoor.media.max_upload', 4096),
            ],

            'image_meta'              => ['array'],
            'image_meta.*.alt_ar'     => ['nullable', 'string', 'max:180'],
            'image_meta.*.alt_en'     => ['nullable', 'string', 'max:180'],
            'image_meta.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],

            'removed_images'   => ['array'],
            'removed_images.*' => [
                'integer',
                Rule::exists('product_images', 'id')->where('product_id', $productId),
            ],

            // Either the id of an existing image, or a "new:{index}" token
            // pointing at one of the uploads in this same request. A plain
            // `string` rule would reject the numeric id form.
            'primary_image' => ['nullable', 'regex:/^(\d+|new:\d+)$/'],

            // ----------------------------------------------------- Variants
            'variants'      => ['array', 'max:200'],
            'variants.*.id' => [
                'nullable', 'integer',
                Rule::exists('product_variants', 'id')->where('product_id', $productId),
            ],
            'variants.*.color_id'            => ['nullable', 'integer', Rule::exists('colors', 'id')],
            'variants.*.size_id'             => ['nullable', 'integer', Rule::exists('sizes', 'id')],
            'variants.*.sku'                 => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/'],
            'variants.*.stock_quantity'      => ['required', 'integer', 'min:0', 'max:999999'],
            'variants.*.low_stock_threshold' => ['required', 'integer', 'min:0', 'max:9999'],
            'variants.*.price'               => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'variants.*.sale_price'          => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'variants.*.is_active'           => ['boolean'],

            // ---------------------------------------------------------- SEO
            'meta_title_ar'       => ['nullable', 'string', 'max:180'],
            'meta_title_en'       => ['nullable', 'string', 'max:180'],
            'meta_description_ar' => ['nullable', 'string', 'max:320'],
            'meta_description_en' => ['nullable', 'string', 'max:320'],
        ];
    }

    /**
     * Cross-field checks the per-field rules cannot express.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateSkusAreUnique($validator);
            $this->validateVariantCombinationsAreUnique($validator);
            $this->validateVariantSalePrices($validator);
        });
    }

    /**
     * SKUs must be unique across the catalog, and unique within this
     * submission — the latter is a case the database constraint would only
     * ever surface as a 500.
     */
    private function validateSkusAreUnique(Validator $validator): void
    {
        $seen = [];

        foreach ($this->input('variants', []) as $index => $variant) {
            $sku = strtoupper(trim((string) ($variant['sku'] ?? '')));

            if ($sku === '') {
                continue;
            }

            if (isset($seen[$sku])) {
                $validator->errors()->add(
                    "variants.{$index}.sku",
                    __('catalog.validation.duplicate_sku_in_form', ['row' => $seen[$sku] + 1]),
                );

                continue;
            }

            $seen[$sku] = $index;

            $taken = ProductVariant::query()
                ->whereRaw('UPPER(sku) = ?', [$sku])
                ->when(
                    filled($variant['id'] ?? null),
                    fn ($query) => $query->whereKeyNot($variant['id']),
                )
                ->exists();

            if ($taken) {
                $validator->errors()->add(
                    "variants.{$index}.sku",
                    __('catalog.validation.sku_taken', ['sku' => $sku]),
                );
            }
        }
    }

    /**
     * A product must not carry two variants for the same colour/size pair.
     */
    private function validateVariantCombinationsAreUnique(Validator $validator): void
    {
        $seen = [];

        foreach ($this->input('variants', []) as $index => $variant) {
            $key = ($variant['color_id'] ?? 'null').':'.($variant['size_id'] ?? 'null');

            if (isset($seen[$key])) {
                $validator->errors()->add(
                    "variants.{$index}.size_id",
                    __('catalog.validation.duplicate_combination', ['row' => $seen[$key] + 1]),
                );

                continue;
            }

            $seen[$key] = $index;
        }
    }

    /**
     * A variant sale price is only meaningful below the price it discounts.
     */
    private function validateVariantSalePrices(Validator $validator): void
    {
        foreach ($this->input('variants', []) as $index => $variant) {
            $sale = $variant['sale_price'] ?? null;

            if (blank($sale)) {
                continue;
            }

            $base = filled($variant['price'] ?? null)
                ? $variant['price']
                : $this->input('base_price');

            if ((float) $sale >= (float) $base) {
                $validator->errors()->add(
                    "variants.{$index}.sale_price",
                    __('catalog.validation.sale_price_too_high'),
                );
            }
        }
    }

    /**
     * Normalise checkbox and repeater input before the rules run.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
            'is_new'      => $this->boolean('is_new'),
        ]);

        if (is_array($variants = $this->input('variants'))) {
            $this->merge([
                'variants' => array_values(array_map(
                    static function (array $variant): array {
                        $variant['is_active'] = filter_var(
                            $variant['is_active'] ?? false,
                            FILTER_VALIDATE_BOOLEAN,
                        );

                        return $variant;
                    },
                    $variants,
                )),
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('catalog.attributes');
    }
}
