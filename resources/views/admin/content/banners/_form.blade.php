{{--
    The banner form, shared by create and edit.

    The date range is the interesting part: a sale banner given an end date
    stops showing by itself, rather than relying on someone remembering.
--}}
@props(['banner' => null])

<form method="POST"
      action="{{ $banner ? route('admin.banners.update', $banner) : route('admin.banners.store') }}"
      enctype="multipart/form-data"
      class="card space-y-6 p-6">

    @csrf
    @if ($banner)
        @method('PATCH')
    @endif

    @if ($errors->any())
        <x-ui.alert variant="danger">{{ __('catalog.messages.has_errors') }}</x-ui.alert>
    @endif

    <x-ui.select name="placement"
                 :label="__('content.banners.placement')"
                 :options="collect(\App\Http\Requests\Admin\BannerRequest::PLACEMENTS)
                     ->mapWithKeys(fn ($p) => [$p => __('content.banners.placements.'.$p)])->all()"
                 :selected="old('placement', $banner?->placement)"
                 required />

    @if ($banner?->imageUrl())
        <img src="{{ $banner->imageUrl() }}" alt="" class="w-full rounded-sm object-cover">
    @endif

    <div>
        <label for="image" class="form-label">{{ __('content.banners.image') }}</label>
        <input type="file" name="image" id="image" accept="image/*" class="form-input">
        @error('image')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    @foreach (['title', 'body', 'cta_label'] as $field)
        <div class="grid gap-5 sm:grid-cols-2">
            @foreach (['ar', 'en'] as $locale)
                @php $name = $field.'_'.$locale; @endphp

                <div>
                    <label for="{{ $name }}" class="form-label">
                        {{ __('content.banners.'.($field === 'title' ? 'heading' : $field)) }}
                        <span class="text-xs font-normal text-hoor-muted">({{ strtoupper($locale) }})</span>
                    </label>

                    <input type="text" name="{{ $name }}" id="{{ $name }}"
                           value="{{ old($name, $banner?->{$name}) }}"
                           @if ($locale === 'ar') dir="rtl" @else dir="ltr" @endif
                           class="form-input">

                    @error($name)<p class="form-error">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </div>
    @endforeach

    <div>
        <label for="cta_url" class="form-label">{{ __('content.banners.cta_url') }}</label>
        <input type="text" name="cta_url" id="cta_url" dir="ltr"
               value="{{ old('cta_url', $banner?->cta_url) }}" class="form-input">
        @error('cta_url')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <label for="starts_at" class="form-label">{{ __('content.banners.starts_at') }}</label>
            <input type="datetime-local" name="starts_at" id="starts_at" dir="ltr"
                   value="{{ old('starts_at', $banner?->starts_at?->format('Y-m-d\TH:i')) }}"
                   class="form-input">
            @error('starts_at')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="ends_at" class="form-label">{{ __('content.banners.ends_at') }}</label>
            <input type="datetime-local" name="ends_at" id="ends_at" dir="ltr"
                   value="{{ old('ends_at', $banner?->ends_at?->format('Y-m-d\TH:i')) }}"
                   class="form-input">
            @error('ends_at')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="position" class="form-label">{{ __('content.banners.position') }}</label>
            <input type="number" name="position" id="position" min="0" dir="ltr"
                   value="{{ old('position', $banner?->position ?? 0) }}" class="form-input">
        </div>
    </div>

    <p class="-mt-3 text-xs text-hoor-muted">{{ __('content.banners.schedule_hint') }}</p>

    <label class="flex items-center gap-2 text-sm text-hoor-navy-700">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1"
               class="rounded border-hoor-cream-300 text-hoor-navy-500 focus:ring-hoor-denim-500"
               @checked(old('is_active', $banner?->is_active ?? true))>
        <span>{{ __('content.banners.active') }}</span>
    </label>

    <div class="flex gap-3 border-t border-hoor-cream-300 pt-5">
        <x-ui.button type="submit" variant="primary">{{ __('common.actions.save') }}</x-ui.button>

        <x-ui.button variant="ghost" :href="route('admin.banners.index')">
            {{ __('common.actions.cancel') }}
        </x-ui.button>
    </div>
</form>
