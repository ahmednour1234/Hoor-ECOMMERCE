@props(['color'])

@php($isEdit = $color->exists)

<form method="POST" action="{{ $isEdit ? route('admin.colors.update', $color) : route('admin.colors.store') }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="card max-w-2xl">
        <div class="card-body grid gap-5 sm:grid-cols-2"
             x-data="{ hex: @js(old('hex', $color->hex ?? '#2B4166')) }">

            <x-ui.input name="name_en" :label="__('catalog.fields.name_en')"
                        :value="$color->name_en" required dir="ltr" />

            <x-ui.input name="name_ar" :label="__('catalog.fields.name_ar')"
                        :value="$color->name_ar" required dir="rtl" />

            <x-ui.input name="slug" :label="__('catalog.fields.slug')"
                        :value="$color->slug" :hint="__('catalog.fields.slug_hint')" dir="ltr" />

            {{-- Text field and picker stay in sync so either can be used. --}}
            <div>
                <label class="form-label" for="hex">{{ __('catalog.fields.hex') }}</label>

                <div class="flex gap-2">
                    <input type="text" id="hex" name="hex" x-model="hex" required dir="ltr"
                           class="form-input font-mono" maxlength="7">

                    <input type="color" x-model="hex" aria-hidden="true" tabindex="-1"
                           class="h-10 w-12 shrink-0 cursor-pointer rounded-sm border border-hoor-cream-300">
                </div>

                @error('hex')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <x-ui.input name="sort_order" type="number" min="0"
                        :label="__('catalog.fields.sort_order')"
                        :value="$color->sort_order ?? 0" dir="ltr" />

            <div class="flex items-center">
                <x-ui.checkbox name="is_active" :label="__('catalog.fields.is_active')"
                               :checked="$color->is_active ?? true" />
            </div>
        </div>

        <div class="card-footer flex items-center justify-between">
            <a href="{{ route('admin.colors.index') }}" class="btn-ghost btn-sm">
                {{ __('common.actions.cancel') }}
            </a>
            <x-ui.button type="submit" variant="primary">{{ __('common.actions.save') }}</x-ui.button>
        </div>
    </div>
</form>
