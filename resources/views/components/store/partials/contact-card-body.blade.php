{{--
    The inside of a contact card, shared by its link and non-link forms.
--}}
<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full
             bg-hoor-cream-100 text-hoor-navy-500">
    <x-store.about-icon :name="$icon" class="h-5 w-5" />
</span>

<span class="min-w-0">
    <span class="block text-sm font-medium text-hoor-navy-700">{{ $title }}</span>

    <span class="mt-0.5 block truncate text-sm text-hoor-denim-600" dir="ltr">{{ $value }}</span>

    @if ($note)
        <span class="mt-1 block text-xs leading-snug text-hoor-muted">{{ $note }}</span>
    @endif
</span>
