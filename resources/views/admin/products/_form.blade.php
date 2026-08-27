{{--
    Product form.

    One <form> containing five tabbed sections. The tabs are presentation only —
    every panel stays in the DOM, so a single save writes details, pricing,
    gallery and variants together, and a validation failure never discards work
    from a section the admin cannot currently see.
--}}
@props(['product', 'categories', 'colors', 'sizes', 'statuses'])

@php
    $isEdit = $product->exists;
    $maxUploadMb = round(config('hoor.media.max_upload', 4096) / 1024, 1);

    // Repopulate the variant rows from old input after a failed validation,
    // otherwise from the stored rows.
    $variantRows = old('variants', $product->variants
        ->map(fn ($variant) => [
            'id'                  => $variant->id,
            'color_id'            => $variant->color_id,
            'size_id'             => $variant->size_id,
            'sku'                 => $variant->sku,
            'stock_quantity'      => $variant->stock_quantity,
            'low_stock_threshold' => $variant->low_stock_threshold,
            'price'               => $variant->price !== null ? \App\Casts\Money::toMajor($variant->price) : '',
            'sale_price'          => $variant->sale_price !== null ? \App\Casts\Money::toMajor($variant->sale_price) : '',
            'is_active'           => $variant->is_active,
        ])
        ->values()
        ->all());

    $colorOptions = $colors->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'hex' => $c->hex])->values();
    $sizeOptions  = $sizes->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'code' => $s->code])->values();
@endphp

<form method="POST"
      action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}"
      enctype="multipart/form-data"
      x-data="productForm({
          variants: @js($variantRows),
          colors: @js($colorOptions),
          sizes: @js($sizeOptions),
          namePrefix: @js($product->name_en ?: 'HOOR'),
      })">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    @if ($errors->any())
        <x-ui.alert variant="danger" class="mb-6">
            {{ __('catalog.messages.has_errors') }}
        </x-ui.alert>
    @endif

    <div class="card">
        <div class="px-5 pt-2 sm:px-6">
            <x-admin.tabs :tabs="[
                ['key' => 'general',  'label' => __('catalog.tabs.general'),  'errors' => ['name_ar', 'name_en', 'slug', 'category_id', 'status', 'description_ar', 'description_en', 'short_description_ar', 'short_description_en', 'fabric_ar', 'fabric_en', 'care_ar', 'care_en']],
                ['key' => 'pricing',  'label' => __('catalog.tabs.pricing'),  'errors' => ['base_price', 'sale_price']],
                ['key' => 'images',   'label' => __('catalog.tabs.images'),   'errors' => ['images', 'image_meta', 'removed_images', 'primary_image']],
                ['key' => 'variants', 'label' => __('catalog.tabs.variants'), 'errors' => ['variants']],
                ['key' => 'seo',      'label' => __('catalog.tabs.seo'),      'errors' => ['meta_title_ar', 'meta_title_en', 'meta_description_ar', 'meta_description_en']],
            ]">

                {{-- ======================================================= GENERAL --}}
                <x-slot:general>
                    <div class="grid gap-5 pb-6 lg:grid-cols-2">
                        <x-ui.input name="name_en" :label="__('catalog.fields.name_en')"
                                    :value="$product->name_en" required dir="ltr" />

                        <x-ui.input name="name_ar" :label="__('catalog.fields.name_ar')"
                                    :value="$product->name_ar" required dir="rtl" />

                        <x-ui.input name="slug" :label="__('catalog.fields.slug')"
                                    :value="$product->slug" :hint="__('catalog.fields.slug_hint')" dir="ltr" />

                        <x-ui.select name="category_id" :label="__('catalog.fields.category')"
                                     :options="$categories" :selected="$product->category_id" required />

                        <x-ui.input name="short_description_en" :label="__('catalog.fields.short_desc_en')"
                                    :value="$product->short_description_en" dir="ltr" />

                        <x-ui.input name="short_description_ar" :label="__('catalog.fields.short_desc_ar')"
                                    :value="$product->short_description_ar" dir="rtl" />

                        <x-ui.textarea name="description_en" :label="__('catalog.fields.description_en')"
                                       :value="$product->description_en" dir="ltr" />

                        <x-ui.textarea name="description_ar" :label="__('catalog.fields.description_ar')"
                                       :value="$product->description_ar" dir="rtl" />

                        <x-ui.input name="fabric_en" :label="__('catalog.fields.fabric_en')"
                                    :value="$product->fabric_en" dir="ltr" />

                        <x-ui.input name="fabric_ar" :label="__('catalog.fields.fabric_ar')"
                                    :value="$product->fabric_ar" dir="rtl" />

                        <x-ui.input name="care_en" :label="__('catalog.fields.care_en')"
                                    :value="$product->care_en" dir="ltr" />

                        <x-ui.input name="care_ar" :label="__('catalog.fields.care_ar')"
                                    :value="$product->care_ar" dir="rtl" />

                        <x-ui.select name="status" :label="__('catalog.fields.status')"
                                     :options="$statuses"
                                     :selected="$product->status?->value ?? \App\Enums\ProductStatus::Draft->value"
                                     required />

                        <div class="flex flex-col justify-center gap-3">
                            <x-ui.checkbox name="is_featured" :label="__('catalog.fields.is_featured')"
                                           :hint="__('catalog.fields.is_featured_hint')"
                                           :checked="$product->is_featured" />

                            <x-ui.checkbox name="is_new" :label="__('catalog.fields.is_new')"
                                           :hint="__('catalog.fields.is_new_hint')"
                                           :checked="$product->is_new" />
                        </div>
                    </div>
                </x-slot:general>

                {{-- ======================================================= PRICING --}}
                <x-slot:pricing>
                    <div class="grid max-w-2xl gap-5 pb-6 sm:grid-cols-2">
                        <x-ui.input name="base_price" type="number" step="0.01" min="0"
                                    :label="__('catalog.fields.base_price').' ('.__('common.currency').')'"
                                    :value="$product->base_price !== null ? \App\Casts\Money::toMajor($product->base_price) : ''"
                                    required dir="ltr" />

                        <x-ui.input name="sale_price" type="number" step="0.01" min="0"
                                    :label="__('catalog.fields.sale_price').' ('.__('common.currency').')'"
                                    :value="$product->sale_price !== null ? \App\Casts\Money::toMajor($product->sale_price) : ''"
                                    :hint="__('catalog.fields.sale_price_hint')" dir="ltr" />
                    </div>

                    <p class="pb-6 text-sm text-hoor-muted">
                        {{ __('catalog.variants.subtitle') }}
                    </p>
                </x-slot:pricing>

                {{-- ======================================================== IMAGES --}}
                <x-slot:images>
                    <div class="pb-6">
                        {{-- Existing gallery --}}
                        @if ($product->images->isNotEmpty())
                            <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($product->images as $image)
                                    <div x-data="{ removed: false }"
                                         :class="removed && 'opacity-40'"
                                         class="rounded-md border border-hoor-cream-300 p-3">

                                        <div class="relative mb-3 aspect-square overflow-hidden rounded-sm bg-hoor-cream-100">
                                            <img src="{{ $image->url() }}"
                                                 alt="{{ $image->alt ?? '' }}"
                                                 class="h-full w-full object-cover">

                                            @if ($image->is_primary)
                                                <span class="absolute start-2 top-2">
                                                    <x-ui.badge variant="gold">{{ __('catalog.images.primary') }}</x-ui.badge>
                                                </span>
                                            @endif
                                        </div>

                                        <label class="mb-2 flex items-center gap-2 text-sm">
                                            <input type="radio" name="primary_image" value="{{ $image->id }}"
                                                   @checked(old('primary_image', $image->is_primary ? $image->id : null) == $image->id)
                                                   class="text-hoor-navy-500 focus:ring-hoor-denim-500">
                                            <span class="text-hoor-navy-700">{{ __('catalog.images.set_primary') }}</span>
                                        </label>

                                        <input type="number" min="0"
                                               name="image_meta[{{ $image->id }}][sort_order]"
                                               value="{{ old("image_meta.{$image->id}.sort_order", $image->sort_order) }}"
                                               class="form-input mb-2 text-sm"
                                               placeholder="{{ __('catalog.fields.sort_order') }}" dir="ltr">

                                        <input type="text"
                                               name="image_meta[{{ $image->id }}][alt_en]"
                                               value="{{ old("image_meta.{$image->id}.alt_en", $image->alt_en) }}"
                                               class="form-input mb-2 text-sm"
                                               placeholder="{{ __('catalog.fields.alt_en') }}" dir="ltr">

                                        <input type="text"
                                               name="image_meta[{{ $image->id }}][alt_ar]"
                                               value="{{ old("image_meta.{$image->id}.alt_ar", $image->alt_ar) }}"
                                               class="form-input text-sm"
                                               placeholder="{{ __('catalog.fields.alt_ar') }}" dir="rtl">

                                        {{-- Removal is deferred to save, so it can be undone before committing. --}}
                                        <label class="mt-3 flex items-center gap-2 text-sm text-red-600">
                                            <input type="checkbox" name="removed_images[]" value="{{ $image->id }}"
                                                   x-model="removed"
                                                   class="rounded border-hoor-cream-300 text-red-600 focus:ring-red-500">
                                            {{ __('catalog.images.remove') }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mb-6 text-sm text-hoor-muted">{{ __('catalog.images.none') }}</p>
                        @endif

                        {{-- New uploads --}}
                        <div class="rounded-md border-2 border-dashed border-hoor-cream-300 p-6">
                            <label class="form-label" for="images">{{ __('catalog.images.upload') }}</label>

                            <input type="file" id="images" name="images[]" multiple
                                   accept="image/jpeg,image/png,image/webp"
                                   x-on:change="pendingImages = Array.from($event.target.files).map(f => f.name)"
                                   class="form-input">

                            <p class="form-hint">
                                {{ __('catalog.images.upload_hint', ['size' => $maxUploadMb]) }}
                            </p>

                            <template x-if="pendingImages.length">
                                <ul class="mt-3 space-y-1 text-sm text-hoor-muted">
                                    <template x-for="(name, i) in pendingImages" :key="i">
                                        <li class="flex items-center gap-2">
                                            <span x-text="name" dir="ltr"></span>
                                            <span class="text-xs">({{ __('catalog.images.pending') }})</span>
                                        </li>
                                    </template>
                                </ul>
                            </template>

                            @error('images.*')
                                <p class="form-error">{{ $message }}</p>
                            @enderror

                            <p class="mt-4 text-xs text-hoor-muted">{{ __('catalog.images.stored_note') }}</p>
                        </div>
                    </div>
                </x-slot:images>

                {{-- ====================================================== VARIANTS --}}
                <x-slot:variants>
                    <div class="pb-6">
                        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <h3 class="font-display text-lg text-hoor-navy-700">{{ __('catalog.variants.title') }}</h3>
                                <p class="mt-1 text-sm text-hoor-muted">{{ __('catalog.variants.subtitle') }}</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-sm text-hoor-muted"
                                      x-text="@js(__('catalog.variants.total_stock', ['count' => ':n'])).replace(':n', totalStock())"></span>

                                <x-ui.button type="button" variant="outline" size="sm" x-on:click="addVariant()">
                                    {{ __('catalog.variants.add') }}
                                </x-ui.button>
                            </div>
                        </div>

                        <template x-if="!variants.length">
                            <p class="rounded-sm bg-hoor-cream-100 px-4 py-6 text-center text-sm text-hoor-muted">
                                {{ __('catalog.variants.none') }}
                            </p>
                        </template>

                        <p class="mb-2 text-xs text-hoor-muted">{{ __('catalog.variants.sku_hint') }}</p>

                        <div class="space-y-2">
                            <template x-for="(variant, index) in variants" :key="variant._key">
                                <div class="rounded-sm border border-hoor-cream-300 px-3 py-2.5"
                                     :class="!variant.is_active && 'bg-hoor-cream-100/60'">

                                    {{-- Persisted rows carry their id so the sync updates rather than recreates. --}}
                                    <template x-if="variant.id">
                                        <input type="hidden" :name="`variants[${index}][id]`" :value="variant.id">
                                    </template>

                                    <div class="grid gap-2 lg:grid-cols-12">
                                        <div class="lg:col-span-2">
                                            <label class="form-label text-xs" x-show="index === 0">{{ __('catalog.labels.color') }}</label>
                                            <select :name="`variants[${index}][color_id]`"
                                                    x-model="variant.color_id"
                                                    x-on:change="refreshSku(index)"
                                                    class="form-select text-sm">
                                                <option value="">{{ __('catalog.variants.no_color') }}</option>
                                                <template x-for="color in colors" :key="color.id">
                                                    <option :value="color.id" x-text="color.name"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div class="lg:col-span-2">
                                            <label class="form-label text-xs" x-show="index === 0">{{ __('catalog.labels.size') }}</label>
                                            <select :name="`variants[${index}][size_id]`"
                                                    x-model="variant.size_id"
                                                    x-on:change="refreshSku(index)"
                                                    class="form-select text-sm">
                                                <option value="">{{ __('catalog.variants.no_size') }}</option>
                                                <template x-for="size in sizes" :key="size.id">
                                                    <option :value="size.id" x-text="size.name"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div class="lg:col-span-3">
                                            <label class="form-label text-xs" x-show="index === 0">{{ __('catalog.labels.sku') }}</label>
                                            <input type="text" :name="`variants[${index}][sku]`"
                                                   x-model="variant.sku" required dir="ltr"
                                                   class="form-input text-sm font-mono">
                                        </div>

                                        <div class="lg:col-span-1">
                                            <label class="form-label text-xs" x-show="index === 0">{{ __('catalog.fields.stock') }}</label>
                                            <input type="number" min="0" :name="`variants[${index}][stock_quantity]`"
                                                   x-model.number="variant.stock_quantity" required dir="ltr"
                                                   class="form-input text-sm">
                                        </div>

                                        <div class="lg:col-span-1">
                                            <label class="form-label text-xs" x-show="index === 0">{{ __('catalog.fields.threshold') }}</label>
                                            <input type="number" min="0" :name="`variants[${index}][low_stock_threshold]`"
                                                   x-model.number="variant.low_stock_threshold" required dir="ltr"
                                                   class="form-input text-sm">
                                        </div>

                                        {{-- Blank means "inherit the product price", not zero. --}}
                                        <div class="lg:col-span-1">
                                            <label class="form-label text-xs" x-show="index === 0">{{ __('catalog.fields.price_override') }}</label>
                                            <input type="number" step="0.01" min="0" :name="`variants[${index}][price]`"
                                                   x-model="variant.price" dir="ltr"
                                                   :placeholder="@js(__('catalog.variants.inherits'))"
                                                   class="form-input text-sm">
                                        </div>

                                        <div class="lg:col-span-1">
                                            <label class="form-label text-xs" x-show="index === 0">{{ __('catalog.fields.sale_price') }}</label>
                                            <input type="number" step="0.01" min="0" :name="`variants[${index}][sale_price]`"
                                                   x-model="variant.sale_price" dir="ltr"
                                                   class="form-input text-sm">
                                        </div>

                                        <div class="flex items-end justify-between gap-2 lg:col-span-1">
                                            <label class="flex items-center gap-1.5 pb-2 text-xs text-hoor-navy-700"
                                                   :title="@js(__('catalog.fields.is_active'))">
                                                <input type="hidden" :name="`variants[${index}][is_active]`" value="0">
                                                <input type="checkbox" :name="`variants[${index}][is_active]`" value="1"
                                                       x-model="variant.is_active"
                                                       class="rounded border-hoor-cream-300 text-hoor-navy-500 focus:ring-hoor-denim-500">
                                            </label>

                                            <button type="button" x-on:click="removeVariant(index)"
                                                    class="pb-2 text-lg leading-none text-hoor-muted transition hover:text-red-600"
                                                    :title="@js(__('catalog.variants.remove'))"
                                                    aria-label="{{ __('catalog.variants.remove') }}">&times;</button>
                                        </div>
                                    </div>


                                    {{-- Server-side errors for this row, resolved by index. --}}
                                    @foreach (['sku', 'stock_quantity', 'low_stock_threshold', 'price', 'sale_price', 'size_id', 'color_id'] as $field)
                                        <template x-if="errors[`variants.${index}.{{ $field }}`]">
                                            <p class="form-error" x-text="errors[`variants.${index}.{{ $field }}`]"></p>
                                        </template>
                                    @endforeach
                                </div>
                            </template>
                        </div>
                    </div>
                </x-slot:variants>

                {{-- =========================================================== SEO --}}
                <x-slot:seo>
                    <div class="grid max-w-3xl gap-5 pb-6 lg:grid-cols-2">
                        <x-ui.input name="meta_title_en" :label="__('catalog.fields.meta_title_en')"
                                    :value="$product->meta_title_en" dir="ltr" />

                        <x-ui.input name="meta_title_ar" :label="__('catalog.fields.meta_title_ar')"
                                    :value="$product->meta_title_ar" dir="rtl" />

                        <x-ui.textarea name="meta_description_en" rows="3"
                                       :label="__('catalog.fields.meta_desc_en')"
                                       :value="$product->meta_description_en" dir="ltr" />

                        <x-ui.textarea name="meta_description_ar" rows="3"
                                       :label="__('catalog.fields.meta_desc_ar')"
                                       :value="$product->meta_description_ar" dir="rtl" />
                    </div>
                </x-slot:seo>
            </x-admin.tabs>
        </div>

        {{-- Actions stay outside the tabs: saving applies every section at once. --}}
        <div class="flex items-center justify-between gap-3 border-t border-hoor-cream-300 px-5 py-4 sm:px-6">
            <a href="{{ route('admin.products.index') }}" class="btn-ghost btn-sm">
                {{ __('common.actions.cancel') }}
            </a>

            <x-ui.button type="submit" variant="primary">
                {{ __('common.actions.save') }}
            </x-ui.button>
        </div>
    </div>
</form>

@push('scripts')
<script>
    /**
     * Variant repeater state.
     *
     * Rows are keyed by a stable synthetic key rather than their array index so
     * that removing a row does not make Alpine reuse DOM nodes for a different
     * variant.
     */
    function productForm({ variants, colors, sizes, namePrefix }) {
        let seq = 0;

        return {
            colors,
            sizes,
            namePrefix,
            pendingImages: [],
            errors: @js($errors->messages() ? collect($errors->messages())->map(fn ($m) => $m[0])->all() : []),

            variants: (variants || []).map(v => ({
                _key: `existing-${seq++}`,
                id: v.id ?? null,
                color_id: v.color_id ?? '',
                size_id: v.size_id ?? '',
                sku: v.sku ?? '',
                stock_quantity: Number(v.stock_quantity ?? 0),
                low_stock_threshold: Number(v.low_stock_threshold ?? 3),
                price: v.price ?? '',
                sale_price: v.sale_price ?? '',
                is_active: v.is_active === undefined ? true : !!Number(v.is_active),
            })),

            addVariant() {
                this.variants.push({
                    _key: `new-${seq++}`,
                    id: null,
                    color_id: '',
                    size_id: '',
                    sku: this.suggestSku('', ''),
                    stock_quantity: 0,
                    low_stock_threshold: 3,
                    price: '',
                    sale_price: '',
                    is_active: true,
                });
            },

            removeVariant(index) {
                this.variants.splice(index, 1);
            },

            /**
             * Suggest a readable SKU. Only fills blank or still-suggested
             * fields, so a hand-written SKU is never overwritten.
             */
            refreshSku(index) {
                const variant = this.variants[index];

                if (variant.id || (variant.sku && !variant.sku.startsWith('HOOR-'))) {
                    return;
                }

                variant.sku = this.suggestSku(variant.color_id, variant.size_id);
            },

            suggestSku(colorId, sizeId) {
                const color = this.colors.find(c => String(c.id) === String(colorId));
                const size = this.sizes.find(s => String(s.id) === String(sizeId));

                return ['HOOR', color?.name?.slice(0, 3).toUpperCase(), size?.code]
                    .filter(Boolean)
                    .join('-')
                    .toUpperCase() || 'HOOR';
            },

            totalStock() {
                return this.variants
                    .filter(v => v.is_active)
                    .reduce((sum, v) => sum + (Number(v.stock_quantity) || 0), 0);
            },
        };
    }
</script>
@endpush
