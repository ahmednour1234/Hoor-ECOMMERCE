{{--
    Destructive action as a real POST form rather than a link, so it cannot be
    triggered by a crawler or a prefetch.
--}}
@props([
    'action',
    'confirm' => null,
    'label'   => null,
])

<form method="POST"
      action="{{ $action }}"
      class="inline"
      @if ($confirm) onsubmit="return confirm(@js($confirm))" @endif>
    @csrf
    @method('DELETE')

    <button type="submit"
            {{ $attributes->merge(['class' => 'text-sm font-medium text-red-600 transition hover:text-red-700']) }}>
        {{ $label ?? __('common.actions.delete') }}
    </button>
</form>
