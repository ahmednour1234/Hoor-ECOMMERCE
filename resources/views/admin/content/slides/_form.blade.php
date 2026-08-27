{{--
    The hero slide form, shared by create and edit.

    Copy is optional throughout: a slide with only a photograph falls back to
    the brand's own headline, so an admin can put an image up without writing
    anything.
--}}
@props(['slide' => null])

<form method="POST"
      action="{{ $slide ? route('admin.slides.update', $slide) : route('admin.slides.store') }}"
      enctype="multipart/form-data"
      class="card space-y-6 p-6">

    @csrf
    @if ($slide)
        @method('PATCH')
    @endif

    @if ($errors->any())
        <x-ui.alert variant="danger">{{ __('catalog.messages.has_errors') }}</x-ui.alert>
    @endif

    @if ($slide)
        <img src="{{ $slide->imageUrl() }}" alt=""
             class="aspect-[12/5] w-full rounded-sm object-cover">
    @endif

    <div>
        <label for="image" class="form-label">
            {{ __('content.slides.image') }}
            @unless ($slide) <span class="text-red-600">*</span> @endunless
        </label>

        <input type="file" name="image" id="image" accept="image/*" class="form-input">

        <p class="form-hint">{{ __('content.slides.image_hint') }}</p>

        @error('image')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="backdrop" class="form-label">{{ __('content.slides.backdrop') }}</label>

        <div class="flex items-center gap-3">
            <input type="color" id="backdrop-picker" class="h-10 w-14 rounded border border-hoor-cream-300"
                   value="{{ old('backdrop', $slide?->backdrop) ?: '#CAB296' }}"
                   oninput="document.getElementById('backdrop').value = this.value">

            <input type="text" name="backdrop" id="backdrop" dir="ltr"
                   value="{{ old('backdrop', $slide?->backdrop) }}"
                   placeholder="#CAB296" class="form-input flex-1">
        </div>

        <p class="form-hint">{{ __('content.slides.backdrop_hint') }}</p>

        @error('backdrop')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    {{-- Copy, side by side in both languages so a translation is not forgotten. --}}
    @foreach (['eyebrow' => 'text', 'headline' => 'text', 'subheadline' => 'text', 'cta_label' => 'text'] as $field => $type)
        <div class="grid gap-5 sm:grid-cols-2">
            @foreach (['ar', 'en'] as $locale)
                @php $name = $field.'_'.$locale; @endphp

                <div>
                    <label for="{{ $name }}" class="form-label">
                        {{ __('content.slides.'.$field) }}
                        <span class="text-xs font-normal text-hoor-muted">
                            ({{ strtoupper($locale) }})
                        </span>
                    </label>

                    <input type="text" name="{{ $name }}" id="{{ $name }}"
                           value="{{ old($name, $slide?->{$name}) }}"
                           @if ($locale === 'ar') dir="rtl" @else dir="ltr" @endif
                           class="form-input">

                    @error($name)<p class="form-error">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </div>
    @endforeach

    <div class="grid gap-5 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <label for="cta_url" class="form-label">{{ __('content.slides.cta_url') }}</label>
            <input type="text" name="cta_url" id="cta_url" dir="ltr"
                   value="{{ old('cta_url', $slide?->cta_url) }}" class="form-input">
            @error('cta_url')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="position" class="form-label">{{ __('content.slides.position') }}</label>
            <input type="number" name="position" id="position" min="0" dir="ltr"
                   value="{{ old('position', $slide?->position ?? 0) }}" class="form-input">
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm text-hoor-navy-700">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1"
               class="rounded border-hoor-cream-300 text-hoor-navy-500 focus:ring-hoor-denim-500"
               @checked(old('is_active', $slide?->is_active ?? true))>
        <span>{{ __('content.slides.active') }}</span>
    </label>

    <div class="flex gap-3 border-t border-hoor-cream-300 pt-5">
        <x-ui.button type="submit" variant="primary">{{ __('common.actions.save') }}</x-ui.button>

        <x-ui.button variant="ghost" :href="route('admin.slides.index')">
            {{ __('common.actions.cancel') }}
        </x-ui.button>
    </div>
</form>
