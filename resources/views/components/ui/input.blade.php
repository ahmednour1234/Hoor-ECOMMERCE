{{--
    Labelled text input with inline validation feedback.

    Errors are read from the shared error bag by field name, so a controller
    only has to fail validation for the message to appear here.
--}}
@props([
    'name',
    'label'    => null,
    'type'     => 'text',
    'value'    => null,
    'hint'     => null,
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
            @if ($required)
                <span class="text-red-600" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <input type="{{ $type }}"
           id="{{ $id }}"
           name="{{ $name }}"
           value="{{ old($name, $value) }}"
           @if ($required) required @endif
           @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
           {{ $attributes->except('class')->merge([
               'class' => 'form-input'.($hasError ? ' border-red-400 focus:border-red-500 focus:ring-red-500' : ''),
           ]) }}>

    @if ($hasError)
        <p id="{{ $id }}-error" class="form-error">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="form-hint">{{ $hint }}</p>
    @endif
</div>
