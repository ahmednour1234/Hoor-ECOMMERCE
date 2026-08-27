@props([
    'name',
    'label'       => null,
    'options'     => [],   // array<value, label>
    'selected'    => null,
    'placeholder' => null,
    'hint'        => null,
    'required'    => false,
])

@php
    $id       = $attributes->get('id', $name);
    $hasError = $errors->has($name);
    $current  = old($name, $selected);
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

    <select id="{{ $id }}"
            name="{{ $name }}"
            @if ($required) required @endif
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->except('class')->merge([
                'class' => 'form-select'.($hasError ? ' border-red-400 focus:border-red-500 focus:ring-red-500' : ''),
            ]) }}>

        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $value => $label)
            <option value="{{ $value }}" @selected((string) $current === (string) $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>

    @if ($hasError)
        <p id="{{ $id }}-error" class="form-error">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="form-hint">{{ $hint }}</p>
    @endif
</div>
