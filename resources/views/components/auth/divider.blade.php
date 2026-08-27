{{-- A rule with a word set into it, for the "or" between action and cross-link. --}}
@props(['label' => null])

<div class="relative py-1" aria-hidden="true">
    <div class="flex items-center">
        <span class="h-px flex-1 bg-hoor-cream-300"></span>

        @if ($label)
            <span class="px-3 text-xs text-hoor-navy-500/60">{{ $label }}</span>
            <span class="h-px flex-1 bg-hoor-cream-300"></span>
        @endif
    </div>
</div>
