{{--
    A labelled input with a leading icon, and an eye toggle on password fields.

    The toggle is Alpine on the field itself rather than a shared script: it is
    two lines of state, and keeping it here means the component is complete on
    its own.
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'icon' => 'user',
    'placeholder' => null,
    'value' => null,
    'autocomplete' => null,
    'required' => false,
])

@php
    $isPassword = $type === 'password';

    $icons = [
        'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0114 0"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7.5l9 5.5 9-5.5"/>',
        'lock' => '<rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7a4 4 0 018 0v3.5"/>',
    ];

    $path = $icons[$icon] ?? $icons['user'];
    $hasError = $errors->has($name);
@endphp

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="block text-sm font-medium text-hoor-navy-700">
        {{ $label }}
        @if ($required)
            <span class="text-hoor-gold-600" aria-hidden="true">*</span>
        @endif
    </label>

    <div class="relative mt-1.5" @if ($isPassword) x-data="{ shown: false }" @endif>

        {{-- Leading icon, positioned with logical properties so it moves to
             the right edge in Arabic without a second rule.

             A quiet mark, not a glyph: the field's job is to be typed in,
             the field is there to be typed in, and an icon that competes
             with the placeholder makes it harder to read. --}}
        <span class="pointer-events-none absolute inset-y-0 start-0 flex w-10 items-center justify-center
                     text-hoor-navy-300" aria-hidden="true">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                {!! $path !!}
            </svg>
        </span>

        <input id="{{ $name }}"
               name="{{ $name }}"
               @if ($isPassword)
                   :type="shown ? 'text' : 'password'"
               @else
                   type="{{ $type }}"
               @endif
               value="{{ $type === 'password' ? '' : old($name, $value) }}"
               @if ($placeholder) placeholder="{{ $placeholder }}" @endif
               @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
               @if ($required) required @endif
               @if ($type === 'email') dir="ltr" @endif
               {{ $attributes->except('class') }}
               class="w-full rounded-md border bg-white/90 py-2.5 ps-10 text-sm text-hoor-navy-700
                      transition placeholder:text-hoor-navy-300
                      focus:border-hoor-navy-400 focus:outline-none focus:ring-2 focus:ring-hoor-denim-500/25
                      {{ $isPassword ? 'pe-10' : 'pe-3' }}
                      {{ $hasError ? 'border-red-400' : 'border-hoor-cream-300' }}">

        @if ($isPassword)
            {{-- Labelled dynamically, so a screen reader is told what the
                 press will do rather than what it did. --}}
            <button type="button"
                    @click="shown = ! shown"
                    :aria-label="shown ? @js(__('auth.hide_password')) : @js(__('auth.show_password'))"
                    class="absolute inset-y-0 end-0 flex w-10 items-center justify-center
                           text-hoor-navy-300 transition hover:text-hoor-navy-500">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                    <path x-show="! shown"
                          d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12z" />
                    <circle x-show="! shown" cx="12" cy="12" r="2.8" />

                    <path x-show="shown" x-cloak
                          d="M4 4l16 16M9.9 5.7A9.9 9.9 0 0112 5.5c6.4 0 10 6.5 10 6.5a17 17 0 01-3.4 4.1M6.4 7.9A17 17 0 002 12s3.6 6.5 10 6.5a9.9 9.9 0 002.3-.26" />
                </svg>
            </button>
        @endif
    </div>

    @error($name)
        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
