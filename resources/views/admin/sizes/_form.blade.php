@props(['size'])

@php($isEdit = $size->exists)

<form method="POST" action="{{ $isEdit ? route('admin.sizes.update', $size) : route('admin.sizes.store') }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="card max-w-2xl">
        <div class="card-body grid gap-5 sm:grid-cols-2">
            <x-ui.input name="code" :label="__('catalog.fields.code')"
                        :value="$size->code" required dir="ltr" class="font-mono" />

            <x-ui.input name="sort_order" type="number" min="0"
                        :label="__('catalog.fields.sort_order')"
                        :value="$size->sort_order ?? 0"
                        :hint="__('catalog.sizes.order_hint')" required dir="ltr" />

            <x-ui.input name="name_en" :label="__('catalog.fields.name_en')"
                        :value="$size->name_en" required dir="ltr" />

            <x-ui.input name="name_ar" :label="__('catalog.fields.name_ar')"
                        :value="$size->name_ar" required dir="rtl" />

            <div class="flex items-center">
                <x-ui.checkbox name="is_active" :label="__('catalog.fields.is_active')"
                               :checked="$size->is_active ?? true" />
            </div>
        </div>

        <div class="card-footer flex items-center justify-between">
            <a href="{{ route('admin.sizes.index') }}" class="btn-ghost btn-sm">
                {{ __('common.actions.cancel') }}
            </a>
            <x-ui.button type="submit" variant="primary">{{ __('common.actions.save') }}</x-ui.button>
        </div>
    </div>
</form>
