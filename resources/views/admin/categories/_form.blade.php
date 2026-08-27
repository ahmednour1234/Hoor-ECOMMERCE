@props(['category', 'parents'])

@php($isEdit = $category->exists)

<form method="POST"
      action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
      enctype="multipart/form-data">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid gap-5 lg:grid-cols-2">
            <x-ui.input name="name_en" :label="__('catalog.fields.name_en')"
                        :value="$category->name_en" required dir="ltr" />

            <x-ui.input name="name_ar" :label="__('catalog.fields.name_ar')"
                        :value="$category->name_ar" required dir="rtl" />

            <x-ui.input name="slug" :label="__('catalog.fields.slug')"
                        :value="$category->slug" :hint="__('catalog.fields.slug_hint')" dir="ltr" />

            <x-ui.select name="parent_id" :label="__('catalog.fields.parent')"
                         :options="$parents" :selected="$category->parent_id"
                         :placeholder="__('catalog.fields.no_parent')" />

            <x-ui.textarea name="description_en" :label="__('catalog.fields.description_en')"
                           :value="$category->description_en" dir="ltr" />

            <x-ui.textarea name="description_ar" :label="__('catalog.fields.description_ar')"
                           :value="$category->description_ar" dir="rtl" />

            <x-ui.input name="sort_order" type="number" min="0"
                        :label="__('catalog.fields.sort_order')"
                        :value="$category->sort_order ?? 0" dir="ltr" />

            <div class="flex items-center">
                <x-ui.checkbox name="is_active" :label="__('catalog.fields.is_active')"
                               :checked="$category->is_active ?? true" />
            </div>

            {{-- Banner image --}}
            <div class="lg:col-span-2">
                <label class="form-label" for="image">{{ __('catalog.fields.image') }}</label>

                @if ($category->image)
                    <div class="mb-3 flex items-center gap-4">
                        <img src="{{ $category->imageUrl() }}" alt=""
                             class="h-20 w-20 rounded-sm border border-hoor-cream-300 object-cover">

                        <x-ui.checkbox name="remove_image" :label="__('catalog.fields.remove_image')" />
                    </div>
                @endif

                <input type="file" id="image" name="image"
                       accept="image/jpeg,image/png,image/webp" class="form-input">

                @error('image')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            {{-- SEO --}}
            <x-ui.input name="meta_title_en" :label="__('catalog.fields.meta_title_en')"
                        :value="$category->meta_title_en" dir="ltr" />

            <x-ui.input name="meta_title_ar" :label="__('catalog.fields.meta_title_ar')"
                        :value="$category->meta_title_ar" dir="rtl" />

            <x-ui.textarea name="meta_description_en" rows="2"
                           :label="__('catalog.fields.meta_desc_en')"
                           :value="$category->meta_description_en" dir="ltr" />

            <x-ui.textarea name="meta_description_ar" rows="2"
                           :label="__('catalog.fields.meta_desc_ar')"
                           :value="$category->meta_description_ar" dir="rtl" />
        </div>

        <div class="card-footer flex items-center justify-between">
            <a href="{{ route('admin.categories.index') }}" class="btn-ghost btn-sm">
                {{ __('common.actions.cancel') }}
            </a>

            <x-ui.button type="submit" variant="primary">{{ __('common.actions.save') }}</x-ui.button>
        </div>
    </div>
</form>
