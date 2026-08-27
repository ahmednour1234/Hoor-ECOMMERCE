@props([
    'variant' => 'horizontal',   // horizontal | icon | primary
    'tone'    => 'blue',         // blue | white
    'class'   => 'h-9 w-auto',
])

<a href="{{ route('store.home') }}"
   class="inline-flex items-center"
   aria-label="{{ __('common.brand') }}">
    <img src="{{ asset("images/brand/hoor-{$variant}-{$tone}.svg") }}"
         alt="{{ __('common.brand') }}"
         class="{{ $class }}">
</a>
