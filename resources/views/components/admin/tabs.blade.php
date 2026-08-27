{{--
    Tabbed sections for a single form.

    The tabs are client-side only: every panel stays in the DOM and inside the
    same <form>, so all sections submit together and nothing is lost when
    validation fails. A tab whose fields have errors is flagged, so the admin is
    never left hunting through hidden panels for the problem.

    Usage:
        <x-admin.tabs :tabs="[
            ['key' => 'general', 'label' => 'General', 'errors' => ['name_en', 'slug']],
        ]">
            <x-slot:general> ... </x-slot:general>
        </x-admin.tabs>
--}}
@props(['tabs' => []])

@php
    // Resolve which tabs currently carry validation errors, and open the first
    // of them on load rather than the default tab.
    $tabState = collect($tabs)->map(function (array $tab) use ($errors): array {
        $patterns = $tab['errors'] ?? [];

        $tab['hasErrors'] = collect($patterns)->contains(
            fn (string $pattern): bool => $errors->hasAny([$pattern])
                || collect($errors->keys())->contains(
                    fn (string $key): bool => str_starts_with($key, rtrim($pattern, '*'))
                )
        );

        return $tab;
    });

    $initial = $tabState->firstWhere('hasErrors', true)['key']
        ?? ($tabState->first()['key'] ?? null);
@endphp

<div x-data="{ tab: @js($initial) }" {{ $attributes }}>

    {{-- Tab bar --}}
    <div class="border-b border-hoor-cream-300">
        <nav class="-mb-px flex gap-1 overflow-x-auto no-scrollbar" role="tablist">
            @foreach ($tabState as $tab)
                <button type="button"
                        role="tab"
                        :aria-selected="tab === @js($tab['key'])"
                        @click="tab = @js($tab['key'])"
                        class="flex items-center gap-2 whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition"
                        :class="tab === @js($tab['key'])
                            ? 'border-hoor-navy-500 text-hoor-navy-700'
                            : 'border-transparent text-hoor-muted hover:border-hoor-cream-400 hover:text-hoor-navy-600'">
                    {{ $tab['label'] }}

                    @if ($tab['hasErrors'])
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-red-100 text-xs font-semibold text-red-700"
                              title="{{ __('catalog.messages.has_errors') }}">!</span>
                    @endif
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Panels: kept in the DOM so every field submits regardless of the active tab. --}}
    <div class="pt-6">
        @foreach ($tabState as $tab)
            <div x-show="tab === @js($tab['key'])" x-cloak role="tabpanel">
                {{ ${$tab['key']} ?? '' }}
            </div>
        @endforeach
    </div>
</div>
