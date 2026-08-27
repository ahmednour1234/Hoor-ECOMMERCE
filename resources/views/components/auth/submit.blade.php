{{-- The card's primary action: navy, full width, with the brand ornament. --}}
@props(['label'])

<button type="submit"
        {{ $attributes->merge([
            'class' => 'group flex w-full items-center justify-center gap-2.5 rounded-md
                        bg-hoor-navy-500 px-6 py-3 text-sm font-medium tracking-wide
                        text-hoor-cream-50 shadow-card transition duration-200 ease-hoor
                        hover:bg-hoor-navy-700 hover:shadow-card-hover
                        focus:outline-none focus:ring-2 focus:ring-hoor-denim-500/40 focus:ring-offset-2',
        ]) }}>

    <svg class="h-4 w-4 text-hoor-gold-500 transition-transform duration-500 group-hover:rotate-90"
         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true">
        <path d="M12 3l2.4 6.6L21 12l-6.6 2.4L12 21l-2.4-6.6L3 12l6.6-2.4L12 3z" />
    </svg>

    {{ $label }}
</button>
