@props([
    'name',
    'label'    => null,
    'value'    => null,
    'hint'     => null,
    'rows'     => 4,
    'required' => false,
])

@php
    $id       = $attributes->get('id', $name);
    $hasError = $errors->has($name);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    @if ($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}
            @if ($required)<span class="text-red-600" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <textarea id="{{ $id }}"
              name="{{ $name }}"
              rows="{{ $rows }}"
              @if ($required) required @endif
              @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
              {{ $attributes->except('class')->merge([
                  'class' => 'form-textarea'.($hasError ? ' border-red-400 focus:border-red-500 focus:ring-red-500' : ''),
              ]) }}>{{ old($name, $value) }}</textarea>

    @if ($hasError)
        <p id="{{ $id }}-error" class="form-error">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="form-hint">{{ $hint }}</p>
    @endif
</div>
