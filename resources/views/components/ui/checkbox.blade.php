{{--
    Checkbox with a paired hidden input so an unchecked box still submits a
    value — otherwise clearing a flag would silently leave it unchanged.
--}}
@props([
    'name',
    'label'   => null,
    'hint'    => null,
    'checked' => false,
])

@php
    $id = $attributes->get('id', $name);
@endphp

<label for="{{ $id }}" {{ $attributes->only('class')->merge(['class' => 'flex items-start gap-3 cursor-pointer']) }}>
    <input type="hidden" name="{{ $name }}" value="0">

    <input type="checkbox"
           id="{{ $id }}"
           name="{{ $name }}"
           value="1"
           @checked(old($name, $checked))
           {{ $attributes->except('class')->merge([
               'class' => 'mt-0.5 h-4 w-4 rounded border-hoor-cream-300 text-hoor-navy-500 focus:ring-hoor-denim-500',
           ]) }}>

    <span>
        @if ($label)
            <span class="text-sm font-medium text-hoor-navy-700">{{ $label }}</span>
        @endif
        @if ($hint)
            <span class="mt-0.5 block text-xs text-hoor-muted">{{ $hint }}</span>
        @endif
    </span>
</label>
