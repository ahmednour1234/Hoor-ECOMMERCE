{{--
    The FAQ form, shared by create and edit.

    Both languages are required: a question that exists in only one would leave
    the other locale's accordion with a blank row.
--}}
@props(['faq' => null])

<form method="POST"
      action="{{ $faq ? route('admin.faqs.update', $faq) : route('admin.faqs.store') }}"
      class="card space-y-6 p-6">

    @csrf
    @if ($faq)
        @method('PATCH')
    @endif

    @if ($errors->any())
        <x-ui.alert variant="danger">{{ __('catalog.messages.has_errors') }}</x-ui.alert>
    @endif

    <div class="grid gap-5 sm:grid-cols-2">
        @foreach (['ar', 'en'] as $locale)
            @php $name = 'question_'.$locale; @endphp

            <div>
                <label for="{{ $name }}" class="form-label">
                    {{ __('content.faqs.question') }}
                    <span class="text-xs font-normal text-hoor-muted">({{ strtoupper($locale) }})</span>
                    <span class="text-red-600">*</span>
                </label>

                <input type="text" name="{{ $name }}" id="{{ $name }}" required
                       value="{{ old($name, $faq?->{$name}) }}"
                       @if ($locale === 'ar') dir="rtl" @else dir="ltr" @endif
                       class="form-input">

                @error($name)<p class="form-error">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        @foreach (['ar', 'en'] as $locale)
            @php $name = 'answer_'.$locale; @endphp

            <div>
                <label for="{{ $name }}" class="form-label">
                    {{ __('content.faqs.answer') }}
                    <span class="text-xs font-normal text-hoor-muted">({{ strtoupper($locale) }})</span>
                    <span class="text-red-600">*</span>
                </label>

                <textarea name="{{ $name }}" id="{{ $name }}" rows="4" required
                          @if ($locale === 'ar') dir="rtl" @else dir="ltr" @endif
                          class="form-textarea">{{ old($name, $faq?->{$name}) }}</textarea>

                @error($name)<p class="form-error">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="position" class="form-label">{{ __('content.faqs.position') }}</label>
            <input type="number" name="position" id="position" min="0" dir="ltr"
                   value="{{ old('position', $faq?->position ?? 0) }}" class="form-input">
        </div>

        <label class="flex items-center gap-2 self-end pb-2 text-sm text-hoor-navy-700">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1"
                   class="rounded border-hoor-cream-300 text-hoor-navy-500 focus:ring-hoor-denim-500"
                   @checked(old('is_active', $faq?->is_active ?? true))>
            <span>{{ __('content.faqs.active') }}</span>
        </label>
    </div>

    <div class="flex gap-3 border-t border-hoor-cream-300 pt-5">
        <x-ui.button type="submit" variant="primary">{{ __('common.actions.save') }}</x-ui.button>

        <x-ui.button variant="ghost" :href="route('admin.faqs.index')">
            {{ __('common.actions.cancel') }}
        </x-ui.button>
    </div>
</form>
